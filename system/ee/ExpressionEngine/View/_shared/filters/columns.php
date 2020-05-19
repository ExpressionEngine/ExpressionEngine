<button type="button" class="filter-bar__button has-sub js-dropdown-toggle" data-filter-label="<?=strtolower(lang($label))?>">
	<?=lang($label)?>
	<?php if ($value): ?>
	<span class="faded">(<?=htmlentities($value, ENT_QUOTES, 'UTF-8')?>)</span>
	<?php endif; ?>
</button>

<!-- Columns -->
<div class="dropdown" rev="toggle-columns">
	<div class="dropdown__search d-flex">
		<div class="filter-bar flex-grow" style="margin-left: 5px;">
			<div class="filter-bar__item">
				<select id="columns_view_choose" name="choose_view">
					<option value="">Choose View</option>
					<option value="NEW">- Save as New View -</option>
					<?php foreach($available_views as $available_view): ?>
						<option value="<?=$available_view['url']?>" <?=($selected_view == $available_view['view_id'] ? 'selected="selected"' : '')?>><?=$available_view['name']?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div id="columns_view_new" style="display:none;">
				<div class="filter-bar__item">
					<div class="search-input">
						<input type="text" class="search-input__input" placeholder="View Name">
					</div>
				</div>
				<div class="filter-bar__item">
					<button type="button" class="button button--action filter-item__link--save">Create View</button>
				</div>
			</div>
			<div id="columns_view_options" class="filter-bar__item" style="display:none;">
				<nobr>
					<button type="button" id="columns_view_switch" class="button button--primary">Switch to View</button>
					<button type="button" class="button button--secondary-alt filter-item__link--save">Update View</button>
				</nobr>
			</div>
		</div>
	</div>
	<div class="dropdown__header">Columns</div>
<?php foreach ($available_columns as $field_name => $field_label): ?>
	<div class="dropdown__item">
		<a style="cursor: move;"><label><input type="checkbox" <?php if (in_array($field_name, $selected_columns)): echo 'checked'; endif; ?> class="checkbox checkbox--small" name="columns[]" value="<?=$field_name?>" style="top: 3px; margin-right: 5px;"/> <?=$field_label?></label></a>
	</div>
<?php endforeach; ?>
</div>