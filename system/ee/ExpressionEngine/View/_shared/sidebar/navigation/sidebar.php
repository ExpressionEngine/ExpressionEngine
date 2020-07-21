			<?php $cp_main_menu = ee()->menu->generate_menu(); $current_page = ee()->uri->segment(2); ?>
			<div class="ee-sidebar <?=$class?> <?php if (!isset($ee_cp_viewmode) || $ee_cp_viewmode!='classic') : ?> hidden<?php endif; ?>">
				<?php if (ee()->config->item('multiple_sites_enabled') === 'y' && (count($cp_main_menu['sites']) > 0 || ee('Permission')->can('admin_sites'))): ?>
				<a class="ee-sidebar__title js-dropdown-toggle" data-dropdown-use-root="true" data-dropdown-pos="bottom-center" title="<?=ee()->config->item('site_name')?>"><span class="ee-sidebar__title-name"><i class="fas fa-desktop fa-fw"></i> <?=ee()->config->item('site_name')?></span><span class="ee-sidebar__title-down-arrow"><i class="fas fa-angle-down"></i></span></a>

				<div class="dropdown dropdown--accent">
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
					<a class="ee-sidebar__title" href="<?=ee()->config->item('site_url')?>" rel="external" title="<?=ee()->config->item('site_name')?>"><span class="ee-sidebar__title-name"><i class="fas fa-desktop fa-fw"></i> <?=ee()->config->item('site_name')?></span></a>
				<?php endif ?>

				<div class="ee-sidebar__items">
					<div>
						<?=$sidebar?>
					</div>

					<!-- Custom Links -->
					<?php $custom = $cp_main_menu['custom']; ?>
					<?php if ($custom && $custom->hasItems()) : ?>
					<div class="ee-sidebar__items-custom">
						<nav class="nav-custom">
							<?php foreach ($custom->getItems() as $item) : ?>
							<?php if ($item->isSubmenu()) : ?>
								<a class="js-dropdown-toggle ee-sidebar__item" data-dropdown-use-root="true" data-dropdown-pos="bottom-center" href="#"><?= lang($item->title) ?></a>
								<div class="dropdown">
									<?php if ($item->hasFilter()) : ?>
									<form class="dropdown__search">
										<div class="search-input">
											<input class="search-input__input input--small" type="text" value="" placeholder="<?= lang($item->placeholder) ?>">
										</div>
									</form>
										<?php if (count($item->getItems()) < 10 && !empty($item->view_all_link)) : ?>
										<a class="dropdown__link" href="<?= $item->view_all_link ?>"><b><?= lang('view_all') ?></b></a>
											<?php if (count($item->getItems()) != 0): ?>
											<div class="dropdown__divider"></div>
											<?php endif; ?>
										<?php endif; ?>
									<?php endif; ?>

									<div class="dropdown__scroll">
									<?php foreach ($item->getItems() as $sub) : ?>
									<a class="dropdown__link" href="<?= $sub->url ?>"><?= lang($sub->title) ?></a>
									<?php endforeach; ?>

									<?php if ($item->hasAddLink()) : ?>
									<a class="dropdown__link" class="nav-add" href="<?= $item->addlink->url ?>"><i class="fas fa-plus"></i><?= lang($item->addlink->title) ?></a>
									<?php endif; ?>
									</div>
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

						<?php if (ee('Permission')->can('access_sys_prefs')) : ?>
						<a href="<?= ee('CP/URL', 'settings') ?>" title="<?= lang('nav_settings') ?>" class="ee-sidebar__item <?= ($current_page == 'settings' ? 'active' : '') ?>"><i class="fas fa-cog"></i> <?= lang('nav_settings') ?></a>
						<?php endif; ?>


						<?php
							$version_class = '';
							$update_available = isset(ee()->view->new_version);
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

						<a href="" data-dropdown-use-root="true" data-dropdown-pos="top-start" data-toggle-dropdown="app-about-dropdown" class="ee-sidebar__item ee-sidebar__version js-dropdown-toggle js-about <?=$version_class?>">ExpressionEngine <span><?=ee()->view->formatted_version?></span></a>
					</div>

				</div>
			</div>




