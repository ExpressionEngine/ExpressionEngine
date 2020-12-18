<?php if (count($roles) > 0):?>
	<p><?=lang('member_assignment_warning')?>
	<p><?=form_dropdown('replacement', $new_roles, ee()->config->item('default_primary_role'))?></p>
<?php endif;?>
