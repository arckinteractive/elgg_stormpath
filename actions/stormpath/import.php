<?php

namespace Arck\Stormpath;

elgg_make_sticky_form('stormpath/import');

$subject = get_input('subject');
$message = get_input('message');

if (!$subject || !$message) {
	register_error(elgg_echo('stormpath:import:error:fields'));
	forward(REFERER);
}

elgg_set_plugin_setting('import_subject', $subject, PLUGIN_ID);
elgg_set_plugin_setting('import_message', $message, PLUGIN_ID);

elgg_register_event_handler('shutdown', 'system', __NAMESPACE__ . '\\import_to_stormpath');

system_message(elgg_echo('stormpath:import:running'));

forward(REFERER);