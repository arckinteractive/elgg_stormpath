<?php

namespace Arck\Stormpath;

$application = get_application();
if (!$application) {
	forward('login');
}

try {
	$response = $application->handleIdSiteCallback($_SERVER['REQUEST_URI']);
} catch (\Exception $exc) {
	register_error($exc->getMessage());
	forward();
}

if ($response->status == 'LOGOUT') {
	logout();
	system_message(elgg_echo('stormpath:logout:success'));
	forward();
}

$account = false;
if ($response->status == 'AUTHENTICATED') {
	$account = $response->account;
}

if (!$account) {
	register_error(elgg_echo('stormpath:sso:error'));
	forward();
}

// log them in!
$users = elgg_get_entities_from_metadata(array(
	'type' => 'user',
	'metadata_name_value_pairs' => array(
		'name' => '__stormpath_user',
		'value' => $account->href
	)
));

if ($users) {
	login($users[0], true);
	forward();
}


// we don't have a local user attached to the stormpath account
// check for email match
$email = sanitise_string($account->email);
$dbprefix = elgg_get_config('dbprefix');
$users = elgg_get_entities(array(
	'type' => 'user',
	'joins' => array(
		"JOIN {$dbprefix}users_entity ue ON e.guid = ue.guid"
	),
	'wheres' => array(
		"ue.email = '{$email}'"
	),
	'limit' => 1
));

if ($users) {
	// link them for next time
	$users[0]->__stormpath_user = $account->href;
	login($users[0], true);
	forward();
}

elgg_set_context($context);
// we have no local users, create a new one
$user = new \ElggUser();
$user->username = preg_replace("/[^a-zA-Z0-9]/", "", $account->username);
$user->email = $account->email;
$user->name = $account->fullName;
$user->access_id = ACCESS_PUBLIC;
$user->salt = _elgg_generate_password_salt();
// set invalid PW that will never work for local login.  This can be changed by the user later
// but won't leave a secondary local login by accident
$user->password = _elgg_generate_password_salt();
$user->owner_guid = 0; // Users aren't owned by anyone, even if they are admin created.
$user->container_guid = 0; // Users aren't contained by anyone, even if they are admin created.
$user->language = get_current_language();
$user->save();

$user->__stormpath_user = $account->href;

elgg_set_user_validation_status($user->guid, TRUE, 'stormpath');

// Turn on email notifications by default
set_user_notification_setting($user->getGUID(), 'email', true);

// done with our extra permissions
elgg_pop_context();

login($user, true);
forward();