<?php

namespace Arck\Stormpath;

const PLUGIN_ID = 'elgg_stormpath';
const PLUGIN_VERSION = 20150730;

require_once __DIR__ . '/lib/events.php';
require_once __DIR__ . '/lib/hooks.php';

elgg_register_event_handler('init', 'system', __NAMESPACE__ . '\\init');

/**
 * plugin initialization
 */
function init() {

	// register actions
	elgg_register_action('elgg_stormpath/settings/save', __DIR__ . '/actions/stormpath/settings.php', 'admin');

	// these things only work if we have a real api connection
	if (get_application()) {

		$importance = elgg_get_plugin_setting('importance', PLUGIN_ID);
		register_pam_handler(__NAMESPACE__ . '\\pam_handler', $importance);
		
		elgg_register_page_handler('stormpath', __NAMESPACE__ . '\\pagehandler');

		// add new users to stormpath
		elgg_register_event_handler('create', 'user', __NAMESPACE__ . '\\event_user_create', 1000);

		// make admin users always validated
		elgg_register_event_handler('make_admin', 'user', 'uservalidationbyemail_validate_new_admin_user');

		// mark users as unvalidated and disable when they register
		elgg_register_plugin_hook_handler('register', 'user', __NAMESPACE__ . '\\disable_new_user');

		// canEdit override to allow not logged in code to disable a user
		elgg_register_plugin_hook_handler('permissions_check', 'user', __NAMESPACE__ . '\\allow_new_user_can_edit');
		
		elgg_register_action('user/requestnewpassword', __DIR__ . '/actions/stormpath/requestnewpassword.php', 'public');
		elgg_register_action('user/passwordreset', __DIR__ . '/actions/stormpath/passwordreset.php', 'public');
		
		
		// differentiation for 1.8/newer compatibility
		if (is_elgg18()) {
			elgg_register_event_handler('login', 'user', __NAMESPACE__ . '\\event_user_login', 1000);
			elgg_unregister_plugin_hook_handler('usersettings:save', 'user', 'users_settings_save');
			elgg_register_plugin_hook_handler('usersettings:save', 'user', __NAMESPACE__ . '\\users_settings_save');
		} else {
			elgg_register_event_handler('login:after', 'user', __NAMESPACE__ . '\\event_user_login', 1000);
			elgg_unregister_plugin_hook_handler('usersettings:save', 'user', '_elgg_set_user_password');
			elgg_register_plugin_hook_handler('usersettings:save', 'user', __NAMESPACE__ . '\\set_user_password');
		}
	}
}

/**
 * Does our keyfile exist?
 * 
 * @return bool
 */
function api_keys_exists() {
	$file = get_api_file();

	return is_file($file);
}

/**
 * Get the location of our keyfile
 * 
 * @return string
 */
function get_api_file() {
	static $file;
	if ($file) {
		return $file;
	}
	
	$dir = elgg_get_config('dataroot') . 'stormpath';

	$file = $dir . '/apiKey.properties';

	return $file;
}

/**
 * Get our configured client singleton
 * 
 * @staticvar type $client
 * @return mixed \Stormpath\Client | false
 */
function get_client() {
	static $client;
	if ($client) {
		return $client;
	}

	require_once __DIR__ . '/vendor/autoload.php';

	if (!api_keys_exists()) {
		return false;
	}

	$builder = new \Stormpath\ClientBuilder();
	$apiKeyFile = get_api_file();
	$client = $builder->setApiKeyFileLocation($apiKeyFile)->build();

	return $client;
}

/**
 * Get our configured application
 * 
 * @staticvar type $application
 * @return \Stormpath\Application | false
 */
function get_application() {
	static $application;

	if ($application) {
		return $application;
	}

	$client = get_client();
	if (!$client) {
		return false;
	}

	$name = elgg_get_plugin_setting('app_name', PLUGIN_ID);
	
	if (!$name) {
		return false;
	}
	
	$apps = $client->tenant->applications;
	$apps->search = array('name' => $name);
	$application = $apps->getIterator()->current();

	if ($application) {
		return $application;
	}

	return false;
}

/**
 * Add an existing ElggUser to Stormpath
 * 
 * @param \ElggUser $user
 * @param type $password
 * @return boolean
 */
