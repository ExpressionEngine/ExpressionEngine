<?php
if (isset($yes_no) && $yes_no)
{
	$value = get_bool_from_string($value) ? 'y' : 'n';
}
$class = isset($class) ? $class : '';
$on_off = (get_bool_from_string($value)) ? 'on' : 'off';
$true_false = (get_bool_from_string($value)) ? 'true' : 'false';
?>
<a href="#" class="toggle-btn <?=$on_off?> <?=(isset($yes_no) && $yes_no) ? 'yes_no' : ''?> <?=($disabled) ? 'disabled' : ''?> <?=$class?>" data-toggle-for="<?=$field_name?>" data-state="<?=$on_off?>" role="switch" aria-checked="<?=$true_false?>" alt="<?=$on_off?>">
	<input type="hidden" name="<?=$field_name?>" value="<?=form_prep($value, $field_name)?>"<?php if (isset($group_toggle)): ?> data-group-toggle='<?=json_encode($group_toggle)?>'<?php endif ?>>
	<span class="slider"></span>
	<span class="option"></span>
</a>
