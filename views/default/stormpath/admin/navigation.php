<?php

namespace Arck\Stormpath;


$tabs = array(
	array(
		'name' => 'settings',
		'text' => elgg_echo('settings'),
		'href' => 'admin/plugin_settings/elgg_stormpath',
		'selected' => (strpos(current_page_url(), elgg_get_site_url() . 'admin/plugin_settings/elgg_stormpath') === 0)
	),
	array(
		'name' => 'import',
		'text' => elgg_echo('import'),
		'href' => 'admin/stormpath/import',
		'selected' => (strpos(current_page_url(), elgg_get_site_url() . 'admin/stormpath/import') === 0)
	)
);

echo elgg_view('navigation/tabs', array('tabs' => $tabs));