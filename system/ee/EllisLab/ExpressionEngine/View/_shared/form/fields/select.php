<?php /*

Required variables:

$choices - Associative value => label array for options
   Or, label can be an array itself with a 'label' key and an
   'html' key for custom HTML to appear after the option
$value - Selected value
$field_name - Input name
$multi - Boolean, true for multi-select, false for single-select

Optional variables:

$filter_url - If AJAX filtering is to be used, URL to endpoint
$attrs - Attributes to be added to the input elements

*/

// Number of items to be shown before scroll bar and search box are added
$max_visible_items = 8;

// Max number of items to load before
$max_for_dom_filtering = 100;

$too_many = (count($choices) > $max_visible_items);
$needs_ajax_filtering = (isset($filter_url) && count($choices) > $max_for_dom_filtering);
?>
<div data-field-name="<?=$field_name?>" class="fields-select<?php if ($too_many): ?> field-resizable<?php endif ?><?php if ($multi): ?> js-multi-select<?php endif ?>"<?php if ($needs_ajax_filtering): ?> data-filter-url="<?=$filter_url?>"<?php endif ?>>
	<?php if ($too_many): ?>
		<div class="field-tools">
			<div class="filter-bar">
				<div class="filter-item filter-item__search">
					<input type="text" placeholder="Keyword Search">
				</div>
			</div>
		</div>
	<?php endif ?>
	<div class="field-inputs">
		<?php foreach ($choices as $key => $choice):

			$label = isset($choice['label']) ? $choice['label'] : $choice;
			$checked = ((is_bool($value) && get_bool_from_string($key) === $value)
				OR ( is_array($value) && in_array($key, $value))
				OR ( ! is_bool($value) && $key == $value));
			if ($checked) $value_label = lang($label); ?>

			<label<?php if ($checked): ?> class="act"<?php endif ?> data-search="<?=strtolower($label)?>">
				<input type="<?=$multi ? 'checkbox' : 'radio'?>" name="<?=$field_name?>" value="<?=$key?>"<?php if ($checked):?> checked="checked"<?php endif ?><?=isset($attrs) ? $atts : '' ?>><?=lang($label)?>
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
		<div class="field-input-selected <?= ($multi OR ! $value) ? 'hidden' : ''?>">
			<label>
				<span class="icon--success"></span> <span class="js-select-label"><?=$value_label?></span>
				<ul class="toolbar">
					<li class="remove"><a href=""></a></li>
				</ul>
			</label>
		</div>
	<?php endif ?>
</div>
