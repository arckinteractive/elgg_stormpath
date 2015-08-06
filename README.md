#Elgg Stormpath

This plugin allows Elgg users to be synchonized with a Stormpath directory for shared credentials with other applications as single-sign-on.
Existing users will have accounts added to Stormpath the next time they sign into Elgg.

New users will have accounts added to Stormpath on registration.

Users from other applications that populate the associated Stormpath Directory will be able to log
in using their existing Stormpath credentials, and an Elgg account will be created for them.

This plugin handles endpoints for validating email verification as well as forgot password links.

##Installation

Unzip this plugin to mod/elgg_stormpath

Enable the plugin through the admin interface

##Configuration

Download the apiKeys.properties file from Stormpath and upload it in the plugin settings

Once the apiKeys have been uploaded, select your application from the dropdown and save the settings again.

##Stormpath Account Store

Enable password reset and email verification on the directory if required.

Custom url endpoints have been defined if necessary, use the following:

Password reset email base url ```[url]/stormpath/passwordreset```

Email verification base url ```[url]/stormpath/emailverification```

##Password Issues
If you are setting up Stormpath on an installation with existing users they may have
existing passwords that do not meet the requirements of Stormpath.  These restrictions
can be lifted in the Stormpath Directory.  By removing all password restrictions Stormpath
will be able to receive existing user passwords.

If you are setting this up on a new site with no existing users it would be preferable to change
the Elgg password restrictions to match Stormpath.

##ID Site

For SSO handling it's recommended to set up the ID Site in the Stormpath settings.

The authorized redirect endpoint for your site will be ```[url]/stormpath/idsite```

Login and logout urls are configured as such:

Login - ```[url]/stormpath/login```

Logout = ```[url]/stormpath/logout```

The workflows and presentation of SSO tend to vary from project to project, therefore
no UI has been implemented for this in this plugin.  Developers can add these SSO links
to their themes or other override plugins.

