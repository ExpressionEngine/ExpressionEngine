<?php
if (isset($yes_no) && $yes_no)
{
	$value = get_bool_from_string($value) ? 'y' : 'n';
}
$class = isset($class) ? $class : '';
?>
<a href="#" class="toggle-btn <?=(get_bool_from_string($value)) ? 'on' : 'off' ?> <?=(isset($yes_no) && $yes_no) ? 'yes_no' : ''?> <?=($disabled) ? 'disabled' : ''?> <?=$class?>" data-toggle-for="<?=$field_name?>">
	<input type="hidden" name="<?=$field_name?>" value="<?=form_prep($value, $field_name)?>"<?php if (isset($group_toggle)): ?> data-group-toggle='<?=json_encode($group_toggle)?>'<?php endif ?>>
	<span class="slider"></span>
	<span class="option"></span>
</a>
