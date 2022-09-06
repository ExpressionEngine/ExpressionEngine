<?php if ((empty(ee()->uri->segment(2)) || ee()->uri->segment(2) == 'homepage') && ee()->session->getMember()->dismissed_banner!='y') : ?>
<div class="upgrade-success-banner__wrapper" style="background-image: url('<?=URL_THEMES?>asset/img/ee-pro-promo-banner-bg@2x.png');">
	<a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title" target="_blank">&#127881; ExpressionEngine <strong><?=APP_VER?></strong></a>
	<a href="<?=DOC_URL . 'installation/changelog.html#version-' . str_replace('.', '', APP_VER)?>" class="upgrade-success-banner__title-link" target="_blank"><i class="fas fa-clipboard"></i> Release Notes&hellip;</a>

	<!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
	<div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(3, 1fr);">

		<a href="https://expressionengine.com/blog/expressionengine-7-official-release" class="upgrade-success-banner__blurb" target="_blank">
			<i class="fal fa-newspaper fa-fw"></i>
			<h6>Welcome to ExpressionEngine 7</h6>
			<p>ExpressionEngine 7 is one of the largest ExpressionEngine releases theres ever been. <span class="upgrade-success-banner__blurb-learn">Learn more about what's changed&hellip;</span></p>
		</a>
		<a href="https://expressionengine.com/blog/expressionengine-7-official-release" class="upgrade-success-banner__blurb" target="_blank">
			<i class="fal fa-archive fa-fw"></i>
			<h6>New File Manager</h6>
			<p>Version 7 comes with a new, robust and powerful file manager. Now allowing you to store files just about anywhere.</p>
		</a>
		<a href="https://expressionengine.com/blog/expressionengine-7.1" class="upgrade-success-banner__blurb" target="_blank">
			<i class="fal fa-brain-circuit fa-fw"></i>
			<h6>Now Including Structure</h6>
			<p>Structure has long been considered by many to be the premier navigation manager in ExpressionEngine. In version 7.1 we’re happy to share that it’s now included in the core!</p>
		</a>
	</div>
	<a href="<?=ee('CP/URL')->make('homepage/dismiss-banner')->compile();?>" class="banner-dismiss"></a>
</div>
<?php endif; ?>
