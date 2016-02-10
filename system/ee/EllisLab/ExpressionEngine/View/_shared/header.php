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
		<section class="bar info-wrap">
			<nav class="snap">
				<div class="site">
					<?php if ($cp_homepage_url->path == 'homepage'): ?>
					<a class="home" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_homepage')?>"></a>
					<?php else: ?>
					<a class="home" href="<?=$cp_homepage_url?>" title="<?=lang('nav_homepage')?>"></a>
					<a class="overview" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_overview')?>"></a>
					<?php endif; ?>
					<?php if (ee()->config->item('multiple_sites_enabled') === 'y' && (count($cp_main_menu['sites']) > 1 || ee()->cp->allowed_group('can_admin_sites'))): ?>
						<a class="has-sub" href=""><?=ee()->config->item('site_name')?></a>
						<a href="<?=ee()->config->item('base_url').ee()->config->item('site_index')?>" rel="external"><?=lang('view')?></a>
						<ul class="sites-list sub-menu">
							<?php foreach ($cp_main_menu['sites'] as $site_name => $link): ?>
								<a href="<?=$link?>"><?=$site_name?></a>
							<?php endforeach ?>
							<?php if (ee()->cp->allowed_group('can_admin_sites')): ?>
								<a class="last add" href="<?=ee('CP/URL', 'msm/create')?>"><?=lang('new_site')?></a>
							<?php endif ?>
						</ul>
					<?php elseif ( ! ($site_name = ee()->config->item('site_name')) OR empty($site_name)): ?>
						<a href="<?=ee('CP/URL', 'settings')?>" class="no-name"><?=lang('name_your_site')?></a>
					<?php else: ?>
						<a href="<?=ee()->config->item('site_url')?>" rel="external"><?=ee()->config->item('site_name')?></a>
					<?php endif ?>
				</div>
				<div class="user">
					<a href="<?=ee('CP/URL', 'login/logout')?>"><?=lang('log_out')?></a> <a class="has-sub" href=""><?=$cp_screen_name?></a>
					<ul class="quick-links sub-menu">
						<a href="<?=ee('CP/URL')->make('members/profile', array('id' => ee()->session->userdata('member_id')))?>"><?=lang('my_profile')?></a>
						<?php foreach($cp_quicklinks as $link): ?>
						<a href="<?=$link['link']?>"><?=$link['title']?></a>
						<?php endforeach ?>
						<a class="last add" href="<?=ee('CP/URL')->make('members/profile/quicklinks/create', array('id' => ee()->session->userdata('member_id'), 'url' => ee('CP/URL')->getCurrentUrl()->encode(), 'name' => $cp_page_title))?>"><?=lang('new_link')?></a>
					</ul>
				</div>
			</nav>
		</section>
		<section class="bar menu-wrap">
			<nav class="snap">
				<ul class="author-menu">
						<?php if (ee()->cp->allowed_group('can_create_entries') && (count($cp_main_menu['channels']['create']) || ee()->cp->allowed_group('can_create_channels'))): ?>
						<li>
							<a class="has-sub" href=""><?=lang('menu_create')?></a>
							<div class="sub-menu">
								<?php if (count($cp_main_menu['channels']['create']) >= 10): ?>
									<form class="filter">
										<input type="text" class="autofocus" value="" placeholder="<?=lang('filter_channels')?>">
									</form>
									<div class="scroll-wrap">
								<?php endif ?>
								<ul class="channels-create">
									<?php $last = ee()->cp->allowed_group('can_create_channels') ? NULL : end($cp_main_menu['channels']['create']); ?>
									<?php foreach ($cp_main_menu['channels']['create'] as $channel_name => $link): ?>
										<li class="search-channel<?php if ($last == $link): ?> last<?php endif ?>" data-search="<?=strtolower($channel_name)?>"><a href="<?=$link?>"><?=$channel_name?></a></li>
									<?php endforeach ?>
									<?php if (ee()->cp->allowed_group('can_create_channels')): ?>
									<li class="last"><a class="add" href="<?=ee('CP/URL', 'channels/create')?>"><?=lang('new_channel')?></a></li>
									<?php endif; ?>
								</ul>
								<?php if (count($cp_main_menu['channels']['create']) >= 10): ?>
								</div>
								<?php endif ?>
							</div>
						</li>
						<?php if (ee()->cp->allowed_group_any('can_edit_other_entries', 'can_edit_self_entries')): ?>
						<li>
							<a class="has-sub" href=""><?=lang('menu_edit')?></a>
							<div class="sub-menu">
								<?php if (count($cp_main_menu['channels']['edit']) >= 10): ?>
									<form class="filter">
										<input type="text" class="autofocus" value="" placeholder="<?=lang('filter_channels')?>">
									</form>
									<div class="scroll-wrap">
								<?php endif ?>
								<ul class="channels-edit">
									<li class="search-channel<?php if (empty($cp_main_menu['channels']['edit'])): ?> last<?php endif ?>" data-search="<?= strtolower(lang('view_all')) ?>">
										<a href="<?=ee('CP/URL', 'publish/edit')?>"><?= lang('view_all') ?></a>
									</li>
									<?php foreach ($cp_main_menu['channels']['edit'] as $channel_name => $link): ?>

										<?php
										$class = 'search-channel';
										if ($link == end($cp_main_menu['channels']['edit']))
										{
											$class .= ' last';
										}
										?>

										<li class="<?=$class?>" data-search="<?=strtolower($channel_name)?>"><a href="<?=$link?>"><?=$channel_name?></a></li>
									<?php endforeach ?>
								</ul>
								<?php if (count($cp_main_menu['channels']['edit']) >= 10): ?>
									</div>
								<?php endif ?>
							</div>
						</li>
						<?php endif; ?>
					<?php endif; ?>
					<?php if (ee()->cp->allowed_group('can_access_files')): ?>
					<li><a href="<?=ee('CP/URL', 'files')?>"><?=lang('menu_files')?></a></li>
					<?php endif; ?>
					<?php if (ee()->cp->allowed_group('can_access_members')): ?>
					<li><a href="<?=ee('CP/URL', 'members')?>"><?=lang('menu_members')?></a></li>
					<?php endif; ?>
				</ul>
				<ul class="dev-menu">
					<?php if (count($cp_main_menu['develop'])): ?>
					<li class="develop">
						<a class="has-sub" href="" title="<?=lang('nav_developer_tools')?>"></a>
						<div class="sub-menu">
							<ul>
								<?php
								// Grab the first and last items from the menu to determine
								// which items we need to put 'last' classes on
								$last = array_values(array_slice($cp_main_menu['develop'], -1, 1));

								foreach ($cp_main_menu['develop'] as $key => $link):
									$class = '';
									if ($link == $last[0])
									{
										$class = 'last';
									}
								?>
									<li<?php if ( ! empty($class)): ?> class="<?=$class?>"<?php endif; ?>><a href="<?=$link?>"><?=lang($key)?></a></li>
								<?php endforeach ?>
							</ul>
						</div>
					</li>
					<?php endif; ?>
					<?php if (ee()->cp->allowed_group('can_access_sys_prefs')): ?>
					<li class="settings"><a href="<?=ee('CP/URL', 'settings')?>" title="<?=lang('nav_settings')?>"><b class="ico settings"></b> <!-- Settings --></a></li>
					<?php endif; ?>
				</ul>
			</nav>
		</section>
		<section class="wrap">

<?php
