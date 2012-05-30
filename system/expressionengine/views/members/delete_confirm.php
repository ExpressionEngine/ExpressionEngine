<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=member_delete')?>

	<?php foreach($damned as $member_id):?>
		<?=form_hidden('delete[]', $member_id)?>
	<?php endforeach;?>

	<p><strong><?=lang('delete_members_confirm')?></strong></p>

	<?=$user_name?>

	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<?php if (isset($heirs)): ?>
		<p>
			<?php if (count($heirs) == 1): ?>
				<?=lang('heir_to_member_entries', 'heir')?>
			<?php elseif (count($heirs) > 1): ?>
				<?=lang('heir_to_members_entries', 'heir')?>
			<?php endif; ?>
			<?=BR.form_dropdown('heir', $heirs)?>
		</p>
	<?php endif; ?>

	<p><?=form_submit('delete_members', lang('delete'), 'class="submit"')?></p>

<?=form_close()?>