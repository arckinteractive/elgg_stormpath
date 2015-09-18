<?php

namespace Arck\Stormpath;

$saved_subject = elgg_get_plugin_setting('import_subject', PLUGIN_ID);
$sticky_subject = elgg_get_sticky_value('stormpath/import', 'subject');

echo '<div class="pas">';
echo '<label>' . elgg_echo('stormpath:import:label:subject') . '<label>';
echo elgg_view('input/text', array(
	'name' => 'subject',
	'value' => $sticky_subject ? $sticky_subject : ($saved_subject ? $saved_subject : elgg_echo('stormpath:import:subject'))
));
echo '</div>';

$saved_message = elgg_get_plugin_setting('import_message', PLUGIN_ID);
$sticky_message = elgg_get_sticky_value('stormpath/import', 'message');

echo '<div class="pas">';
echo '<label>' . elgg_echo('stormpath:import:label:message') . '<label>';
echo elgg_view('input/plaintext', array(
	'name' => 'message',
	'value' => $sticky_message ? $sticky_message : ($saved_subject ? $saved_subject : elgg_echo('stormpath:import:message'))
));
echo elgg_view('output/longtext', array(
	'value' => elgg_echo('stormpath:import:help:message'),
	'class' => 'elgg-subtext'
));
echo '</div>';


echo elgg_view('input/submit', array('value' => elgg_echo('submit')));