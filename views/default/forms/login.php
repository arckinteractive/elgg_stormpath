<?php

namespace Arck\Stormpath;

$sso = elgg_get_plugin_setting('idsite', PLUGIN_ID);

if ($sso) {
	echo elgg_view('stormpath/sso_login');
} else {
	echo elgg_view('stormpath/original_login');
}