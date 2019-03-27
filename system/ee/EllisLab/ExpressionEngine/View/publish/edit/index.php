<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<header class="section-header">
			<div class="section-header__title"><?=$cp_heading?></div>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
			<?php if (isset($filters)) echo $filters; ?>
		</header>
		<div class="filter-bar filter-bar--listing">
			<div class="filter-item">
				<a href="#" class="filter-item__link filter-item__link--toggle js-toggle-link" rel="toggle-columns">
					<span class="icon--columns icon--small"></span>
					Columns
				</a>
			</div>
		</div>
		<div class="app-toggle" rev="toggle-columns">
			<?php
			$component = [
				'items' => $available_columns,
				'selected' => $selected_columns,
				'multi' => TRUE,
				'filter_url' => NULL,
				'limit' => 100,
				'no_results' => 'no_results',
				'no_related' => 'no_related',
				'select_filters' => []
			];
			?>
			<div class="field-control">
				<div data-relationship-react="<?=base64_encode(json_encode($component))?>" data-input-value="columns">
					<div class="fields-select">
						<div class="field-inputs">
							<label class="field-loading">
								<?=lang('loading')?><span></span>
							</label>
						</div>
					</div>
				</div>
			</div>
			<!-- only shows when non default columns are checked -->
			<div class="filter-bar">
				<div class="filter-item">
					<a href="#" class="filter-item__link filter-item__link--clear">
						<span class="icon--close"></span>
						Reset
					</a>
				</div>
			</div>
		</div>

		<div id="edit-table">
			<?php $this->embed('publish/partials/edit_list_table'); ?>
		</div>

	<?=form_close()?>
</div>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-entry',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-entry', $modal);

$modal = $this->make('ee:_shared/modal-bulk-edit')->render([
	'name' => 'modal-bulk-edit'
]);
ee('CP/Modal')->addModal('bulk-edit', $modal);

$modal = ee('View')->make('ee:_shared/modal-form')->render([
	'name' => 'modal-form',
	'contents' => ''
]);
ee('CP/Modal')->addModal('modal-form', $modal);
?>
