<?php

namespace Arck\Stormpath;

$application = get_application();

try {
	$account = $application->verifyPasswordResetToken($vars['sptoken']);	
} catch (\Exception $exc) {
	register_error($exc->getMessage());
	forward('forgotpassword');
}

$title = elgg_echo('stormpath:resetpassword');

$layout = elgg_view_layout('one_column', array(
	'content' => elgg_view_form('user/passwordreset', array(), array(
		'sptoken' => $vars['sptoken']
	))
));

echo elgg_view_page($title, $layout);