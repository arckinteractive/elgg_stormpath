<?php

namespace Arck\Stormpath;

/**
 * Called on successful user login
 * If they are not in stormpath lets add them
 * 
 * @param type $event
 * @param type $type
 * @param type $user
 */
function event_user_login($event, $type, $user) {
	$access_status = access_get_show_hidden_status();
	access_show_hidden_entities(TRUE);

	if (($user instanceof ElggUser) && !$user->isEnabled() && !$user->validated) {
		// send new validation email
		uservalidationbyemail_request_validation($user->getGUID());
		
		// restore hidden entities settings
		access_show_hidden_entities($access_status);
		
		// throw error so we get a nice error message
		throw new LoginException(elgg_echo('uservalidationbyemail:login:fail'));
	}

	access_show_hidden_entities($access_status);
	
	if ($user->__stormpath_user) {
		return true;
	}
	
	// search stormpath for a matching account
	// may be in stormpath by manual addition, or from another application
	// with shared login
	$application = get_application();
	if ($application) {
		$accts = $application->getAccounts(array('email' => $user->email));
		foreach ($accts as $a) {
			$user->__stormpath_user = $a->href;
			return true;
		}
	
		$password = get_input('password');
		if ($password) {
			add_to_stormpath($user, $password);
		}
	}
	return true;
}


/**
 * When a user is created
 * 
 * @param type $hook
 * @param type $type
 * @param type $user
 * @return boolean
 */
function event_user_create($hook, $type, $user) {
	if ($user->__stormpath_user) {
		return true;
	}
	
	// search stormpath for a matching account
	$application = get_application();
	$accts = $application->getAccounts(array('email' => $user->email));
	foreach ($accts as $a) {
		$user->__stormpath_user = $a->href;
		return true;
	}
	
	$password = get_input('password');
	if ($password) {
		add_to_stormpath($user, $password);
	}
	
	return true;
}


/**
 * Make sure any admin users are automatically validated
 *
 * @param string   $event
 * @param string   $type
 * @param ElggUser $user
 */
function validate_new_admin_user($event, $type, $user) {
	if ($user instanceof \ElggUser && !$user->validated) {
		elgg_set_user_validation_status($user->guid, TRUE, 'admin_user');
	}
}