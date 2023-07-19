<div class="sidebar__link sidebar__link--parent <?=$class?>" data-<?=$key?>="<?=$value?>">
	<a href="<?=$url?>"<?php if ($external) {
    echo ' rel="external"';
}?>>
		<?php if (!empty($icon)): ?>
			<i class="fal fa-<?=$icon?>"></i>
		<?php else: ?>
			<i class="fal fa-folder"></i>
		<?php endif; ?>
		<?=$text?>
	</a>
	<?php if ($edit || $remove): ?>
	<div class="button-toolbar toolbar">
		<div class="button-group button-group-xsmall">
			<?php if ($edit): ?>
			<a href="<?=$edit_url?>" title="<?=lang('edit')?>" class="edit button button--default"><span class="hidden"><?=lang('edit')?></span></a>
			<?php endif; ?>
			<?php if ($remove): ?>
			<a class="remove m-link button button--default" rel="modal-confirm-<?=$modal_name?>" href="" title="<?=lang('remove')?>" data-confirm="<?=$confirm?>" data-<?=$key?>="<?=$value?>"><span class="hidden"><?=lang('remove')?></span></a>
			<?php endif; ?>
		</div>
	</div>
	<?php endif; ?>
</div>
