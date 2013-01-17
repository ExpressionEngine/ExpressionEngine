<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=do_login_as_member', '', $form_hidden)?>

	<p class="notice"><?=$this->lang->line('action_can_not_be_undone')?></p>

	<p><?=$message?></p>

	<div>
		<?=form_radio(array('name'=>'return_destination','id'=>'site_homepage', 'value'=>'site'))?>
		<?=lang('site_homepage', 'site_homepage')?>
	</div>
	<?php if ($can_access_cp):?>
		<div>
			<?=form_radio(array('name'=>'return_destination','id'=>'cp', 'value'=>'cp'))?>
			<?=lang('control_panel', 'cp')?>
		</div>
	<?php endif;?>
	<div>
		<?=form_radio(array('name'=>'return_destination','id'=>'other', 'value'=>'other'))?>
		<?=lang('other', 'other')?> 
		<?=form_input(array('id'=>'other_url','name'=>'other_url','size'=>50,'value'=>$this->functions->fetch_site_index()))?>
	</div>

	<br />
	<p>
		<span class="notice"><?=lang('password_auth', 'password_auth')?></span>
		<?=form_password(array('id' => 'password_auth', 'name' => 'password_auth', 'maxlength' => 40, 'autofocus' => 'autofocus'))?>
	</p>

	<p>
		<?=form_submit('login_as_member', lang('submit'), 'class="submit"')?>
	</p>

<?=form_close()?>