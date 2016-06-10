<?php if ($editable): ?><div class="nestable" style="position: relative" data-nestable-group="<?=$group_id?>"><?php endif ?>
	<div class="scroll-wrap pr">
		<ul class="nested-list<?php if ($editable): ?> nestable-list<?php endif ?>">
			<?php $this->embed('item'); ?>
		</ul>
	</div>
<?php if ($editable): ?>

	<div class="toggle-tools">
		<b><?=$manage_toggle_label?></b>
		<?php $state = ($editing) ? 'on' : 'off' ?>
		<a href="#" class="toggle-btn <?=$state?>" data-state="<?=$state?>">
			<span class="slider"></span>
			<span class="option"></span>
		</a>
	</div>
</div><?php endif ?>
