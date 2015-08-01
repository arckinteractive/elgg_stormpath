<?php

echo '<div class="pvs">';
echo '<label>' . elgg_echo('user:password:label') . '</label>';
echo elgg_view('input/password', array(
	'name' => 'password'
));
echo '</div>';
echo '<div class="pvs">';
echo '<label>' . elgg_echo('user:password2:label') . '</label>';
echo elgg_view('input/password', array(
	'name' => 'password2'
));
echo '</div>';

echo '<div class="elgg-foot">';
echo elgg_view('input/hidden', array('name' => 'sptoken', 'value' => $vars['sptoken']));
echo elgg_view('input/submit', array('value' => elgg_echo('submit')));
echo '</div>';