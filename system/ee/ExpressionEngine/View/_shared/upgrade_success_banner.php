<!-- Upgrade Success banner should ONLY display on dashboard: -->
<div class="upgrade-success-banner__wrapper" style="background-image: url('<?=URL_THEMES?>asset/img/ee-pro-promo-banner-bg@2x.png');">
	<a href="" class="upgrade-success-banner__title" target="_blank">&#127881; ExpressionEngine <strong>6.1.0</strong></a>
	<a href="" class="upgrade-success-banner__title-link" target="_blank"><i class="fas fa-clipboard"></i> Release Notes&hellip;</a>

	<!-- Change number of blurb columns via inline grid style below IF less than 3 blurbs: -->
	<div class="upgrade-success-banner__blurb-wrapper" style="grid-template-columns: repeat(2, 1fr);">
		<!--
		<a href="" class="upgrade-success-banner__blurb" target="_blank">
			<i class="fas fa-star fa-fw"></i>
			<h6>Introducting ExpressionEngine Pro</h6>
			<p>Cotidieque sea accommodare reprimique eos laudem maiorum agam scribentur indoctum aeque recteque ea delicata viderer nam. <span class="upgrade-success-banner__blurb-learn">Learn more&hellip;</span></p>
		</a>
		-->

		<a href="" class="upgrade-success-banner__blurb" target="_blank">
			<i class="fas fa-code fa-fw"></i>
			<h6>Major CLI Improvements</h6>
			<p>Cotidieque sea accommodare reprimique eos laudem maiorum agam scribentur indoctum aeque recteque ea delicata viderer nam. <span class="upgrade-success-banner__blurb-learn">Learn more&hellip;</span></p>
		</a>
		<a href="" class="upgrade-success-banner__blurb" target="_blank">
			<i class="fas fa-paragraph fa-fw"></i>
			<h6>Use Redactor for Rich Text Editing</h6>
			<p>Cotidieque sea accommodare reprimique eos laudem maiorum agam scribentur indoctum aeque recteque ea delicata viderer nam. <span class="upgrade-success-banner__blurb-learn">Learn more&hellip;</span></p>
		</a>
	</div>
	<a href="<?=ee('CP/URL')->make('homepage/upgradeSuccessBanner')->compile();?>" class="upgrade-banner-dismiss"></a>
</div>