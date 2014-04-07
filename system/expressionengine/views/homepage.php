<?php extend_template('wrapper'); ?>

<?php enabled('ee_message') && $this->view('_shared/message');?>

<?php if ($message OR isset($new_checksums)):?>
	<div id="ee_important_message" class="<?=( ! $info_message_open) ? 'closed' : 'open'?>">
		<div class="contents" id="ee_homepage_notice">
			<div class="heading">
				<h2><span class="ee_notice_icon"></span><?=lang('important_messages')?><span class="msg_open_close">Ignore Button</span></h2>
			</div>
			<div class="pageContents open" id="noticeContents">
				<?php if ( ! empty($message)):
					foreach ($message as $value): ?>
						<p><?=$value?></p>
						<?php if ($value != end($message)): ?>
							<hr>
						<?php endif;
					endforeach;
				endif; ?>

				<?php // Bootstrap Checksum Failure Notice?>
				<?php if (isset($new_checksums)):?>
					<ul id="checksumFailure">
						<li><?=lang('checksum_changed_warning')?>
							<ul>
								<?php foreach($new_checksums as $path): ?>
								<li><?=$path; ?></li>
								<?php endforeach; ?>
							</ul>
					<?php if ($this->session->userdata('group_id') == 1): ?>
						</li>
					</ul>
					<a class="submit" href="<?=BASE.AMP.'C=homepage'.AMP.'M=accept_checksums'?>"><?=lang('checksum_changed_accept')?></a>
					<?php endif; ?>
				<?php endif; ?>
				<div class="clear"></div>
			</div>
		</div>
	</div>
<?php endif;?>

<?php if ($can_access_content == TRUE): ?>

	<div class="contentMenu create">
		<div class="heading"><h2><?=lang('create')?></h2></div>
		<ul class="homeBlocks">
		<?php if ($this->session->userdata['can_access_content'] == 'y'):?>
			<li class="item"><a href="<?=BASE.AMP.'C=content_publish'?>" class="submenu accordion"><?=lang('entry')?></a>
				<ul class="submenu" style="display: none;">
					<?php if (! isset($cp_menu_items['content']['publish'])):?>
						<li><p><?=lang('no_channels_exist'); ?></p></li>
					<?php elseif (! is_array($cp_menu_items['content']['publish'])):?>
						<li><a href="<?=$cp_menu_items['content']['publish']?>" title="<?=lang('nav_publish')?>"><?=lang('nav_publish')?></a></li>
					<?php else:?>
						<li><p><?=$instructions?></p></li>
					<?php foreach($cp_menu_items['content']['publish'] as $channel_name => $uri):?>
							<li><a href="<?=$uri?>" title="<?=$channel_name?>"><?=$channel_name?></a></li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</li>
		<?php endif;?>
		<?php if ($this->session->userdata['can_admin_templates'] == 'y'):?>
			<li class="item"><a href="#" class="submenu accordion"><?=lang('template')?></a>
				<ul class="submenu" style="display:none">
					<?php if (count($cp_menu_items['design']['templates']['edit_templates']) <= 1):?>
						<li><p><?=$no_templates?></p></li>
					<?php else:?>
						<?php foreach ($cp_menu_items['design']['templates']['edit_templates'] as $template_group => $url):?>
							<?php if (is_array($url)):?>
							<li><a href="<?=$url[lang('nav_create_template')]?>"><?=lang($template_group)?></a></li>
							<?php endif;?>
						<?php endforeach;?>
					<?php endif;?>
				</ul>
			</li>
			<li class="group"><a href="<?=cp_url('design/new_template_group')?>"><?=lang('template_group')?></a></li>
		<?php endif;?>
		<?php if ($show_page_option):?>
			<li class="item"><a href="<?=cp_url('content_publish')?>"><?=lang('page')?></a></li>
		<?php endif;?>
		<?php if ($this->session->userdata['can_admin_channels'] == 'y'):?>
			<li class="group"><a href="<?=cp_url('admin_content/channel_add')?>"><?=lang('channel')?></a></li>
		<?php endif;?>
		<?php if ($this->config->item('multiple_sites_enabled') == 'y' && $this->cp->allowed_group('can_admin_sites')):?>
			<li class="site"><a href="<?=cp_url('sites/manage_sites')?>"><?=lang('site')?></a></li>
		<?php endif;?>
		</ul>

	</div>
<?php endif; ?>
<?php if ($can_access_modify == TRUE): ?>
	<div class="contentMenu modify">
		<div class="heading"><h2><?=lang('modify')?> <span class="subtext"><?=lang('or_delete')?></span></h2></div>
		<ul class="homeBlocks">
			<li class="item"><a href="<?=BASE.AMP.'C=content_edit'?>"><?=lang('entry')?></a></li>
			<?php if ($can_access_templates): ?>
			<li class="item"><a href="<?=BASE.AMP.'C=design'.AMP.'M=manager'?>"><?=lang('template')?></a></li>
			<?php endif; ?>
			<?php if ($this->session->userdata['can_admin_templates'] == 'y'):?>
			<li class="group"><a href="<?=BASE.AMP.'C=design'.AMP.'M=edit_template_group'?>" class="submenu"><?=lang('template_group')?></a></li>
			<?php endif;?>
			<?php if ($show_page_option):?>
			<li class="group"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages'?>"><?=lang('page')?></a></li>
		<?php endif;?>
		<?php if ($cp_recent_ids['entry']): ?>
			<li class="group"><a href="<?=BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'channel_id='.$cp_recent_ids['entry']['channel_id'].AMP.'entry_id='.$cp_recent_ids['entry']['entry_id']?>"><?=lang('most_recent_entry')?></a></li>
		<?php endif;?>
		<?php if ($comments_installed && $can_moderate_comments): ?>
			<li class="item"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'status=p'?>"><span><?=$comment_validation_count?></span><?=lang('total_validating_comments')?></a></li>
		<?php endif;?>
		</ul>
	</div>
<?php endif; ?>
	<div class="contentMenu view">
		<div class="heading"><h2><?=lang('view')?></h2></div>
		<ul class="homeBlocks">
			<li class="site"><?=anchor($this->config->item('site_url').$this->config->item('index_page').'?URL='.$this->config->item('site_url').$this->config->item('index_page'), lang('site'))?></li>
			<?php if ($comments_installed && $can_moderate_comments):?>
			<li class="item"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'?>"><?=lang('recent_comments')?></a></li>
			<?php endif;?>

			<li class="item"><a href="<?=BASE.AMP.'C=content_edit'.AMP.'M=show_recent_entries'.AMP.'count=10'?>" class="submenu accordion"><?=lang('recent_entries')?></a>
				<ul class="submenu" style="display: none;">
					<?php if (count($recent_entries) == 0):?>
						<li><p><?=lang('no_entries'); ?></p></li>
					<?php else:?>
						<?php foreach($recent_entries as $entry_link):?>
							<li><?=$entry_link?></li>
						<?php endforeach; ?>
					<?php endif; ?>
				</ul>
			</li>

			<li class="resource"><a rel="external" href="<?=config_item('doc_url')?>"><?=lang('user_guide')?></a></li>
		</ul>
	</div>

	<div class="clear_left"></div>

<?php

/* End of file homepage.php */
/* Location: ./themes/cp_themes/default/homepage.php */