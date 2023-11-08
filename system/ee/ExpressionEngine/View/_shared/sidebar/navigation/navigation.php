			<?php $sites = ee()->session->userdata('assigned_sites'); ?>
			<div role="navigation" class="ee-sidebar <?php if (!isset($ee_cp_viewmode) || $ee_cp_viewmode != 'classic') : ?> hidden<?php endif; ?><?php if (isset($collapsed_nav) && $collapsed_nav == '1') : ?> ee-sidebar__collapsed<?php endif; ?>">
				<?php if (ee()->config->item('multiple_sites_enabled') === 'y' && (count($sites) > 0 || ee('Permission')->can('admin_sites'))): ?>
				<a class="ee-sidebar__title js-dropdown-toggle" data-dropdown-use-root="true" data-dropdown-pos="bottom-center" title="<?=ee()->config->item('site_name')?>"><span class="ee-sidebar__title-name"><i class="fal fa-desktop fa-fw"></i><span class="ee-sidebar__collapsed-hidden"> <?=ee()->config->item('site_name')?></span></span><span class="ee-sidebar__title-down-arrow ee-sidebar__collapsed-hidden"><i class="fal fa-angle-down"></i></span></a>

				<div class="dropdown dropdown--accent">
					<a class="dropdown__link" href="<?=ee()->config->item('site_url')?>" rel="external"><i class="fal fa-eye"></i> <?=lang('view_site')?></a>
					<div class="dropdown__divider"></div>
					<div class="dropdown__header"><?=lang('sites')?></div>

					<?php
                    if (!empty($sites)) :
                    foreach ($sites as $site_id => $site_name):
                        if ($site_id != ee()->config->item('site_id')) :
                    ?>
						<a class="dropdown__link" href="<?=ee('CP/URL', 'msm/switch_to/' . $site_id)?>"><?=$site_name?></a>
					<?php
                        endif;
                    endforeach;
                    ?>
					<div class="dropdown__divider"></div>
					<?php endif; ?>
					<a class="dropdown__link" href="<?=ee('CP/URL', 'msm/create')?>"><i class="fal fa-plus"></i> <?=lang('add_site')?></a>
				</div>
				<?php elseif (! ($site_name = ee()->config->item('site_name')) or empty($site_name)): ?>
					<a class="ee-sidebar__title ee-sidebar__title--needs-name" href="<?=ee('CP/URL', 'settings')?>"><i class="fal fa-desktop fa-fw"></i> <span class="ee-sidebar__collapsed-hidden"><?=lang('name_your_site')?></span></a>
				<?php else: ?>
					<a class="ee-sidebar__title" href="<?=ee()->config->item('site_url')?>" rel="external" title="<?=ee()->config->item('site_name')?>"><span class="ee-sidebar__title-name"><i class="fal fa-desktop fa-fw"></i> <?=ee()->config->item('site_name')?></span></a>
				<?php endif ?>

                <?php echo ee('CP/NavigationSidebar')->render(); ?>

			</div>
