<?php
/**
 * Elgg login form
 *
 * @package Elgg
 * @subpackage Core
 */
?>

<div class="row">
	<div class="small-12 medium-6 columns">
		<div class="nl-input-login">
			<?php
			echo elgg_view('output/url', array(
				'text' => elgg_echo('stormpath:login:sso'),
				'href' => 'stormpath/login',
				'class' => 'elgg-button elgg-button-action'
			));
			?>
		</div>
	</div>
</div>