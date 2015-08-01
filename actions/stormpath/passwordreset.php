<?php

namespace Arck\Stormpath;

$sptoken = get_input('sptoken');
$password = get_input('password');
$password2 = get_input('password2');

$application = get_application();

try {
	$account = $application->verifyPasswordResetToken($sptoken);	
} catch (\Exception $exc) {
	register_error($exc->getMessage());
	forward('forgotpassword');
}

try {
	$result = validate_password($password);
} catch (RegistrationException $e) {
	register_error($e->getMessage());
	forward(REFERER);
}

if ($password != $password2) {
	register_error(elgg_echo('user:password:fail:notsame'));
	forward(REFERER);
}

$account->password = $password;
$account->save();

system_message(elgg_echo('user:password:success'));
forward();