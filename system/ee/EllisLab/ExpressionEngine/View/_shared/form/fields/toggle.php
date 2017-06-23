<a href="#" class="toggle-btn <?=(get_bool_from_string($value)) ? 'on' : 'off' ?> <?=(isset($yes_no) && $yes_no) ? 'yes_no' : ''?> <?=($disabled) ? 'disabled' : ''?>" data-toggle-for="<?=$field_name?>">
	<?=form_hidden($field_name, $value)?>
	<span class="slider"></span>
	<span class="option"></span>
</a>
