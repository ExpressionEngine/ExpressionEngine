<!doctype html>
<html>
	<head>
		<?=ee()->view->head_title($cp_page_title)?>
		<meta http-equiv="content-type" content="text/html; charset=utf-8" lang="en-us" dir="ltr">
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<?php if (isset($meta_refresh)): ?>
		<meta http-equiv='refresh' content='<?=$meta_refresh['rate']?>; url=<?=$meta_refresh['url']?>'>
		<?php endif;?>

		<?=ee()->view->head_link('css/common.min.css'); ?>
		<?php if (ee()->extensions->active_hook('cp_css_end') === TRUE):?>
		<link rel="stylesheet" href="<?=ee('CP/URL', 'css/cp_global_ext')?>" type="text/css" />
		<?php endif;?>
		<!-- <link href="touch-icon-iphone.png" rel="apple-touch-icon-precomposed" sizes="114x114">
		<link href="touch-icon-ipad.png" rel="apple-touch-icon-precomposed" sizes="144x144"> -->

		<?php
		foreach (ee()->cp->get_head() as $item)
		{
			echo $item."\n";
		}
		?>
	</head>
	<body id="top">
		<?=ee('CP/Alert')->getAllBanners()?>
		<div class="nav-global-wrap">
			<nav class="nav-global">
				<div class="nav-global-site">
					<?php if ($cp_homepage_url->path == 'homepage'): ?>
					<a class="nav-home" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_homepage')?>"><i class="icon-home"></i><span class="nav-txt-collapse"><?=lang('nav_homepage')?></span></a>
					<?php else: ?>
					<a class="nav-home" href="<?=$cp_homepage_url?>" title="<?=lang('nav_homepage')?>"><i class="icon-home"></i><span class="nav-txt-collapse"><?=lang('nav_homepage')?></span></a>
					<a class="nav-overview" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_overview')?>"><i class="icon-dashboard"></i><span class="nav-txt-collapse"><?=lang('nav_overview')?></span></a>
					<?php endif; ?>
					<?php if (ee()->config->item('multiple_sites_enabled') === 'y' && (count($cp_main_menu['sites']) > 0 || ee()->cp->allowed_group('can_admin_sites'))): ?>
					<div class="nav-sites">
						<a class="nav-has-sub" href=""><?=ee()->config->item('site_name')?></a>
						<a class="nav-view" href="<?=ee()->config->item('base_url').ee()->config->item('site_index')?>" rel="external"><i class="icon-view"></i><span class="nav-txt-collapse"><?=lang('view')?></span></a>
						<ul class="nav-sub-menu">
							<?php foreach ($cp_main_menu['sites'] as $site_name => $link): ?>
								<li><a href="<?=$link?>"><?=$site_name?></a></li>
							<?php endforeach ?>
							<?php if (ee()->cp->allowed_group('can_admin_sites')
									  && ee('License')->getEELicense()->canAddSites(ee('Model')->get('Site')->count())): ?>
								<li><a class="nav-add" href="<?=ee('CP/URL', 'msm/create')?>"><i class="icon-add"></i><?=lang('new_site')?></a></li>
							<?php endif ?>
						</ul>
					</div>
					<?php elseif ( ! ($site_name = ee()->config->item('site_name')) OR empty($site_name)): ?>
						<a class="nav-no-name" href="<?=ee('CP/URL', 'settings')?>" class="no-name"><i class="icon-settings"></i><?=lang('name_your_site')?></a>
					<?php else: ?>
						<a class="nav-site" href="<?=ee()->config->item('site_url')?>" rel="external"><?=ee()->config->item('site_name')?></a>
					<?php endif ?>
				</div>
				<div class="nav-global-user">
					<a class="nav-logout" href="<?=ee('CP/URL', 'login/logout')?>"><i class="icon-logout"></i><span class="nav-txt-collapse"><?=lang('log_out')?></span></a>
					<div class="nav-user">
						<a class="nav-has-sub" href=""><i class="icon-user"></i><span class="nav-txt-collapse"><?=$cp_screen_name?></span></a>
						<ul class="nav-sub-menu">
							<li><a href="<?=ee('CP/URL')->make('members/profile', array('id' => ee()->session->userdata('member_id')))?>"><?=lang('my_profile')?></a></li>
							<?php foreach($cp_quicklinks as $link): ?>
							<li><a href="<?=$link['link']?>"><?=htmlentities($link['title'], ENT_QUOTES, 'UTF-8')?></a></li>
							<?php endforeach ?>
							<li><a class="nav-add" href="<?=ee('CP/URL')->make('members/profile/quicklinks/create', array('id' => ee()->session->userdata('member_id'), 'url' => ee('CP/URL')->getCurrentUrl()->encode(), 'name' => $cp_page_title))?>"><i class="icon-add"></i><?=lang('new_link')?></a></li>
						</ul>
					</div>
				</div>
			</nav>
		</div>
		<div class="nav-main-wrap">
			<nav class="nav-main">
				<div class="nav-main-author">
					<?php if (ee()->cp->allowed_group('can_create_entries') && (count($cp_main_menu['channels']['create']) || ee()->cp->allowed_group('can_create_channels'))): ?>
					<div class="nav-create">
						<a class="nav-has-sub" href=""><?=lang('menu_create')?></a>
						<div class="nav-sub-menu">
							<?php if (count($cp_main_menu['channels']['create']) >= 10): ?>
								<form class="nav-filter">
									<input type="text" class="autofocus" value="" placeholder="<?=lang('filter_channels')?>">
								</form>
							<?php endif ?>
							<ul>
								<?php $last = ee()->cp->allowed_group('can_create_channels') ? NULL : end($cp_main_menu['channels']['create']); ?>
								<?php foreach ($cp_main_menu['channels']['create'] as $channel_name => $link): ?>
									<li><a href="<?=$link?>"><?=$channel_name?></a></li>
								<?php endforeach ?>
							</ul>
							<?php if (ee()->cp->allowed_group('can_create_channels')): ?>
							<a class="nav-add" href="<?=ee('CP/URL', 'channels/create')?>"><i class="icon-add"></i><?=lang('new_channel')?></a>
							<?php endif; ?>
						</div>
					</div>
					<?php endif; ?>
					<?php if (ee()->cp->allowed_group_any('can_edit_other_entries', 'can_edit_self_entries')): ?>
					<div class="nav-edit">
						<a class="nav-has-sub" href=""><?=lang('menu_edit')?></a>
						<div class="nav-sub-menu">
							<?php if (count($cp_main_menu['channels']['edit']) >= 10): ?>
								<form class="nav-filter">
									<input type="text" class="autofocus" value="" placeholder="<?=lang('filter_channels')?>">
									<hr>
									<a class="reset" href="<?=ee('CP/URL', 'publish/edit')?>"><b><?= lang('view_all') ?></b></a>
								</form>
							<?php endif ?>
							<ul>
								<?php foreach ($cp_main_menu['channels']['edit'] as $channel_name => $link): ?>
									<li><a href="<?=$link?>"><?=$channel_name?></a></li>
								<?php endforeach ?>
							</ul>
						</div>
					</div>
					<?php endif; ?>
					<?php if (ee()->cp->allowed_group('can_access_files')): ?>
					<a class="nav-files" href="<?=ee('CP/URL', 'files')?>"><i class="icon-files"></i><span class="nav-txt-collapse"><?=lang('menu_files')?></span></a>
					<?php endif; ?>
					<?php if (ee()->cp->allowed_group('can_access_members')): ?>
					<a class="nav-members" href="<?=ee('CP/URL', 'members')?>"><i class="icon-members"></i><span class="nav-txt-collapse"><?=lang('menu_members')?></span></a>
					<?php endif; ?>
				</div>
				<div class="nav-main-develop">
					<?php if (count($cp_main_menu['develop'])): ?>
					<div class="nav-tools">
						<a class="nav-has-sub" href="" title="<?=lang('nav_developer_tools')?>"><i class="icon-tools"></i><span class="nav-txt-collapse"><?=lang('nav_developer')?></span></a>
						<div class="nav-sub-menu">
							<ul>
								<?php foreach ($cp_main_menu['develop'] as $key => $link): ?>
									<li><a href="<?=$link?>"><?=lang($key)?></a></li>
								<?php endforeach ?>
							</ul>
						</div>
					</div>
					<?php endif; ?>
					<?php if (ee()->cp->allowed_group('can_access_sys_prefs')): ?>
					<a class="nav-settings" href="<?=ee('CP/URL', 'settings')?>" title="<?=lang('nav_settings')?>"><i class="icon-settings"></i><span class="nav-txt-collapse"><?=lang('nav_settings')?></span></a>
					<?php endif; ?>
				</div>
			</nav>
		<?php $custom = $cp_main_menu['custom']; ?>
		<?php if ($custom && $custom->hasItems()): ?>
			<div class="nav-custom-wrap">
				<nav class="nav-custom">
					<?php foreach ($custom->getItems() as $item): ?>
						<?php if ($item->isSubmenu()) :?>
							<div class="nav-item-sub">
								<a class="nav-has-sub" href=""><?=lang($item->title)?></a>
								<div class="nav-sub-menu">
									<?php if ($item->hasFilter()): ?>
									<form class="nav-filter">
										<input type="text" value="" placeholder="<?=lang($item->placeholder)?>">
									</form>
									<?php endif; ?>
									<ul>
										<?php foreach ($item->getItems() as $sub): ?>
										<li><a href="<?=$sub->url?>"><?=lang($sub->title)?></a></li>
										<?php endforeach; ?>
									</ul>
									<?php if ($item->hasAddLink()): ?>
									<a class="nav-add" href="<?=$item->addlink->url?>"><i class="icon-add"></i><?=lang($item->addlink->title)?></a>
									<?php endif; ?>
								</div>
							</div>
						<?php else: ?>
							<a class="nav-item" href="<?=$item->url?>"><?=lang($item->title)?></a>
						<?php endif; ?>
					<?php endforeach; ?>
				</nav>
			</div>
		<?php endif; ?>

		</div>
		<section class="wrap">


<?php
