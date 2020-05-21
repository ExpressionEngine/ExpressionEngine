<?php /*
<!-- <div class="filter-item" style="float:right;">
				<a href="#" class="filter-item__link filter-item__link--save js-modal-link--side" rel="modal-filter-settings">
					<span class="icon--save"></span>
					Save
				</a>
			</div> -->
<!-- 			<div class="filter-bar__item" style="float:right;">
				<a href="#" class="has-sub filter-bar__button js-dropdown-toggle js-toggle-link" rel="toggle-columns">
					<span class="icon--columns icon--small"></span>
					Columns
				</a>
			</div> -->

			<!-- TODO: only show when user has permissions for changing views -->
			<!-- View Selector -->
			<!-- <div class="filter-item" style="float:right;">
				<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">
					Views <?php if ($selected_view): ?><span class="faded">(<?=$selected_view->name;?>)</span><?php endif; ?>
				</a>
				<div class="filter-submenu">
					<div class="filter-submenu__search">
						<form>
							<input type="text" placeholder="Filter Views">
						</form>
					</div>
					<div class="filter-submenu__selected">
						<?php if ($selected_view): ?>
							<a href="<?=ee('CP/URL')->make('publish/edit');?>"><?=$selected_view->name;?></a>
						<?php endif;?>
					</div>
					<div class="filter-submenu__scroll">
						<?php foreach($available_views as $view):
						// Don't show the selected item in the available views because it's shown above
						if ($selected_view && $selected_view->view_id === $view->view_id) { continue; }
						?>
							<a href="<?=ee('CP/URL')->make('publish/edit', array('view' => $view->view_id));?>" class="filter-submenu__link">
								<?=$view->name?>
								<span class="icon--delete icon--float-right js-modal-link js-modal--destruct" rel="modal-list-view-remove"></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div> -->
*/ ?>
<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<div id="edit-table">
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar js-filters-collapsible">
			<h2 class="title-bar__title"><?=$cp_heading?></h2>
			<?php if (isset($filters)) echo $filters; ?>
		</div>


		<?php $this->embed('_shared/table', $table); ?>

		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php if ($can_edit || $can_delete) {
				$options = [
					[
						'value' => "",
						'text' => '-- ' . lang('with_selected') . ' --'
					]
				];
				if ($can_delete) {
					$options[] = [
						'value' => "remove",
						'text' => lang('delete'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete-entry"'
					];
				}
				if ($can_edit) {
					$options[] = [
						'value' => "edit",
						'text' => lang('edit'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-edit"'
					];
					$options[] = [
						'value' => "bulk-edit",
						'text' => lang('bulk_edit'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-bulk-edit"'
					];
					$options[] = [
						'value' => "add-categories",
						'text' => lang('add_categories'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-bulk-edit"'
					];
					$options[] = [
						'value' => "remove-categories",
						'text' => lang('remove_categories'),
						'attrs' => ' data-confirm-trigger="selected" rel="modal-bulk-edit"'
					];
				}
				$this->embed('ee:_shared/form/bulk-action-bar', [
					'options' => $options,
					'modal' => true
				]);
			}
			?>
		<?php endif; ?>
		</div>
	<?=form_close()?>
</div>
