<div class="fields-grid-tools">
	<a class="fields-grid-tool-expand" href="" title="<?=lang('grid_expand_field')?>"></a>
	<a class="fields-grid-tool-reorder" href="" title="<?=lang('grid_reorder_field')?>"></a>
	<a class="fields-grid-tool-copy" href="" title="<?=lang('grid_copy_field')?>"></a>
	<a class="fields-grid-tool-add" href="" title="<?=lang('grid_add_field')?>"></a>
	<a class="fields-grid-tool-remove" href="" title="<?=lang('grid_remove_field')?>"></a>
	<?php if (isset($col_label)):
		$col_type = $col_type ?: 'text'; ?>
		<div class="toggle-header">
			<b><?=$col_label?></b> <span class="txt-fade">(<?=strtolower($col_type)?>)</span>
		</div>
	<?php endif?>
</div>
