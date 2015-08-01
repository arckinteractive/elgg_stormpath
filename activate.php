<?php

namespace Arck\Stormpath;

$version = elgg_get_plugin_setting('version', PLUGIN_ID);
if (!$version) {
	elgg_set_plugin_setting('version', PLUGIN_VERSION, PLUGIN_ID);
}

$importance = elgg_get_plugin_setting('importance', PLUGIN_ID);
if (!$importance) {
	elgg_set_plugin_setting('importance', 'sufficient', PLUGIN_ID);
}