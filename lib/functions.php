<?php

namespace Arck\Stormpath;

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
	
	$params = array(
		'user' => $user,
		'account' => $account
	);
	$account = elgg_trigger_plugin_hook('elgg_stormpath', 'import', $params, $account);

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

function import_to_stormpath() {
	$dbprefix = elgg_get_config('dbprefix');
	$subject = elgg_get_plugin_setting('import_subject', PLUGIN_ID);
	$message = elgg_get_plugin_setting('import_message', PLUGIN_ID);
	$site = elgg_get_site_entity();
	$site_url = elgg_get_site_url();
	
	if (!$subject || !$message) { error_log('no subject/message');
		return true;
	}

	if (is_elgg18()) {
		$name_id = add_metastring('__stormpath_user');
		$value_id = add_metastring(1);
	} else {
		$name_id = elgg_get_metastring_id('__stormpath_user');
		$value_id = elgg_get_metastring_id(1);
	}

	$options = array(
		'type' => 'user',
		'joins' => array(
			"LEFT JOIN {$dbprefix}metadata md ON md.entity_guid = e.guid AND md.name_id = {$name_id}"
		),
		'wheres' => array(
			'md.name_id IS NULL'
		),
		'limit' => 20
	);

	$batch = new \ElggBatch('elgg_get_entities', $options);
	$batch->setIncrementOffset(false);

	foreach ($batch as $user) {
		// search stormpath for a matching account
		$application = get_application();
		$accts = $application->getAccounts(array('email' => $user->email));
		foreach ($accts as $a) {
			$user->__stormpath_user = $a->href;
			error_log('set user ' . $user->username . ': ' . $a->href);
			continue;
		}

		// change it locally
		$password = generate_random_cleartext_password();
		$user->salt = _elgg_generate_password_salt();
		$user->password = generate_user_password($user, $password);
		
		$user->save();

		error_log('adding to stormpath ' . $user->email);
		$result = add_to_stormpath($user, $password);
		
		if ($result) {
			// notify them of the change
			
			// replace tokens in the message
			$message_m = str_replace('{{password}}', $password, $message);
			$message_m = str_replace('{{name}}', $user->name, $message_m);
			$message_m = str_replace('{{username}}', $user->username, $message_m);
			$message_m = str_replace('{{email}}', $user->email, $message_m);
			$message_m = str_replace('{{forgot_password}}', $site_url . 'forgotpassword', $message_m);
			$message_m = str_replace('{{site_email}}', $site->email, $message_m);
			$message_m = str_replace('{{site_url}}', $site_url, $message_m);
		
			notify_user($user->guid, $site->guid, $subject, $message_m, null, 'email');
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
