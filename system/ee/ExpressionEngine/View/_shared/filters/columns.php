<button type="button" class="filter-bar__button has-sub js-dropdown-toggle" data-filter-label="<?=strtolower(lang($label))?>">
	<?=lang($label)?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</button>

<!-- Columns -->
<div class="dropdown dropdown__scroll" rev="toggle-columns">
	<div class="dropdown__header">Columns</div>
<?php foreach ($available_columns as $field_name => $field_label): ?>
	<div class="dropdown__item">
		<a style="cursor: move;"><label><input type="checkbox" <?php if (in_array($field_name, $selected_columns)): echo 'checked'; endif; ?> class="checkbox checkbox--small" name="columns[]" value="<?=$field_name?>" style="top: 3px; margin-right: 5px;"/> <?=$field_label?></label></a>
	</div>
<?php endforeach; ?>
</div>