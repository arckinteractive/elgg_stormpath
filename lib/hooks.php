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
	if (!$user instanceof \ElggUser) {
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

	if (!($user instanceof \ElggUser)) {
		return;
	}

	$context = elgg_get_context();
	if ($context == 'stormpath_new_user' || $context == 'stormpath_validate_user') {
		return TRUE;
	}

	return;
}

function users_settings_save($hook, $type, $return, $params) {
	elgg_set_user_language();
	//elgg_set_user_password();
	set_user_password();
	elgg_set_user_default_access();
	elgg_set_user_name();
	elgg_set_user_email();
}

/**
 * Called on usersettings save action - changes the users password
 * locally and on stormpath
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 * @return boolean|null
 */
function set_user_password($hook = 'usersettings:save', $type = 'user', $return = true, $params = array()) {
	$current_password = get_input('current_password', null, false);
	$password = get_input('password', null, false);
	$password2 = get_input('password2', null, false);
	$user_guid = get_input('guid');

	if ($user_guid) {
		$user = get_user($user_guid);
	} else {
		$user = elgg_get_logged_in_user_entity();
	}

	if ($user && $password) {
		// let admin user change anyone's password without knowing it except his own.
		if (!elgg_is_admin_logged_in() || elgg_is_admin_logged_in() && $user->guid == elgg_get_logged_in_user_guid()) {
			$credentials = array(
				'username' => $user->email,
				'password' => $current_password
			);

			try {
				pam_handler($credentials);
			} catch (\LoginException $e) {
				register_error(elgg_echo('LoginException:ChangePasswordFailure'));
				return false;
			}
		}

		try {
			$result = validate_password($password);
		} catch (\RegistrationException $e) {
			register_error($e->getMessage());
			return false;
		}

		if ($result) {
			if ($password == $password2) {

				// change it on stormpath
				if ($user->__stormpath_user) {
					try {
						$client = get_client();
						$account = $client->dataStore->getResource($user->__stormpath_user, \Stormpath\Stormpath::ACCOUNT);
						$account->password = $password;
						$account->save();
					} catch (\Exception $exc) {
						register_error($exc->getMessage());
						return false;
					}
				} else {
					if ($password) {
						add_to_stormpath($user, $password);
					}
				}

				// change it locally
				$user->salt = _elgg_generate_password_salt();
				$user->password = generate_user_password($user, $password);

				if (is_elgg18()) {
					$user->code = '';
					if ($user->guid == elgg_get_logged_in_user_guid() && !empty($_COOKIE['elggperm'])) {
						// regenerate remember me code so no other user could
						// use it to authenticate later
						$code = _elgg_generate_remember_me_token();
						$_SESSION['code'] = $code;
						$user->code = md5($code);
						setcookie("elggperm", $code, (time() + (86400 * 30)), "/");
					}
				} else {
					_elgg_services()->persistentLogin->handlePasswordChange($user, elgg_get_logged_in_user_entity());
				}

				if ($user->save()) {
					system_message(elgg_echo('user:password:success'));
					return true;
				} else {
					register_error(elgg_echo('user:password:fail'));
				}
			} else {
				register_error(elgg_echo('user:password:fail:notsame'));
			}
		} else {
			register_error(elgg_echo('user:password:fail:tooshort'));
		}
	} else {
		// no change
		return null;
	}

	return false;
}

/**
 * Add custom data to stormpath for a user
 * 
 * @param type $hook
 * @param type $type
 * @param type $return
 * @param type $params
 * @return type
 */
function stormpath_custom_data($hook, $type, $return, $params) {
	$user = $params['user'];
	$account = $return;
	
	$customData = $account->customData;
	$customData->elgg_guid = $user->guid;
	
	return $account;
}