function add_to_stormpath(\ElggUser $user, $password) {
	$name_parts = explode(' ', $user->name);
	// check common metadata first
	$firstname = $user->first_name ? $user->first_name : $user->firstname;
	if (!$firstname) {
		$firstname = $name_parts[0];
	}

	$lastname = $user->last_name ? $user->last_name : $user->lastname;
	if (!$lastname) {
		$lastname = $name_parts[1] ? $name_parts[1] : $name_parts[0];
	}

	$client = get_client();

	// lets add them to stormpath
	$account = $client->dataStore->instantiate(\Stormpath\Stormpath::ACCOUNT);
	$account->givenName = $firstname;
	$account->username = $user->username;
	$account->surname = $lastname;
	$account->email = $user->email;
	$account->password = $password;

	try {
		$application = get_application();
		$acct = $application->createAccount($account);
		$user->__stormpath_user = $acct->href;
	} catch (Exception $exc) {
		// hmm, lets let this fail silently
		return false;
	}

	return true;
}

/**
 * Determine if we're running elgg 1.8 or newer
 * @return boolean
 */
function is_elgg18() {
	static $is_elgg18;
	
	if ($is_elgg18 !== null) {
		return $is_elgg18;
	}
	
	if (is_callable('elgg_get_version')) {
		return false; // this is newer than 1.8
	}

	$is_elgg18 = (strpos(get_version(true), '1.8') === 0);

	return $is_elgg18;
}

/**
 * Can we allow the user with the credentials to log in?
 * Check stormpath, create the user if they can log in and don't exist
 * Enable the user if they can log in but were waiting for email verification
 * 
 * @param type $credentials
 * @return boolean
 */
function pam_handler($credentials) {

	// try to authenticate first
	$application = get_application();
	$authResult = $application->authenticate($credentials['username'], $credentials['password']);
	$account = $authResult->account;

	if (!$account || strtolower($account->status) != 'enabled') {
		return false;
	}

	// we need to search hidden users too
	// in case of email confirmation disabling
	$show_hidden = access_get_show_hidden_status();
	access_show_hidden_entities(true);

	// we have an account and it's enabled
	// see if we have a matching account here
	// check if logging in with email address
	if (strpos($credentials['username'], '@') !== false) {
		$users = get_user_by_email($credentials['username']);
		$user = $users[0];
	} else {
		$user = get_user_by_username($credentials['username']);
	}

	// custom context gives us permission to do this
	elgg_push_context('stormpath_validate_user');

	// if we don't have a user we need to create one
	if (!$user) {
		$user = new \ElggUser();
		$user->username = preg_replace("/[^a-zA-Z0-9]/", "", $account->username);
		$user->email = $account->email;
		$user->name = $account->fullName;
		$user->access_id = ACCESS_PUBLIC;
		$user->salt = _elgg_generate_password_salt();
		$user->password = generate_user_password($user, $credentials['password']);
		$user->owner_guid = 0; // Users aren't owned by anyone, even if they are admin created.
		$user->container_guid = 0; // Users aren't contained by anyone, even if they are admin created.
		$user->language = get_current_language();
		$user->save();

		$user->__stormpath_user = $account->href;

		elgg_set_user_validation_status($user->guid, TRUE, 'stormpath');

		// Turn on email notifications by default
		set_user_notification_setting($user->getGUID(), 'email', true);
	}

	// see if we need to enable/verify the user
	if (!$user->isEnabled() && in_array($user->disable_reason, array('stormpath_new_user', 'uservalidationbyemail_new_user'))) {
		$user->enable();
		$user->__stormpath_user = $account->href;
		elgg_set_user_validation_status($user->guid, TRUE, 'stormpath');
	}

	elgg_pop_context();
	access_show_hidden_entities($show_hidden);

	if ($user && $user->isEnabled()) {
		return true;
	}

	return false;
}



function pagehandler($page) {
	switch ($page[0]) {
		case 'passwordreset':
			echo elgg_view('resources/stormpath/passwordreset', array(
				'sptoken' => get_input('sptoken')
			));
			return true;
			break;
		case 'emailverification':
			echo elgg_view('resources/stormpath/emailverification', array(
				'sptoken' => get_input('sptoken')
			));
			return true;
			break;
		case 'login':
			echo elgg_view('resources/stormpath/login');
			return true;
			break;
		case 'logout':
			echo elgg_view('resources/stormpath/logout');
			return true;
			break;
		case 'idsite':
			echo elgg_view('resources/stormpath/idsite');
			return true;
			break;
	}
	
	return false;
}