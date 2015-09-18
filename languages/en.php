<?php

$english = array(
	'admin:stormpath' => "Stormpath",
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
	'stormpath:login:local' => "Local Login",
	
	'stormpath:settings:idsite' => "ID Site Login",
	'stormpath:settings:idsite:help' => "Force logins through the SSO ID Site portal?",
	
	'admin:stormpath:import' => "Import",
	'stormpath:import:required:vroom' => "The vroom plugin is required for this import",
	'stormpath:import:title' => "There are %s users who are not represented in Stormpath",
	'stormpath:import:description' => "This action will import your existing Elgg users to Stormpath.  Their password will be reset and they will be sent an email notifying them of the change.",
	'stormpath:import:description:unnecessary' => "There is no need to import anything at this time",
	'stormpath:import:directory:instructions' => "Before you import your users you <b>MUST</b> disable email verification on your Stormpath directory otherwise your users will be listed as unverified.",
	'stormpath:import:label:subject' => "Email Subject",
	'stormpath:import:subject' => "Your password has been reset",
	'stormpath:import:label:message' => "Email Message",
	'stormpath:import:error:fields' => "Please enter a subject/message",
	'stormpath:import:running' => "The Stormpath import is running, it may take a while if you have a large user base.  Keep checking back to see the progress.",
	'stormpath:import:message' => "Hello {{name}},

We have recently upgraded our account login workflow for better security and the ability to have single sign on across all of our applications.
As part of this migration it is necessary to reset your password.  We apologize for any inconvenience.

Your new password is: {{password}}

You may log in at {{site_url}}

You may change the password on your next login by visiting your account settings.
If you have any issues logging in you may try the forgot password function at {{forgot_password}}

If you still have any issues please feel free to contact us at {{site_email}} for assistance.
",
	'stormpath:import:help:message' => "You can use the following tokens in the email message:

{{name}} - the users display name
{{username}} - the users username
{{email}} - the users email
{{password}} - the newly generated password
{{forgot_password}} - the url of the forgot password page
{{site_email}} - the site email address
{{site_url}} - the url of the site
",
);

add_translation("en", $english);
