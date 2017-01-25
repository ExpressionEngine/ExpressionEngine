<?php $this->extend('_templates/out'); ?>

<div class="box">
	<div class="updater-working">
		<div class="updater-load"></div>
		<h1>Updating <b><?=$site_name?></b> from <?=$current_version?> to <?=$to_version?></h1>
		<ul class="updater-steps">
			<li class="updater-step-work">Downloading Update<span>...</span></li>
		</ul>
	</div>
</div>
