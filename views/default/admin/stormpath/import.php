<?php

namespace Arck\Stormpath;

echo elgg_view('stormpath/admin/navigation');

$vroom = elgg_is_active_plugin('vroom');
$dbprefix = elgg_get_config('dbprefix');

if (is_elgg18()) {
	$name_id = add_metastring('__stormpath_user');
	$value_id = add_metastring(1);
} else {
	$name_id = elgg_get_metastring_id('__stormpath_user');
	$value_id = elgg_get_metastring_id(1);
}

$count = elgg_get_entities(array(
	'type' => 'user',
	'joins' => array(
		"LEFT JOIN {$dbprefix}metadata md ON md.entity_guid = e.guid AND md.name_id = {$name_id}"
	),
	'wheres' => array(
		'md.name_id IS NULL'
	),
	'count' => true
		));

$title = elgg_echo('stormpath:import:title', array('<b>' . $count . '<b>'));

if ($count) {
	$description = elgg_view('output/longtext', array(
		'value' => elgg_echo('stormpath:import:description')
	));

	if ($vroom) {
		$description .= elgg_view('output/longtext', array(
			'value' => elgg_echo('stormpath:import:directory:instructions')
		));
		
		$description .= elgg_view_form('stormpath/import');
	}
	else {
		$description .= elgg_view('output/longtext', array(
			'value' => elgg_echo('stormpath:import:required:vroom')
		));
	}
} else {
	$description = elgg_echo('stormpath:import:description:unnecessary');
}


echo elgg_view_module('main', $title, $description);