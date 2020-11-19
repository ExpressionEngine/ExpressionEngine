<?php $this->extend('_templates/wrapper'); ?>

<div class="four-o-four">
	<div class="four-o-four__inner">
	<h1 class="four-o-four__title"><?=lang('404_does_not_exist')?></h1>
	<div class="four-o-four__body typography">
		<p><?=lang('404_does_not_exist_desc')?></p>
		<?php if (trim($url)): ?>
			<p class="four-o-four__url"><b><?=lang('url')?>:</b> <code><?=$url?></code></p>
		<?php endif; ?>
	</div>
	</div>
</div>
