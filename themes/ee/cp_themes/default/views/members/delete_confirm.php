<?php if (isset($heirs)): ?>
	<p>
		<?php if (count($selected) == 1): ?>
			<?=lang('heir_to_member_entries')?>
		<?php elseif (count($selected) > 1): ?>
			<?=lang('heir_to_members_entries')?>
		<?php endif; ?>
	</p>
	<ul>
		<li><label class="notice"><?=form_radio('heir_action', 'delete', 'n')?> <?= lang('member_delete_dont_reassign_entries') ?></label></li>
		<li><label><?=form_radio('heir_action', 'assign', 'y')?> <?= lang('member_delete_reassign_entries')?> <?= form_dropdown('heir', $heirs, $selected) ?></label></li>
	</ul>
<?php endif; ?>
