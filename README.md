 #Elgg Stormpath

This plugin allows Elgg users to be synchonized with a Stormpath directory for shared credentials with other applications as single-sign-on.

 ## Installation

Unzip this plugin to mod/elgg_stormpath
Enable the plugin through the admin interface

 ## Configuration
Download the apiKeys.properties file from Stormpath and upload it in the plugin settings

Disable password restrictions in stormpath directory - if they are more restrictive than elgg then account creation will fail.

Password reset email base url = [url]/stormpath/passwordreset

Email verification base url = [url]/stormpath/emailverification

