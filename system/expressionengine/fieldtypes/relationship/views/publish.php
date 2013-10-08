<?php

$scroll_class = count($entries) ? 'force-scroll' : 'empty';

// underscore.js template string
$active_template = '
	<li><span class="reorder-handle">&nbsp;</span>
	<%= title %>
	<span class="remove-item">&times;</span></li>
';
?>

<div class="relationship" id="relationship-<?=$field_name?>">

	<!-- Active Pane -->
	<div class="multiselect-active force-scroll <?=$field_name?>-active" data-template="<?=form_prep($active_template)?>">
		<ul></ul>
	</div>

	<!-- Filter Textbox-->
	<div class="multiselect-filter js_show">
		<?=form_input('', '', 'class="'.$field_name.'-filter"')?>
	</div>

	<!-- Selection Pane -->
	<div class="multiselect <?=$field_name?> <?=$scroll_class?>">
		<ul>
		<?php foreach ($entries as $row):?>
			<?php $checked = in_array($row['entry_id'], $selected); ?>
			<?php $sort = $checked ? $order[$row['entry_id']] : 0; ?>

			<li <?=($checked ? 'class="selected"' : '')?>>
				<label>
					<?=form_input($field_name.'[sort][]', $sort, 'class="js_hide"')?>
					<?=form_checkbox($field_name.'[data][]', $row['entry_id'], $checked, 'class="js_hide"')?>
					<?=$row['title']?>
				</label>
			</li>
		<?php endforeach;?>

		<?php if ( ! count($entries)): ?>
			<li><?=lang('rel_ft_no_entries')?></li>
		<?php endif; ?>
		</ul>
	</div>

</div>
