<?php
/**
 * Action to request a new password.
 */

namespace Arck\Stormpath;

$username = get_input('username');
$email = '';

// allow email addresses
if (strpos($username, '@') !== false) {
	$email = $username;
}

$account = false;

// search stormpath for a matching account
$application = get_application();
if ($email) {
	$accts = $application->getAccounts(array('email' => $email));
}
else {
	$accts = $application->getAccounts(array('username' => $username));
}
foreach ($accts as $a) {
	$account = $a;
	break;
}

if ($account) {
	
	try {
		$result = $application->sendPasswordResetEmail($account->email);
		
		if ($result) {
			system_message(elgg_echo('user:password:resetreq:success'));
		}
		else {
			register_error(elgg_echo('user:password:resetreq:fail'));
		}
	} catch (\Exception $exc) {
		register_error($exc->getMessage());
	}
} else {
	register_error(elgg_echo('user:username:notfound', array($username)));
}

forward();
