<?php $this->extend('_templates/wrapper'); ?>

<div class="col-group snap">
	<div class="col w-16 last">
		<div class="box">
			<h1><?=lang('404_does_not_exist')?></h1>
			<div class="txt-wrap">
				<p><?=lang('404_does_not_exist_desc')?></p>
				<?php if (trim($url)): ?>
					<p><b><?=lang('url')?>:</b> <?=$url?></p>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
