<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('browse_avatars')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=select_avatar', array('id'=>'browse_avatar_form'), $form_hidden)?>

	<?=$this->table->generate($this->table->make_columns($avatars, 3))?>

	<?php if ($pagination != ''):?>
		<p><?=$pagination?></p>
	<?php endif;?>

	<div><?=form_submit('edit_profile', lang('choose_selected'), 'class="submit"')?></div>

	<?=form_close()?>
</div>