<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>
<div class="panel panel__no-main-title">
	<div class="tbl-ctrls">
		<div class="panel-heading">
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines() ?></div>
			<?php if (!empty($updates) && count($updates)==1) : ?>
				<div id="alert_banner" class="alert">
					<div class="alert__icon"><i class="fas fa-info-circle fa-fw"></i></div>
					<div id="alert" class="alert__close">
						<a id="alert_close" class="fas fa-times alert__close-icon"></a>
					</div>
					<p class="alert__title"> <br>
					<p class="alert__title"><?php echo lang('update'); ?><br>
						<?php 
						$count=count($updates);
						echo sprintf(lang('single_update'), $count); 
						?>
						
						<a id="show_me"><?php echo lang('show'); ?></a>
					</p>
						
				</div>
			<?php endif; ?>

			<?php if (!empty($updates) && count($updates) > 1) : ?>
				<div id="alert_banner" class="alert">
					<div class="alert__icon"><i class="fas fa-info-circle fa-fw"></i></div>
					<div id="alert" class="alert__close">
						<a id="alert_close" class="fas fa-times alert__close-icon"></a>
					</div>
					<p class="alert__title"><?php echo lang('updates'); ?><br>
						
						<?php 
						$count=count($updates);
						echo sprintf(lang('multi_updates'), $count); 
						?>
						<a id="show_me"><?php echo lang('show'); ?></a>
					</p>
				</div>
			<?php endif; ?>

			


			<div class="form-btns form-btns-top">
				<div class="title-bar js-filters-collapsable title-bar--large">
					<div class="filter-bar">
						<div class="filter-bar__item ">
							<div class="filter-search-bar__item ">
								<button id = "current_status" type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="status" title="status"><?php echo lang('filter_by_status'); ?></button>
								<div class="dropdown">
									<div class="dropdown__scroll">
										<a class="dropdown__link" id="installed_sort"><?php echo lang('installed'); ?></a>
										<a class="dropdown__link" id="uninstalled_sort"><?php echo lang('uninstalled'); ?></a>
										<a class="dropdown__link" id="update_sort"><?php echo lang('update_available'); ?></a>
										<a class="dropdown__link" id="show_all"><?php echo lang('all_addons'); ?></a>
									</div>
								</div>
							</div>
						</div>
						<div class="filter-bar__item filter-search-form">
							<div class="filter-search-bar__item">
								<div class="search-input">
									<input id="search_term" class="search-input__input input--small" type="text" name="filter_by_keyword" value="" placeholder="Search">
								</div>
							</div>
						</div>

						

					</div>
				</div>
			</div>
		</div>


		<form class="form-standard-unique">
			<div class="js-list-group-wrap">
				<table id="main_Table" cellspacing="0">
					<thead>
						<tr class="app-listing__row_h app-listing__row--head">
							<th class="column-sort-header">
								<a href="" class="column-sort column-sort--desc"><?php echo lang('name'); ?></a>
							</th>
							<th class="column-sort-header">
								<a href="" class="column-sort column-sort--desc"><?php echo lang('version'); ?></a>
							</th>
							<th class="column-sort-header">
								<a class="column-sort column-sort--desc"><?php echo lang('manage'); ?></a>
							</th>
							<th class="app-listing__header text--center">
								<label><?php echo lang('uninstall'); ?> </label>
							</th>

							<th class="app-listing__header text--center">
								<label ><?php echo lang('select_all'); ?></label>
								<input  class="input--no-mrg" type="checkbox" title="Select All">
							</th>
						</tr>
					</thead>

					<tbody class="list-group">
						<?php $addons=$installed;
						foreach ($addons as $addon) : ?>

							<?php if (isset($addon['update'])) {
								echo '<tr  class="app-listing__row_update">';
							} else {
								echo '<tr  class="app-listing__row">';
							} ?>
							<td>
								<span class="collapsed-label"><?php echo lang('name'); ?></span>
								<a>
									<div class="addon-name-table d-flex align-items-center">
										<img src="<?=$addon["icon_url"] ?>" class="addon-icon-table">
										<div class="name_of_addon">
											<strong><?=$addon["name"] ?></strong><br><span class="meta-info text-muted"> <?=$addon["description"] ?> </span>
										</div>
									</div>
								</a>
							</td>
							<td>
								<span class="collapsed-label"><?php echo lang('version'); ?></span>
								<?=$addon["version"] ?>
							</td>
							<td class="manage">
								<span class="collapsed-label"><?php echo lang('manage'); ?></span>
								<div class="button-toolbar toolbar">
									<div class="button-group button-group-xsmall">

										<?php if (isset($addon['manual_url'])) : ?>
											<a href="<?=$addon['manual_url'] ?>" class="manual button button--default" <?php if ($addon['manual_external']) {
																															echo 'rel="external"';
																														} ?>></a>
										<?php endif; ?>

										<?php if (isset($addon['settings_url'])) : ?><a class="settings button button--default" href="<?=$addon['settings_url'] ?>" title=<?=lang('settings') ?>><span class="hidden"><?=lang('settings') ?></span></a> <?php endif; ?>

										<?php if (isset($addon['update'])) : ?> <a href="" data-post-url="<?=$addon['update_url'] ?>" class="button button--primary button--small">
												<?php echo sprintf(lang('update_to_version'), '<br />' . $addon['update']); ?>
											</a> <?php endif; ?>

									</div>
								</div>
							</td>


							<td class="app-listing__cell app-listing__cell--input text--center">
								<?php if (ee('Permission')->hasAll('can_admin_addons') && $addon['installed']) : ?>
									<a href="<?=$addon['remove_url'] ?>" class="button button--primary button--small" onclick="return confirm('<?php echo lang('confirm'); ?> <?=$addon['name'] ?>')"><?=lang('uninstall') ?></a>
								<?php endif; ?>
							</td>

							<td class="app-listing__cell app-listing__cell--input text--center">
								<div class="list-item__checkbox">
									<input name="selection[]" type="checkbox" value="<?=$addon["package"] ?>" data-confirm="<?=$addon["name"] ?>">
								</div>
							</td>


						<?php endforeach; ?>

						<?php $addons=$installed;
						foreach ($uninstalled as $addon) : ?>
							<tr class="app-listing__row_uninstalled">
								<td>
									<span class="collapsed-label"><?php echo lang('name'); ?></span>

									<div class="addon-name-table d-flex align-items-center">
										<img src="<?=$addon["icon_url"] ?>" class="addon-icon-table">
										<div class="name_of_addon">
											<strong><?=$addon["name"] ?></strong><br><span class="meta-info text-muted"><?=$addon["description"] ?></span>
										</div>
									</div>

								</td>
								<td>
									<span class="collapsed-label"><?php echo lang('version'); ?></span>
									<?=$addon["version"] ?>
								</td>
								<td>
									<span class="collapsed-label"><?php echo lang('manage'); ?></span>
									<a href="" data-post-url="<?=$addon['install_url'] ?>" class="button button--primary button--small"><?=lang('install') ?></a>
								</td>
								<td class="app-listing__cell app-listing__cell--input text--center">
									<p> -- </p>
								</td>

								<td class="app-listing__cell app-listing__cell--input text--center">
									<div class="list-item__checkbox">
										<input name="selection[]" type="checkbox" value="<?=$addon["package"] ?>" data-confirm="<?=$addon["name"] ?>">
									</div>
								</td>


							</tr>

						<?php endforeach; ?>



					</tbody>

				</table>

			</div>
			<?php $this->embed('ee:_shared/form/bulk-action-bar', [
				'options'=> [
					[
						'value'=> "",
						'text'=> '-- ' . lang('with_selected') . ' --'
					],
					[
						'value'=> "remove",
						'text'=> lang('delete'),
						'attrs'=> 'data-confirm-trigger="selected" rel="modal-confirm-remove"'
					],
					[
						'value'=> "update",
						'text'=> lang('update'),
						'attrs'=> 'data-confirm-trigger="selected" rel="modal-confirm-update"'

					],
					[
						'value'=> "install",
						'text'=> lang('install'),
						'attrs'=> 'data-confirm-trigger="selected" rel="modal-confirm-install"'

					]
				],
				'modal'=> true
			]); ?>
		</form>
	</div>

