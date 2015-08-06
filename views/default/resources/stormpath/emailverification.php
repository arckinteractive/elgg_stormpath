<?php

namespace Arck\Stormpath;

try {
	$client = get_client();
	if ($client) {
		$account = $client->tenant->verifyEmailToken($vars['sptoken']);
		system_message(elgg_echo('email:confirm:success'));
	}
	else {
		register_error(elgg_echo('email:confirm:fail'));
	}

} catch (\Exception $exc) {
	register_error($exc->getMessage());
	forward();
}

forward();