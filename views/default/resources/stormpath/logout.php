<?php

namespace Arck\Stormpath;

$application = get_application();

if (!$application) {
	forward('action/logout');
}

try {
	$logoutLink = $application->createIdSiteUrl(['logout' => true, 'callbackUri' => elgg_get_site_url() . 'stormpath/idsite']);
	forward($logoutLink);
} catch (\Exception $exc) {
	register_error($exc->getMessage());
	forward('action/logout');
}

forward('action/logout');