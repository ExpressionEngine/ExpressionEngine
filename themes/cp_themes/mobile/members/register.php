<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<?=form_open('C=members'.AMP.'M=new_member_form');?>
	<ul>
		<li>
			<?=form_error('username')?>
			<?=form_label(lang('username'), 'username')?><br />
			<?=form_input(array('id'=>'username', 'name'=>'username', 'value'=>set_value('username'), 'placeholder' => lang('username')))?>
		</li>
		<li>
			<?=form_error('password')?>
			<?=form_label(lang('password'), 'password')?><br />
			<?=form_password(array('id'=>'password','name'=>'password','class'=>'long_field','value'=>set_value('password'), 'placeholder' => lang('password')))?>
		</li>
		<li>
			<?=form_error('password_confirm')?>
			<?=form_label(lang('password_confirm'), 'password_confirm')?><br />
			<?=form_password(array('id'=>'password_confirm','name'=>'password_confirm','value'=>set_value('password_confirm'), 'placeholder' => lang('password_confirm')))?>
		</li>		
		<li>
			<?=form_error('screen_name')?>
			<?=form_label(lang('screen_name'), 'screen_name')?><br />
			<?=form_input(array('id'=>'screen_name','name'=>'screen_name','class'=>'long_field','value'=>set_value('screen_name'), 'placeholder' => lang('screen_name')))?>
		</li>
		
		<li><?=form_error('email')?>
			<?=form_label(lang('email'), 'email')?><br />
			<?=form_input(array('id'=>'email','name'=>'email','class'=>'long_field','value'=>set_value('email'), 'placeholder' => lang('email')))?>	
		</li>
		<?php if ($this->cp->allowed_group('can_admin_mbr_groups')):?>			
			<li>
				<?=form_error('group_id')?>
				<?=form_label(lang('member_group_assignment'), 'group_id')?><br />
				<?=form_dropdown('group_id', $member_groups, set_value('group_id', 5), 'id="group_id"')?>
			</li>
		<?php endif;?>	
	</ul>
	<?=form_submit('members', lang('register_member'), 'class="whiteButton"')?>
	<?=form_close()?>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file register.php */
/* Location: ./themes/cp_themes/mobile/members/register.php */