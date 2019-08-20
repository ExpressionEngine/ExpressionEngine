<?php
$this->enabled('ee_header') && $this->embed('_shared/header');
?>

<div class="theme-switch-circle"></div>

<?php $current_page = ''; ?>

<section class="ee-wrapper">

	<div class="ee-sidebar">
		<?php if (ee()->config->item('multiple_sites_enabled') === 'y' && (count($cp_main_menu['sites']) > 0 || ee()->cp->allowed_group('can_admin_sites'))): ?>
		<div class="nav-sites">
			<a class="nav-has-sub" href=""><?=ee()->config->item('site_name')?></a>
			<a class="nav-view" href="<?=ee()->config->item('site_url')?>" rel="external"><i class="icon-view"></i><span class="nav-txt-collapse"><?=lang('view')?></span></a>
			<ul class="nav-sub-menu">
				<?php foreach ($cp_main_menu['sites'] as $site_name => $link): ?>
					<li><a href="<?=$link?>"><?=$site_name?></a></li>
				<?php endforeach ?>
				<?php if (ee()->cp->allowed_group('can_admin_sites')): ?>
					<li><a class="nav-manage" href="<?=ee('CP/URL', 'msm')?>"><i class="icon-settings"></i>Manage Sites</a></li>
					<li><a class="nav-add" href="<?=ee('CP/URL', 'msm/create')?>"><i class="icon-add"></i><?=lang('new_site')?></a></li>
				<?php endif ?>
			</ul>
		</div>
		<?php elseif ( ! ($site_name = ee()->config->item('site_name')) OR empty($site_name)): ?>
			<a class="nav-no-name" href="<?=ee('CP/URL', 'settings')?>" class="no-name"><i class="icon-settings"></i><?=lang('name_your_site')?></a>
		<?php else: ?>
			<a class="nav-site" href="<?=ee()->config->item('site_url')?>" rel="external"><?=ee()->config->item('site_name')?></a>
		<?php endif ?>
		<div class="ee-sidebar-title">EllisLab</div>

		<div class="ee-sidebar__items">
			<div>
				<!-- <?php if ($cp_homepage_url->path == 'homepage'): ?>
					<a class="nav-home" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_homepage')?>"><i class="icon-home"></i><span class="nav-txt-collapse"><?=lang('nav_homepage')?></span></a>
					<?php else: ?>
					<a class="nav-home" href="<?=$cp_homepage_url?>" title="<?=lang('nav_homepage')?>"><i class="icon-home"></i><span class="nav-txt-collapse"><?=lang('nav_homepage')?></span></a>
					<a class="nav-overview" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_overview')?>"><i class="icon-dashboard"></i><span class="nav-txt-collapse"><?=lang('nav_overview')?></span></a>
					<?php endif; ?> -->
				<a href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_overview')?>" class="<?= ($current_page == 'dashbaord' ? 'active' : '') ?>"><i class="fas fa-tachometer-alt"></i> <?=lang('nav_overview')?></a>

				<?php if (ee()->cp->allowed_group('can_create_entries') && (count($cp_main_menu['channels']['create']) || ee()->cp->allowed_group('can_create_channels')) || ee()->cp->allowed_group_any('can_edit_other_entries', 'can_edit_self_entries')) : ?>
				<a href="<?= ee('CP/URL', 'publish/edit') ?>" class="<?= (($current_page == 'entry' || $current_page == 'entries') ? 'active' : '') ?>"><i class="fas fa-newspaper"></i> <?= lang('entries') ?></a>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_files')) : ?>
				<a href="<?= ee('CP/URL', 'files') ?>" class="<?= ($current_page == 'files' ? 'active' : '') ?>"><i class="fas fa-folder"></i> <?= lang('menu_files') ?></a>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_members')) : ?>
				<a href="<?= ee('CP/URL', 'members') ?>" class="<?= ($current_page == 'members' ? 'active' : '') ?>"><i class="fas fa-users"></i> <?= lang('menu_members') ?></a>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_admin_channels') && ee()->cp->allowed_group_any('can_create_categories', 'can_edit_categories', 'can_delete_categories')) : ?>
				<a href="<?= ee('CP/URL')->make('categories') ?>" class="<?= ($current_page == 'categories' ? 'active' : '') ?>"><i class="fas fa-tags"></i> <?= lang('categories') ?></a>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_addons')) : ?>
				<a href="<?= ee('CP/URL')->make('addons') ?>" class="<?= ($current_page == 'add-ons' ? 'active' : '') ?>"><i class="fas fa-bolt"></i> <?= lang('addons') ?></a>
				<?php endif; ?>


				<!-- Custom Links -->
				<?php $custom = $cp_main_menu['custom']; ?>
				<?php if ($custom && $custom->hasItems()) : ?>
				<div class="nav-custom-wrap">
					<nav class="nav-custom">
						<?php foreach ($custom->getItems() as $item) : ?>
						<?php if ($item->isSubmenu()) : ?>
						<div class="nav-item-sub">
							<a class="nav-has-sub" href=""><?= lang($item->title) ?></a>
							<div class="nav-sub-menu">
								<?php if ($item->hasFilter()) : ?>
								<form class="nav-filter">
									<input type="text" value="" placeholder="<?= lang($item->placeholder) ?>">

									<?php if (count($item->getItems()) < 10 && !empty($item->view_all_link)) : ?>
									<hr>
									<a class="reset" href="<?= $item->view_all_link ?>"><b><?= lang('view_all') ?></b></a>
									<?php endif; ?>
								</form>
								<?php endif; ?>
								<ul>
									<?php foreach ($item->getItems() as $sub) : ?>
									<li><a href="<?= $sub->url ?>"><?= lang($sub->title) ?></a></li>
									<?php endforeach; ?>
								</ul>
								<?php if ($item->hasAddLink()) : ?>
								<a class="nav-add" href="<?= $item->addlink->url ?>"><i class="icon-add"></i><?= lang($item->addlink->title) ?></a>
								<?php endif; ?>
							</div>
						</div>
						<?php else : ?>
						<a class="nav-item" href="<?= $item->url ?>"><?= lang($item->title) ?></a>
						<?php endif; ?>
						<?php endforeach; ?>
					</nav>
				</div>
				<?php endif; ?>
			</div>

			<div class="ee-sidebar__items-bottom">
				<a href=""><i class="fas fa-database"></i> Developer</a>
				<?php if (count($cp_main_menu['develop'])) : ?>
				<div class="nav-tools">
					<a class="nav-has-sub" href="" title="<?= lang('nav_developer_tools') ?>"><i class="icon-tools"></i><span class="nav-txt-collapse"><?= lang('nav_developer') ?></span></a>
					<div class="nav-sub-menu">
						<ul>
							<?php foreach ($cp_main_menu['develop'] as $key => $link) : ?>
							<li><a href="<?= $link ?>"><?= lang($key) ?></a></li>
							<?php endforeach ?>
						</ul>
					</div>
				</div>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_sys_prefs')) : ?>
				<a href="<?= ee('CP/URL', 'settings') ?>" title="<?= lang('nav_settings') ?>"><i class="fas fa-cog"></i> <?= lang('nav_settings') ?></a>
				<?php endif; ?>

				<a href="" class="ee-sidebar__version">ExpressionEngine <span>6.0.0</span></a>
			</div>

		</div>
	</div>

	<div class="ee-main">
		<?= $child_view ?>
	</div>
</section>

<?php
$this->enabled('ee_footer') && $this->embed('_shared/footer');
?>
