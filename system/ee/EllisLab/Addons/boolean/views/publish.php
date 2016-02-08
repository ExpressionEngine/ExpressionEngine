<a href="#" class="toggle-btn <?=($selected) ? 'on' : 'off' ?> <?=($disabled) ? 'disabled' : ''?>">
	<?=form_hidden($field_name, $selected)?>
	<span class="slider"></span>
	<span class="option"></span>
</a>