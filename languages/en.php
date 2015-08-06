<?php

$english = array(
	'stormpath:settings:keyfile:upload' => "Upload the apiKey.properties file for Stormpath",
	'stormpath:settings:keyfile:exists' => "A keyfile already exists on this system, uploading a new one will replace it",
	'stormpath:settings:app' => "Application",
	'stormpath:settings:importance' => "Require Stormpath authentication?",
	'stormpath:settings:importance:option:sufficient' => "No, allow local login (and other providers) as well as stormpath login",
	'stormpath:settings:importance:option:required' => "Yes, all authentication MUST go through Stormpath",
	'stormpath:settings:email_validate' => "Require email validation for new accounts?",
	'stormpath:settings:email_validate:help' => "If setting this to 'yes' make sure to configure the Stormpath Directory to use the email validation workflow.",
	'stormpath:resetpassword' => "Password Reset",
	'email:confirm:success' => "You have confirmed your email address!  You may now log in.",
	'stormpath:sso:error' => "There was an issue logging in, please try again",
	'stormpath:logout:success' => "You have been logged out",
	'stormpath:login:sso' => "Login with Stormpath",
	'stormpath:login:local' => "Local Login"
);

add_translation("en", $english);
