<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=homepage" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>

		<h3 class="pad"><?=lang('personal_settings')?></h3>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_profile'.AMP.'id='.$id?>"><?=lang('edit_profile')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_signature'.AMP.'id='.$id?>"><?=lang('edit_signature')?></a></li>
		<?php if ($this->config->item('enable_avatars') == 'y'):?>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_avatar'.AMP.'id='.$id?>"><?=lang('edit_avatar')?></a></li>
		<?php endif;?>
		<?php if ($this->config->item('enable_photos') == 'y'):?>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_photo'.AMP.'id='.$id?>"><?=lang('edit_photo')?></a></li>
		<?php endif;?>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=email_settings'.AMP.'id='.$id?>"><?=lang('email_settings')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=username_password'.AMP.'id='.$id?>"><?=lang('username_and_password')?></a></li>
		<?php if ($allow_localization):?>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=localization'.AMP.'id='.$id?>"><?=lang('localization')?></a></li>
		<?php endif?>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_preferences'.AMP.'id='.$id?>"><?=lang('edit_preferences')?></a></li>
		</ul>

		<h3 class="pad"><?=lang('utilities')?></h3>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=subscriptions'.AMP.'id='.$id?>"><?=lang('edit_subscriptions')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=ignore_list'.AMP.'id='.$id?>"><?=lang('ignore_list')?></a></li>
		</ul>

		<?php if (FALSE AND count($private_messaging_menu) > 0):?>
		<h3 class="pad"><?=lang('private_messages')?></h3>
		<ul>
		<?php foreach ($private_messaging_menu['single_items'] as $item => $value):?>
			<li><a href="<?=$value['link']?>"><?=lang($item)?></a></li>
		<?php endforeach;?>
		<?php foreach ($private_messaging_menu['repeat_items'] as $item):?>
			<?php foreach ($item as $sub_item):?>
				<li><a href="<?=$sub_item['link']?>"><?=$sub_item['text']?></a></li>
			<?php endforeach;?>
		<?php endforeach;?>
		</ul>
		<?php endif;?>

		<h3 class="pad"><?=lang('customize_cp')?></h3>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=cp_theme'.AMP.'id='.$id?>"><?=lang('myaccount_cp_theme')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=main_menu_manager'.AMP.'id='.$id?>"><?=lang('main_menu_manager')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=quicklinks'.AMP.'id='.$id?>"><?=lang('quicklinks_manager')?></a></li>
		</ul>
		
		<h3 class="pad"><?=lang('channel_preferences')?></h3>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=ping_servers'.AMP.'id='.$id?>"><?=lang('your_ping_servers')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=bookmarklet'.AMP.'id='.$id?>"><?=lang('bookmarklet')?></a></li>
		</ul>
		<?php if ($can_admin_members):?>
		<h3 class="pad"><?=lang('administrative_options')?></h3>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=member_preferences'.AMP.'id='.$id?>"><?=lang('member_preferences')?></a></li>
		<?php if ($member_email):?>
			<li><a href="<?=BASE.AMP.'C=tools_communicate'.AMP.'email_member='.$id?>"><?=lang('member_email')?></a></li>
		<?php endif?>
		<?php if ($resend_activation_email):?>
			<li><a href="<?=BASE.AMP.'C=members'.AMP.'M=resend_activation_emails'.AMP.'mid='.$id?>"><?=lang('resend_activation_email')?></a></li>
		<?php endif?>
		<?php if ($login_as_member):?>
			<li><a href="<?=BASE.AMP.'C=members'.AMP.'M=login_as_member'.AMP.'mid='.$id?>"><?=lang('login_as_member')?></a></li>
		<?php endif?>
		<?php if ($can_delete_members):?>
			<li><a href="<?=BASE.AMP.'C=members'.AMP.'M=member_delete_confirm'.AMP.'mid='.$id?>"><?=lang('delete')?></a></li>
		<?php endif?>
		</ul>
		<?php endif;?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/account/index.php */