<?php if ($editable): ?><div class="nestable" style="position: relative" data-nestable-group="<?=$group_id?>"><?php endif ?>
	<div class="scroll-wrap pr">
		<ul class="nested-list<?php if ($editable): ?> nestable-list<?php endif ?>">
			<?php $this->embed('item'); ?>
		</ul>
	</div>
<?php if ($editable): ?></div><?php endif ?>
