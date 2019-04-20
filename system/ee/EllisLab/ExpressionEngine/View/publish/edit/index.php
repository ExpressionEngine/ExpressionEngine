<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>

		<h1><?=$cp_heading?></h1>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<!--  search  -->
		<div class="filter-bar filter-bar--search">

		</div>

		<div class="filter-bar filter-bar--listing">
		<div class="filter-item">
				<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Search In</a>
				<div class="filter-submenu">
					<div class="filter-submenu__selected">
						<a href="">Titles</a>
					</div>
					<div class="filter-submenu__scroll">
						<a href="" class="filter-submenu__link filter-submenu__link---active">Titles</a>
						<a href="" class="filter-submenu__link">Content</a>
						<a href="" class="filter-submenu__link">Titles & Content</a>
					</div>
				</div>
			</div>
			<div class="filter-item filter-item__search" style="display: flex;">
				<form>
					<input type="text" placeholder="Keyword Search" name="search">
					<input type="submit" value="GO">
				</form>
			</div>

			<div class="filter-item" style="float:right;">
				<a href="#" class="filter-item__link filter-item__link--save js-modal-link--side" rel="modal-filter-settings">
					<span class="icon--save"></span>
					Save
				</a>
			</div>
			<div class="filter-item" style="float:right;">
				<a href="#" class="filter-item__link filter-item__link--toggle js-toggle-link" rel="toggle-columns">
					<span class="icon--columns icon--small"></span>
					Columns
				</a>
			</div>


			<div class="filter-item" style="float:right;">
				<a href="#" class="filter-item__link filter-item__link--toggle js-toggle-link" rel="toggle-filters">
					<span class="icon--filters icon--small"></span>
					Filters
				</a>
			</div>


			<!-- TODO: only show when user has permissions for changing views -->
			<!-- View Selector -->
			<div class="filter-item" style="float:right;">
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
			</div>


			<!-- <?php if (isset($filters)) echo $filters; ?> -->

		</div>

		<!-- Filter Bar -->
		<div class="app-toggle app-more-filters" rev="toggle-filters">
			<div class="filter-bar--add" rev="add-filters">
				<div class="filter-bar">
					<div class="filter-item filter-item---active">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">
							<span class="icon--close js-filter-clear"></span>
							Channel <span class="faded">(Article)</span>
						</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Channels">
								</form>
							</div>
							<div class="filter-submenu__selected">
								<a href="">[Allowed Channel_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Channel Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu"><span class="icon--close js-filter-clear"></span>Category <span class="faded">(PHP)</span></a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Categories">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<div class="filter-submenu---loading">
									Searching<span></span>
								</div>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Author</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Authors">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Author Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Author_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Author]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Author]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu"><span class="icon--close js-filter-clear"></span>Status <span class="faded">(Open)</span></a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Statuses">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Status Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Status_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Status]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Status]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Date</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Custom Date" rel="date-picker">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Date Range]</a>
								<a href="" class="filter-submenu__link">[Allowed Date_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Date]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Date]</a>
							</div>
						</div>
					</div>
					<!-- only shows when 1 or more filters are active -->
					<div class="filter-item">
						<a href="#" class="filter-item__link filter-item__link--clear">
							<span class="icon--close"></span>
							Clear All
						</a>
					</div>
					<div class="filter-item filter-item--align-right">
						<a href="#" class="filter-item__link filter-item__link--add js-remove-link hidden">
							<span class="icon--add icon--remove"></span>
						</a>
						<a href="#" class="filter-item__link filter-item__link--add js-add-link">
							<span class="icon--add icon--small"></span>
						</a>
					</div>
				</div>
			</div>
			<div class="filter-bar--add" rev="add-filters">
				<div class="filter-bar">
					<div class="filter-item filter-item---active">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">
							<span class="icon--close js-filter-clear"></span>
							Channel <span class="faded">(Author)</span>
						</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Channels">
								</form>
							</div>
							<div class="filter-submenu__selected">
								<a href="">[Allowed Channel_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Channel Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Category</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Categories">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<div class="filter-submenu---loading">
									Searching<span></span>
								</div>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Author</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Authors">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Author Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Author_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Author]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Author]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu"><span class="icon--close js-filter-clear"></span>Status <span class="faded">(Closed)</span></a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Statuses">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Status Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Status_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Status]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Status]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Date</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Custom Date" rel="date-picker">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Date Range]</a>
								<a href="" class="filter-submenu__link">[Allowed Date_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Date]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Date]</a>
							</div>
						</div>
					</div>
					<!-- only shows when 1 or more filters are active -->
					<div class="filter-item">
						<a href="#" class="filter-item__link filter-item__link--clear">
							<span class="icon--close"></span>
							Clear All
						</a>
					</div>
					<div class="filter-item filter-item--align-right">
						<a href="#" class="filter-item__link filter-item__link--add js-remove-link hidden">
							<span class="icon--add icon--remove"></span>
						</a>
						<a href="#" class="filter-item__link filter-item__link--add js-add-link">
							<span class="icon--add icon--small"></span>
						</a>
					</div>
				</div>
			</div>
			<div class="filter-bar--add" rev="add-filters">
				<div class="filter-bar">
					<div class="filter-item filter-item---active">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">
							Channel
						</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Channels">
								</form>
							</div>
							<div class="filter-submenu__selected">
								<a href="">[Allowed Channel_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Channel Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Channel]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Category</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Categories">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<div class="filter-submenu---loading">
									Searching<span></span>
								</div>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Author</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Authors">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Author Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Author_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Author]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Author Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Author]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu"><span class="icon--close js-filter-clear"></span>Status <span class="faded">(Draft)</span></a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Filter Statuses">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Status Name]</a>
								<a href="" class="filter-submenu__link">[Allowed Status_name_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Status]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Status Name medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Status]</a>
							</div>
						</div>
					</div>
					<div class="filter-item">
						<a href="#" class="js-filter-link filter-item__link filter-item__link--has-submenu">Date</a>
						<div class="filter-submenu">
							<div class="filter-submenu__search">
								<form>
									<input type="text" placeholder="Custom Date" rel="date-picker">
								</form>
							</div>
							<div class="filter-submenu__scroll">
								<a href="" class="filter-submenu__link filter-submenu__link---active">[Allowed Date Range]</a>
								<a href="" class="filter-submenu__link">[Allowed Date_that_is_very_long_and_unbroken_it_breaks_everything]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Date]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range is a little long]</a>
								<a href="" class="filter-submenu__link">[Allowed Date Range medium]</a>
								<a href="" class="filter-submenu__link">[Allowed Date]</a>
							</div>
						</div>
					</div>
					<!-- only shows when 1 or more filters are active -->
					<div class="filter-item">
						<a href="#" class="filter-item__link filter-item__link--clear">
							<span class="icon--close"></span>
							Clear All
						</a>
					</div>
					<div class="filter-item filter-item--align-right">
						<a href="#" class="filter-item__link filter-item__link--add js-remove-link hidden">
							<span class="icon--add icon--remove"></span>
						</a>
						<a href="#" class="filter-item__link filter-item__link--add js-add-link">
							<span class="icon--add icon--small"></span>
						</a>
					</div>
				</div>
			</div>

		</div>

		<!-- Columns -->
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