</div>

<?php

$modal_vars_install=array(
	'name'=> 'modal-confirm-install',
	'form_url'=> $form_url,
	'title'=> lang('bulk_install_title'),
	'alert'=> lang('bulk_install'),
	'noTrash'=> true,
	'button'=> [
		'text'=> lang('bulk_install_button'),
		'working'=> lang('bulk_install_button')
	],
	'hidden'=> array(
		'bulk_action'=> 'install'
	)
);

$modal_in=$this->make('ee:_shared/modal_confirm_remove')->render($modal_vars_install);
ee('CP/Modal')->addModal('install', $modal_in);
?>


<?php

$modal_vars_update=array(
	'name'=> 'modal-confirm-update',
	'form_url'=> $form_url,
	'title'=> lang('bulk_update_title'),
	'alert'=> lang('bulk_update'),
	'noTrash'=> true,
	'button'=> [
		'text'=> lang('bulk_update_button'),
		'working'=> lang('bulk_update_button')
	],
	'hidden'=> array(
		'bulk_action'=> 'update'
	)
);

$modal_up=$this->make('ee:_shared/modal_confirm_remove')->render($modal_vars_update);
ee('CP/Modal')->addModal('update', $modal_up);
?>


<?php

$modal_vars=array(
	'name'=> 'modal-confirm-remove',
	'form_url'=> $form_url,
	'title'=> lang('confirm_uninstall'),
	'alert'=> lang('confirm_uninstall_desc'),
	'button'=> [
		'text'=> lang('btn_confirm_and_uninstall'),
		'working'=> lang('btn_confirm_and_uninstall_working')
	],
	'hidden'=> array(
		'bulk_action'=> 'remove'
	)
);

$modal=$this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>