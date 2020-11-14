			<?php $cp_main_menu = ee()->menu->generate_menu(); $current_page = ee()->uri->segment(2); ?>
			<div class="ee-sidebar <?=$class?> <?php if (!isset($ee_cp_viewmode) || $ee_cp_viewmode!='classic') : ?> hidden<?php endif; ?><?php if (isset($collapsed_nav) && $collapsed_nav=='1') : ?> ee-sidebar__collapsed<?php endif; ?>">
				<?php if (ee()->config->item('multiple_sites_enabled') === 'y' && (count($cp_main_menu['sites']) > 0 || ee('Permission')->can('admin_sites'))): ?>
				<a class="ee-sidebar__title js-dropdown-toggle" data-dropdown-use-root="true" data-dropdown-pos="bottom-center" title="<?=ee()->config->item('site_name')?>"><span class="ee-sidebar__title-name"><i class="fas fa-desktop fa-fw"></i><span class="ee-sidebar__collapsed-hidden"> <?=ee()->config->item('site_name')?></span></span><span class="ee-sidebar__title-down-arrow ee-sidebar__collapsed-hidden"><i class="fas fa-angle-down"></i></span></a>

				<div class="dropdown dropdown--accent">
					<a class="dropdown__link" href="<?=ee()->config->item('site_url')?>" rel="external"><i class="fas fa-eye"></i> <?=lang('view_site')?></a>
					<div class="dropdown__divider"></div>
					<div class="dropdown__header"><?=lang('sites')?></div>

					<?php
					if (!empty($cp_main_menu['sites'])) :
					foreach ($cp_main_menu['sites'] as $site_name => $link):
					?>
						<a class="dropdown__link" href="<?=$link?>"><?=$site_name?></a>
					<?php endforeach; ?>
					<div class="dropdown__divider"></div>
					<?php endif; ?>
					<a class="dropdown__link" href="<?=ee('CP/URL', 'msm/create')?>"><i class="fas fa-plus"></i> <?=lang('add_site')?></a>
				</div>
				<?php elseif ( ! ($site_name = ee()->config->item('site_name')) OR empty($site_name)): ?>
					<a class="ee-sidebar__title ee-sidebar__title--needs-name" href="<?=ee('CP/URL', 'settings')?>"><i class="fas fa-desktop fa-fw"></i> <span class="ee-sidebar__collapsed-hidden"><?=lang('name_your_site')?></span></a>
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
								<a class="js-dropdown-toggle ee-sidebar__item js-dropdown-hover" data-dropdown-use-root="true" data-dropdown-pos="right-start" href="#" title="<?= lang($item->title) ?>"><span class="ee-sidebar__item-custom-icon"><?=substr(lang($item->title), 0, 1)?></span><span class="ee-sidebar__collapsed-hidden"><?= lang($item->title) ?></span></a>
								<div class="dropdown dropdown--accent" style="margin-left: -12px;">
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
							<a class="ee-sidebar__item" href="<?= $item->url ?>" title="<?= lang($item->title) ?>"><span class="ee-sidebar__item-custom-icon"><?=substr(lang($item->title), 0, 1)?></span><span class="ee-sidebar__collapsed-hidden"><?= lang($item->title) ?></span></a>
							<?php endif; ?>
							<?php endforeach; ?>
						</nav>
					</div>
					<?php endif; ?>

          <div class="ee-sidebar__items-dev">
  					<?=$bottom?>
          </div>


          <div class="ee-sidebar__items-bottom">
            <?php
							$version_class = '';
							$update_available = isset(ee()->view->new_version);
							$vital_update = $update_available && ee()->view->new_version && ee()->view->new_version['security'];

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

						<a href="" data-dropdown-use-root="true" data-dropdown-pos="top-start" data-toggle-dropdown="app-about-dropdown" class="ee-sidebar__item ee-sidebar__version js-dropdown-toggle js-about <?=$version_class?>" title="ExpressionEngine">
							<svg class="ee-logomark" width="50px" height="35px" viewBox="0 0 50 35" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
								<defs>
									<polygon id="path-1" points="0 0.06905 25.8202 0.06905 25.8202 31.6178513 0 31.6178513"></polygon>
									<polygon id="path-3" points="0.10635 0.204 25.9268587 0.204 25.9268587 31.7517 0.10635 31.7517"></polygon>
								</defs>
								<g id="ee-logomark" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
									<g id="ee-logomark-outer">
										<path d="M20.60205,17.64605 C21.11355,14.75605 22.01655,12.45255 23.28405,10.79305 C24.18105,9.60555 25.17405,9.00405 26.23755,9.00405 C26.80055,9.00405 27.27705,9.22055 27.65305,9.64955 C28.01805,10.06905 28.20405,10.64605 28.20405,11.36305 C28.20405,13.02405 27.45705,14.53555 25.98455,15.86155 C24.91705,16.81355 23.20305,17.51055 20.89205,17.93305 L20.53855,17.99805 L20.60205,17.64605 Z M30.67305,21.68355 C29.37505,22.92855 28.23905,23.80705 27.31805,24.24655 C26.34905,24.70655 25.34805,24.93855 24.34355,24.93855 C23.11755,24.93855 22.12155,24.54805 21.38655,23.77655 C20.65105,23.00705 20.27805,21.90355 20.27805,20.49455 L20.37305,19.08355 L20.56855,19.05005 C24.00755,18.47005 26.60155,17.80655 28.27555,17.07555 C29.93155,16.35405 31.14005,15.49505 31.86855,14.52405 C32.59155,13.56105 32.95655,12.59155 32.95655,11.65055 C32.95655,10.50805 32.52355,9.59355 31.63105,8.84705 C30.73555,8.10155 29.44355,7.72455 27.79455,7.72455 C25.50305,7.72455 23.33455,8.25905 21.34955,9.31405 C19.36805,10.36805 17.78305,11.82905 16.64005,13.65605 C15.50005,15.48105 14.92155,17.41555 14.92155,19.40105 C14.92155,21.61755 15.60505,23.39505 16.95205,24.68005 C18.30455,25.96905 20.19355,26.62005 22.56705,26.62005 C24.25255,26.62005 25.84755,26.28155 27.30805,25.61355 C28.70455,24.97455 30.14905,23.86705 31.60805,22.37255 C31.33005,22.16805 30.87005,21.82855 30.67305,21.68355 L30.67305,21.68355 Z" id="Fill-35" ></path>
										<g id="Group-39" transform="translate(0.000000, 2.796000)">
											<mask id="mask-2" fill="white">
												<use xlink:href="#path-1"></use>
											</mask>
											<g id="Clip-38"></g>
											<path d="M7.2737,19.35005 C5.3202,11.70605 9.9462,3.71505 17.8897,0.06905 C17.6907,0.14055 17.5042,0.22255 17.3077,0.29605 C17.5087,0.20005 17.6882,0.11955 17.8272,0.07205 L2.9432,3.91255 L6.9112,6.26005 C1.7147,10.66105 -0.9663,16.11555 0.3187,21.14505 C2.3302,29.02005 13.3457,33.12605 25.8202,31.10805 C17.1117,31.75655 9.2257,26.99355 7.2737,19.35005" id="Fill-37"  mask="url(#mask-2)"></path>
										</g>
										<g id="Group-42" transform="translate(23.500000, 0.296000)">
											<mask id="mask-4" fill="white">
												<use xlink:href="#path-3"></use>
											</mask>
											<g id="Clip-41"></g>
											<path d="M18.65285,12.4697 C20.60635,20.1147 15.98135,28.1052 8.03735,31.7517 C8.23585,31.6797 8.42235,31.5977 8.61885,31.5232 C8.41785,31.6212 8.23835,31.7002 8.09935,31.7482 L22.98335,27.9087 L19.01585,25.5612 C24.21185,21.1597 26.89285,15.7042 25.60835,10.6747 C23.59635,2.8027 12.58085,-1.3053 0.10635,0.7142 C8.81435,0.0637 16.70135,4.8267 18.65285,12.4697" id="Fill-40"  mask="url(#mask-4)"></path>
										</g>
									</g>
								</g>
							</svg>
							<span class="ee-sidebar__collapsed-hidden">ExpressionEngine <span class="ee-sidebar__version-number"><?=ee()->view->formatted_version?></span></span>
						</a>
					</div>

				</div>
			</div>




