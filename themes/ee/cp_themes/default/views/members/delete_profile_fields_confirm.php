<?php extend_template('default') ?>

<?=form_open($form_action, '', $form_hidden)?>

	<p><strong><?=lang('delete_field')?></strong></p>

	<p><em><?=$field_name?></em></p>

	<p><?=lang('delete_profile_field_confirmation')?></p>

	<p class="notice"><?=lang('action_can_not_be_undone')?></p>

	<p><?=form_submit('delete_members', lang('delete'), 'class="submit"')?></p>

<?=form_close()?>