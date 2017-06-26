<?php /*

Variables:

$choices (required) - Associative value => label array for options
   Or, label can be an array itself with a 'label' key and an
   'html' key for custom HTML to appear after the option
$value (required) - Selected value
$field_name - Input name

*/

// Number of items to be shown before scroll bar and search box are added
$max_visible_items = 8;
$too_many = (count($choices) > $max_visible_items);
?>
<div class="fields-select<?php if ($too_many): ?> field-resizable<?php endif ?>">
	<?php if ($too_many): ?>
		<div class="field-tools">
			<div class="filter-bar">
				<div class="filter-item filter-item__search">
					<form>
						<input type="text" placeholder="Keyword Search">
					</form>
				</div>
			</div>
		</div>
	<?php endif ?>
	<div class="field-inputs">
		<?php foreach ($choices as $key => $choice):
			$label = isset($choice['label']) ? $choice['label'] : $choice;
			$checked = ((is_bool($value) && get_bool_from_string($key) === $value) OR ( ! is_bool($value) && $key == $value)); ?>
			<label<?php if ($checked): ?> class="act"<?php endif ?> data-search="<?=strtolower($label)?>">
				<input type="radio" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=isset($attrs) ? $atts : '' ?>><?=lang($label)?>
				<?php if ( ! empty($choice['html'])): ?><?=$choice['html']?><?php endif ?>
			</label>
		<?php endforeach ?>
		<?php if (empty($choices)): ?>
			<label class="field-empty">
				No <b>[choices]</b> found.
			</label>
		<?php endif ?>
	</div>
	<?php if ($too_many): ?>
		<div class="field-inputs js-no-results hidden">
			<label class="field-empty">
				No <b>[choices]</b> found.
			</label>
		</div>
	<?php endif ?>
</div>
