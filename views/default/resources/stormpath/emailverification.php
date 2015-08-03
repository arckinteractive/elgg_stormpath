<?php

namespace Arck\Stormpath;

error_log('pre block: ' . memory_get_usage());
try {
	$client = get_client();
	error_log('got client: ' . memory_get_usage());
	if ($client) {
		error_log('pre validation: ' . memory_get_usage());
		$account = $client->tenant->verifyEmailToken($vars['sptoken']);
		error_log('post validation: ' . memory_get_usage());
	}
	else {
		register_error(elgg_echo('email:confirm:fail'));
	}

} catch (\Exception $exc) {
	register_error($exc->getMessage());
	forward();
}

system_message(elgg_echo('email:confirm:success'));
forward();