<?php

namespace Arck\Stormpath;

const PLUGIN_ID = 'elgg_stormpath';
const PLUGIN_VERSION = 20150730;

require_once __DIR__ . '/lib/events.php';
require_once __DIR__ . '/lib/hooks.php';
require_once __DIR__ . '/lib/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

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
		elgg_register_event_handler('make_admin', 'user', __NAMESPACE__ . '\\validate_new_admin_user');

		// mark users as unvalidated and disable when they register
		elgg_register_plugin_hook_handler('register', 'user', __NAMESPACE__ . '\\disable_new_user');

		// canEdit override to allow not logged in code to disable a user
		elgg_register_plugin_hook_handler('permissions_check', 'user', __NAMESPACE__ . '\\allow_new_user_can_edit');

		// add custom data to our stormpath user
		elgg_register_plugin_hook_handler('elgg_stormpath', 'import', __NAMESPACE__ . '\\stormpath_custom_data');
		
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

		if (elgg_is_active_plugin('vroom')) {
			elgg_register_action('stormpath/import', __DIR__ . '/actions/stormpath/import.php', 'admin');
		}
	}
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
			echo elgg_view('resources/stormpath/login', array(
				'back' => get_input('back', false)
			));
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

