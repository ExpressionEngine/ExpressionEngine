<ul id="menu" class="side_navigation">
	<li><span class="top_menu"><?=lang('personal_settings')?></span>
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
			<?php if ($allow_localization):?><li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=localization'.AMP.'id='.$id?>"><?=lang('localization')?></a></li><?php endif?>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_preferences'.AMP.'id='.$id?>"><?=lang('edit_preferences')?></a></li>
		</ul>
	</li>
	<li><span class="top_menu"><?=lang('utilities')?></span>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=subscriptions'.AMP.'id='.$id?>"><?=lang('edit_subscriptions')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=ignore_list'.AMP.'id='.$id?>"><?=lang('ignore_list')?></a></li>
		</ul>
	</li>
	<?php if (FALSE AND count($private_messaging_menu) > 0):?>
	<li><span class="top_menu"><?=lang('private_messages')?></span>
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
	</li>
	<?php endif;?>
	<li><span class="top_menu"><?=lang('customize_cp')?></span>
		<ul>
<?php //	<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=cp_homepage'.AMP.'id='.$id? >"><?=lang('cp_homepage')? ></a></li>?>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=cp_theme'.AMP.'id='.$id?>"><?=lang('myaccount_cp_theme')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=main_menu_manager'.AMP.'id='.$id?>"><?=lang('main_menu_manager')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=quicklinks'.AMP.'id='.$id?>"><?=lang('quicklinks_manager')?></a></li>
		</ul>
	</li>
	<li><span class="top_menu"><?=lang('channel_preferences')?></span>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=ping_servers'.AMP.'id='.$id?>"><?=lang('your_ping_servers')?></a></li>
			<li id="myaccountHtmlButtonsLink" style="display: none;"><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=html_buttons'.AMP.'id='.$id?>"><?=lang('your_html_buttons')?></a></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=bookmarklet'.AMP.'id='.$id?>"><?=lang('bookmarklet')?></a></li>
		</ul>
	</li>

	<?php if ($can_admin_members):?>
	<li><span class="top_menu"><?=lang('administrative_options')?></span>
		<ul>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=member_preferences'.AMP.'id='.$id?>"><?=lang('member_preferences')?></a></li>
			<?php if ($member_email):?><li><a href="<?=BASE.AMP.'C=tools_communicate'.AMP.'email_member='.$id?>"><?=lang('member_email')?></a></li><?php endif?>
			<?php if ($resend_activation_email):?><li><a href="<?=BASE.AMP.'C=members'.AMP.'M=resend_activation_emails'.AMP.'mid='.$id?>"><?=lang('resend_activation_email')?></a></li><?php endif?>
			<?php if ($login_as_member):?><li><a href="<?=BASE.AMP.'C=members'.AMP.'M=login_as_member'.AMP.'mid='.$id?>"><?=lang('login_as_member')?></a></li><?php endif?>
			<?php if ($can_delete_members):?><li><a href="<?=BASE.AMP.'C=members'.AMP.'M=member_delete_confirm'.AMP.'mid='.$id?>"><?=lang('delete')?></a></li><?php endif?>
		</ul>
	</li>
	<?php endif;?>
</ul>