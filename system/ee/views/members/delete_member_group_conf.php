<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=delete_member_group'.AMP.'group_id='.$group_id, '', $form_hidden)?>

	<p><strong><?=$this->lang->line('delete_member_group_confirm')?></strong></p>
	
	<p><em><?=$group_title?></em></p>
	
	<p class="notice"><?=$this->lang->line('action_can_not_be_undone')?></p>

	<?php if ($member_count > 0):?>
		<p><?=str_replace('%x', $member_count , $this->lang->line('member_assignment_warning'))?>
		<p><?=form_dropdown('new_group_id', $new_group_id)?></p>
	<?php endif;?>

	<p><?=form_submit('delete', $this->lang->line('delete'), 'class="submit"')?></p>

<?=form_close()?>