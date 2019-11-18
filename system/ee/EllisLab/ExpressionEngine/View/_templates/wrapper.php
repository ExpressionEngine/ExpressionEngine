<?php
$this->enabled('ee_header') && $this->embed('_shared/header');
?>

<div class="theme-switch-circle"></div>

<?php
// Get the current page to highlight it in the sidebar
$current_page = ee()->uri->segment(2);
?>

<section class="ee-wrapper">

	<div class="ee-sidebar">
		<?php if (ee()->config->item('multiple_sites_enabled') === 'y' && (count($cp_main_menu['sites']) > 0 || ee()->cp->allowed_group('can_admin_sites'))): ?>
		<a class="ee-sidebar__title js-dropdown-toggle" data-dropdown-pos="bottom-center"><?=ee()->config->item('site_name')?><span class="ee-sidebar__title-down-arrow"><i class="fas fa-chevron-down"></i></span></a>

		<div class="dropdown">
			<a class="dropdown__link" href="<?=ee()->config->item('site_url')?>" rel="external"><i class="fas fa-eye"></i> <?=lang('view_site')?></a>
			<div class="dropdown__divider"></div>
			<div class="dropdown__header"><?=lang('sites')?></div>

			<?php foreach ($cp_main_menu['sites'] as $site_name => $link): ?>
				<a class="dropdown__link" href="<?=$link?>"><?=$site_name?></a>
			<?php endforeach ?>
		</div>
		<?php elseif ( ! ($site_name = ee()->config->item('site_name')) OR empty($site_name)): ?>
			<a class="ee-sidebar__title ee-sidebar__title--needs-name" href="<?=ee('CP/URL', 'settings')?>"><i class="fas fa-cog"></i> <?=lang('name_your_site')?></a>
		<?php else: ?>
			<a class="ee-sidebar__title" href="<?=ee()->config->item('site_url')?>" rel="external"><?=ee()->config->item('site_name')?></a>
		<?php endif ?>

		<div class="ee-sidebar__items">
			<div>
				<!-- <?php if ($cp_homepage_url->path == 'homepage'): ?>
					<a class="nav-home" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_homepage')?>"><i class="icon-home"></i><span class="nav-txt-collapse"><?=lang('nav_homepage')?></span></a>
					<?php else: ?>
					<a class="nav-home" href="<?=$cp_homepage_url?>" title="<?=lang('nav_homepage')?>"><i class="icon-home"></i><span class="nav-txt-collapse"><?=lang('nav_homepage')?></span></a>
					<a class="nav-overview" href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_overview')?>"><i class="icon-dashboard"></i><span class="nav-txt-collapse"><?=lang('nav_overview')?></span></a>
					<?php endif; ?> -->
				<a href="<?=ee('CP/URL', 'homepage')?>" title="<?=lang('nav_overview')?>" class="ee-sidebar__item <?= ($current_page == 'homepage' ? 'active' : '') ?>"><i class="fas fa-tachometer-alt"></i> <?=lang('nav_overview')?></a>

				<?php if (ee()->cp->allowed_group_any('can_edit_other_entries', 'can_edit_self_entries')) : ?>
				<a data-dropdown-pos="right-start" href class="ee-sidebar__item js-dropdown-toggle <?= (($current_page == 'publish') ? 'active' : '') ?>"><i class="fas fa-newspaper"></i> <?= lang('entries') ?></a>
				<div class="dropdown js-filterable">
					<a href="<?= ee('CP/URL', 'publish/edit') ?>" class="dropdown__link"><b>View All</b></a>
					<?php foreach ($cp_main_menu['channels']['edit'] as $channel_name => $link): ?>
						<div class="dropdown__item">
							<a href="<?=$link?>"><?=$channel_name?></a>
							<?php if (ee()->cp->allowed_group('can_create_entries') && array_key_exists($channel_name, $cp_main_menu['channels']['create'])): ?>
							<a href="<?=$cp_main_menu['channels']['create'][$channel_name]?>" class="dropdown__item-button button button--action button--small"><i class="fas fa-plus"></i></a>
							<?php endif; ?>
						</div>
					<?php endforeach ?>
				</div>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_files')) : ?>
				<a href="<?= ee('CP/URL', 'files') ?>" class="ee-sidebar__item <?= ($current_page == 'files' ? 'active' : '') ?>"><i class="fas fa-folder"></i> <?= lang('menu_files') ?></a>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_members')) : ?>
				<a href="<?= ee('CP/URL', 'members') ?>" class="ee-sidebar__item <?= ($current_page == 'members' ? 'active' : '') ?>"><i class="fas fa-users"></i> <?= lang('menu_members') ?></a>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_admin_channels') && ee()->cp->allowed_group_any('can_create_categories', 'can_edit_categories', 'can_delete_categories')) : ?>
				<a href="<?= ee('CP/URL')->make('categories') ?>" class="ee-sidebar__item <?= ($current_page == 'categories' ? 'active' : '') ?>"><i class="fas fa-tags"></i> <?= lang('categories') ?></a>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_addons')) : ?>
				<a href="<?= ee('CP/URL')->make('addons') ?>" class="ee-sidebar__item <?= ($current_page == 'addons' ? 'active' : '') ?>"><i class="fas fa-bolt"></i> <?= lang('addons') ?></a>
				<?php endif; ?>
			</div>

			<!-- Custom Links -->
			<?php $custom = $cp_main_menu['custom']; ?>
			<?php if ($custom && $custom->hasItems()) : ?>
			<div class="ee-sidebar__items-custom">
				<nav class="nav-custom">
					<?php foreach ($custom->getItems() as $item) : ?>
					<?php if ($item->isSubmenu()) : ?>
						<a class="js-dropdown-toggle ee-sidebar__item" data-dropdown-pos="bottom-center" href=""><?= lang($item->title) ?></a>
						<div class="dropdown">
							<?php if ($item->hasFilter()) : ?>
							<form class="dropdown__search">
								<input type="text" value="" placeholder="<?= lang($item->placeholder) ?>">
							</form>
								<?php if (count($item->getItems()) < 10 && !empty($item->view_all_link)) : ?>
								<a class="dropdown__link" href="<?= $item->view_all_link ?>"><b><?= lang('view_all') ?></b></a>
									<?php if (count($item->getItems()) != 0): ?>
									<div class="dropdown__divider"></div>
									<?php endif; ?>
								<?php endif; ?>
							<?php endif; ?>

							<?php foreach ($item->getItems() as $sub) : ?>
							<a class="dropdown__link" href="<?= $sub->url ?>"><?= lang($sub->title) ?></a>
							<?php endforeach; ?>

							<?php if ($item->hasAddLink()) : ?>
							<a class="dropdown__link" class="nav-add" href="<?= $item->addlink->url ?>"><i class="fas fa-plus"></i><?= lang($item->addlink->title) ?></a>
							<?php endif; ?>
						</div>
					<?php else : ?>
					<a class="ee-sidebar__item" href="<?= $item->url ?>"><?= lang($item->title) ?></a>
					<?php endif; ?>
					<?php endforeach; ?>
				</nav>
			</div>
			<?php endif; ?>

			<div class="ee-sidebar__items-bottom">
				<?php if (count($cp_main_menu['develop'])) : ?>
					<?php
						$developer_pages = ['fields', 'channels', 'design', 'msm', 'utilities', 'logs'];
						$developer_menu_active = (in_array($current_page, $developer_pages) ? 'active' : '');
					?>
					<a href="" class="ee-sidebar__item js-toggle-developer-menu <?=$developer_menu_active?>"><i class="fas fa-database"></i> <?=lang('nav_developer')?></a>
					<div class="developer-menu js-developer-menu-content hidden">
						<?php foreach ($cp_main_menu['develop'] as $key => $link) : ?>
							<a class="ee-sidebar__item" href="<?= $link ?>"><?= lang($key) ?></a>
						<?php endforeach ?>
					</div>
				<?php endif; ?>

				<?php if (ee()->cp->allowed_group('can_access_sys_prefs')) : ?>
				<a href="<?= ee('CP/URL', 'settings') ?>" title="<?= lang('nav_settings') ?>" class="ee-sidebar__item <?= ($current_page == 'settings' ? 'active' : '') ?>"><i class="fas fa-cog"></i> <?= lang('nav_settings') ?></a>
				<?php endif; ?>


				<?php
					$version_class = '';
					$update_available = isset($new_version);
					$vital_update = $update_available && $new_version['security'];

					if ( ! empty($version_identifier))
					{
						$version_class .= ' ee-sidebar__version--dev';
					}
					elseif ($update_available)
					{
						if ($vital_update) {
							$version_class .= ' ee-sidebar__version--update-vital';
						} else {
							$version_class .= ' ee-sidebar__version--update';
						}
					}
				?>

				<a href="" data-dropdown-pos="top-start" data-toggle-dropdown="app-about-dropdown" class="ee-sidebar__item ee-sidebar__version js-about <?=$version_class?>">ExpressionEngine <span><?=$formatted_version?></span></a>
			</div>

		</div>
	</div>

	<div class="ee-main">
		<?= $child_view ?>
	</div>
