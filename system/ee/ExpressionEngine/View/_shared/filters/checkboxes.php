<div class="filter-search-bar__item <?=($value ? 'in-use' : '')?>">
	<button type="button" class="filter-bar__button has-sub js-dropdown-toggle button button--default button--small" data-filter-label="columns" title="<?=$short_label?>">
		<?=$short_label?>
		<?php if ($value): ?>
		<span class="faded">(<?=htmlentities(implode(', ', $display_value), ENT_QUOTES, 'UTF-8')?>)</span>
		<?php endif; ?>
	</button>

	<!-- Columns -->
	<div class="dropdown dropdown__scroll" rev="toggle-columns">
		<div class="dropdown__header"><?=$label?></div>
        <input type="hidden" name="<?=$name?>" value="" />
	<?php foreach ($options as $field_value => $field_label): ?>
		<div class="dropdown__item">
        <a><label><input type="checkbox" <?php if (in_array($field_value, $value)): echo 'checked'; endif; ?> class="checkbox checkbox--small" name="<?=$name?>[]" value="<?=$field_value?>" style="top: 1px; margin-right: 5px;"/> <?=$field_label?></label></a>
		</div>
	<?php endforeach; ?>
	</div>
</div>
