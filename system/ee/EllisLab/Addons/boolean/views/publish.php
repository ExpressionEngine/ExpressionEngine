<div class="toggle-tools">
	<a href="#" class="toggle <?=($selected) ? 'on' : 'off' ?>" data-on-value="y" data-off-value="n">
		<?=form_hidden($field_name, ($selected) ? 'y' : 'n')?>
		<span class="slider"></span>
		<?php if ($options == 'of'): ?>
		<span class="option"><b><?=lang('on')?></b></span>
		<span class="option"><?=lang('off')?></span>
		<?php else: ?>
		<span class="option"><b><?=lang('yes')?></b></span>
		<span class="option"><?=lang('no')?></span>
		<?php endif; ?>
	</a>
</div>