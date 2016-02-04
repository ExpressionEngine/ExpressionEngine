<div class="toggle-tools">
	<a href="#" class="toggle <?=($selected) ? 'on' : 'off' ?>" data-on-value="1" data-off-value="0">
		<?=form_hidden($field_name, $selected)?>
		<span class="slider"></span>
		<span class="option"><b><?=lang('on')?></b></span>
		<span class="option"><?=lang('off')?></span>
	</a>
</div>