</section>


<div class="dropdown app-about" data-dropdown="app-about-dropdown">
	<div class="app-about__title">ExpressionEngine <span class="float-right"><?=$formatted_version?></span></div>
	<div class="app-about__subtitle">
		&copy;<?=date('Y')?> <a href="https://expressionengine.com/" rel="external noreferrer">Packet Tide, LLC</a>.
		<span class="float-right"><?=$ee_build_date?></span>
	</div>

	<div href="https://expressionengine.com/support" class="dropdown__link app-about__support-link"><i class="fas fa-life-ring fa-fw"></i> <?=lang('support')?></div>

	<?php if (ee()->cp->allowed_group('can_access_footer_report_bug')): ?>
		<a href="https://expressionengine.com/support/bugs/new" class="dropdown__link app-about__bug-link" rel="external noreferrer"><i class="fas fa-bug fa-fw"></i>  <?=lang('report_bug')?></a>
	<?php endif ?>
	<?php if (ee()->cp->allowed_group('can_access_footer_user_guide')): ?>
		<a href="<?=DOC_URL?>" class="dropdown__link app-about__user-guide-link" rel="external noreferrer"><i class="fas fa-book fa-fw"></i> <?=lang('user_guide')?></a>
	<?php endif; ?>

	<?php if (ee()->session->userdata('group_id') == 1): ?>
		<div class="app-about__status app-about__status--checking">
			<?=lang('checking_for_updates')?>
		</div>
		<div class="app-about__status app-about__status--update-to-date hidden">
			<?=lang('up_to_date')?>
		</div>
		<div class="app-about__status app-about__status--update hidden">
			<?=lang('out_of_date_upgrade')?>
			<a data-post-url="<?=ee('CP/URL', 'updater')?>" class="button button--action"><?=lang('update_btn')?></a>
			<div class="app-about__status-version"></div>
		</div>
		<div class="app-about__status app-about__status--update-vital hidden">
			<?=lang('out_of_date_recommended')?>
			<a data-post-url="<?=ee('CP/URL', 'updater')?>" class="button button--action"><?=lang('update_btn')?></a>
			<div class="app-about__status-version"></div>
		</div>
	<?php endif ?>
</div>


<?php
$this->enabled('ee_footer') && $this->embed('_shared/footer');
?>
