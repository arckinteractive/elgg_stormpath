<?php

namespace Arck\Stormpath;

$application = get_application();

if ($vars['back']) {
	$_SESSION['last_forward_from'] = $vars['back'];
}

if (!$application) {
	forward('login');
}

try {
	$loginLink = $application->createIdSiteUrl(['callbackUri' => elgg_get_site_url() . 'stormpath/idsite']);
	forward($loginLink);
} catch (\Exception $exc) {
	register_error($exc->getMessage());
	forward('login');
}

forward('login');