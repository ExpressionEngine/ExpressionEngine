<?php if (count($groups) > 0):?>
	<p><?=lang('member_assignment_warning')?>
	<p><?=form_dropdown('replacement', $new_groups)?></p>
<?php endif;?>
