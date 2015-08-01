<?php

namespace Arck\Stormpath;

/**
 * 
 * Disable a new user pending email verification
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 * @return type
 */
function disable_new_user($hook, $type, $value, $params) {
	$user = elgg_extract('user', $params);
	
	if (!elgg_get_plugin_setting('email_validate', PLUGIN_ID)) {
		return;
	}

	// no clue what's going on, so don't react.
	if (!$user instanceof ElggUser) {
		return;
	}

	// another plugin is requesting that registration be terminated
	// no need for uservalidationbyemail
	if (!$value) {
		return $value;
	}

	// has the user already been validated?
	if (elgg_get_user_validation_status($user->guid) == true) {
		return $value;
	}

	// disable user to prevent showing up on the site
	// set context so our canEdit() override works
	elgg_push_context('stormpath_new_user');
	$hidden_entities = access_get_show_hidden_status();
	access_show_hidden_entities(TRUE);

	// Don't do a recursive disable.  Any entities owned by the user at this point
	// are products of plugins that hook into create user and might need
	// access to the entities.
	// @todo That ^ sounds like a specific case...would be nice to track it down...
	$user->disable('stormpath_new_user', FALSE);

	// set user as unvalidated and send out validation email
	elgg_set_user_validation_status($user->guid, FALSE);
	
	// trigger the stormpath email validation

	elgg_pop_context();
	access_show_hidden_entities($hidden_entities);

	return $value;
}


/**
 * Override the canEdit() call for if we're in the context of registering a new user.
 *
 * @param string $hook
 * @param string $type
 * @param bool   $value
 * @param array  $params
 * @return bool|null
 */
function allow_new_user_can_edit($hook, $type, $return, $params) {
	// $params['user'] is the user to check permissions for.
	// we want the entity to check, which is a user.
	$user = elgg_extract('entity', $params);

	if (!($user instanceof ElggUser)) {
		return;
	}

	$context = elgg_get_context();
	if ($context == 'stormpath_new_user' || $context == 'stormpath_validate_user') {
		return TRUE;
	}

	return;
}