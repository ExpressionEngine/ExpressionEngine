<?php
	$this->extend('_templates/default-nav', array(), 'outer_box');
?>
<?php if ($license->isValid()): ?>
<div class="box mb">
	<h1><?=lang('license_and_registration')?></h1>
	<div class="txt-wrap">
		<?=ee('CP/Alert')->get('core-license')?>
		<ul class="checklist">
			<li><b><?=lang('license_no')?></b>: <?=$license->getData('license_number')?></li>
			<li><b><?=lang('owned_by')?></b>: <a href="mailto:<?=ee('Format')->make('Text', $license->getData('license_contact'))->attributeEscape()?>">
				<?=ee('Format')->make('Text', ($license->getData('license_contact_name')) ?: $license->getData('license_contact'))->attributeEscape()?>
			</a></li>
			<li class="last"><b><?=lang('site_limit')?></b>: <?=$license->getData('sites')?></li>
		</ul>
	</div>
</div>
<?php endif; ?>

<?php $this->embed('_shared/form'); ?>
