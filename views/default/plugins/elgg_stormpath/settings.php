<?php

namespace Arck\Stormpath;

echo elgg_view('stormpath/admin/navigation');

echo '<div>';
echo '<label>' . elgg_echo('stormpath:settings:keyfile:upload') . '</label><br>';
echo elgg_view('input/file', array(
	'name' => 'keyfile'
));
if (api_keys_exists()) {
	echo elgg_view('output/longtext', array(
		'value' => elgg_echo('stormpath:settings:keyfile:exists'),
		'class' => 'elgg-subtext'
	));
}
echo '</div>';

echo '<div>';
echo '<label>' . elgg_echo('stormpath:settings:importance') . '</label><br>';
echo elgg_view('input/dropdown', array(
	'name' => 'params[importance]',
	'value' => $vars['entity']->importance,
	'options_values' => array(
		'sufficient' => elgg_echo('stormpath:settings:importance:option:sufficient'),
		'required' => elgg_echo('stormpath:settings:importance:option:required')
	)
));
echo '</div>';


echo '<div>';
echo '<label>' . elgg_echo('stormpath:settings:email_validate') . '</label><br>';
echo elgg_view('input/dropdown', array(
	'name' => 'params[email_validate]',
	'value' => $vars['entity']->email_validate,
	'options_values' => array(
		0 => elgg_echo('option:no'),
		1 => elgg_echo('option:yes')
	)
));
echo elgg_view('output/longtext', array(
	'value' => elgg_echo('stormpath:settings:email_validate:help'),
	'class' => 'elgg-subtext'
));
echo '</div>';


if (api_keys_exists()) {
	$client = get_client();
	$apps = $client->tenant->applications;
	$options_values = array('' => '');
	foreach ($apps as $app) {
		$options_values[$app->name] = $app->name;
	}
	
	echo '<div class="pbm">';
	echo '<label>' . elgg_echo('stormpath:settings:app') . '</label><br>';
	echo elgg_view('input/dropdown', array(
		'name' => 'params[app_name]',
		'value' => $vars['entity']->app_name,
		'options_values' => $options_values
	));
	echo '</div>';
}

?>
<script>
	$(document).ready(function() {
		$('form.elgg-form-plugins-settings-save').attr('enctype', 'multipart/form-data');
	});
</script>