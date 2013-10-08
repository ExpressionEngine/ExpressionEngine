<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=member_delete')?>

	<?php foreach($damned as $member_id):?>
		<?=form_hidden('delete[]', $member_id)?>
	<?php endforeach;?>

	<p><strong><?=lang('delete_members_confirm')?></strong></p>

	<ul>
		<?php foreach ($usernames as $username): ?>
			<li><?=$username?></li>
		<?php endforeach ?>
	</ul>

	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<?php if (isset($heirs)): ?>
		<p>
			<?php if (count($damned) == 1): ?>
				<?=lang('heir_to_member_entries')?>
			<?php elseif (count($damned) > 1): ?>
				<?=lang('heir_to_members_entries')?>
			<?php endif; ?>
		</p>
		<ul>
			<li><label class="notice"><?=form_radio('heir_action', 'delete')?> <?= lang('member_delete_dont_reassign_entries') ?></label></li>
			<li><label><?=form_radio('heir_action', 'assign')?> <?= lang('member_delete_reassign_entries')?> <?= form_dropdown('heir', $heirs) ?></label></li>
		</ul>
	<?php endif; ?>

	<p><?=form_submit('delete_members', lang('delete_member'), 'class="submit"')?></p>
<?=form_close()?>