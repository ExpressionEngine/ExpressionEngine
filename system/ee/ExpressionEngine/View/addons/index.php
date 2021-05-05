<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel panel__no-main-title">
  <div class="tbl-ctrls">
    <form>
      <div class="panel-heading">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
        <div class="alert">
          <div class="alert__icon"><i class="fas fa-info-circle fa-fw"></i></div>
          <div class="alert__content">
              <p class="alert__title">Updates Available</p>
              <p>There are <strong>2</strong> installed add-ons with updates available. <a href="">Show me...</a></p>
          </div>
          <a href="" class="alert__close">
              <i class="fas fa-times alert__close-icon"></i>
          </a>
      </div>
        <div class="form-btns form-btns-top">
          <div class="title-bar js-filters-collapsable title-bar--large">
            <h3 class="title-bar__title">Add-Ons</h3>
            <div class="filter-bar">
    					<div class="filter-bar__item ">
        				<div class="filter-search-bar__item ">
                	<button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="status" title="status">status</button>
                	<div class="dropdown">
                    <div class="dropdown__scroll">
					            <a class="dropdown__link" href="">Installed</a>
                      <a class="dropdown__link" href="">Uninstalled</a>
                      <a class="dropdown__link" href="">Update Available</a>
            				</div>
                	</div>
              	</div>
        			</div>
    					<div class="filter-bar__item filter-search-form">
        				<div class="filter-search-bar__item">
                	<div class="search-input">
                		<input class="search-input__input input--small" type="text" name="filter_by_keyword" value="" placeholder="Search">
                	</div>
                </div>
        			</div>
    					<div class="filter-bar__item ">
        				<div class="filter-search-bar__item">
                	<button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="show">show				<span class="faded">(25)</span>
            			</button>
                	<div class="dropdown">
            				<div class="dropdown__search">
                			<div class="search-input">
                  			<input type="text" name="perpage" value="" placeholder="custom limit" data-threshold="1000" data-threshold-text="Viewing more than 1000 items at a time may result in reduced performance." class="search-input__input input--small">
                			</div>
                		</div>
										<a class="dropdown__link" href="">25 results</a>
										<a class="dropdown__link" href="">50 results</a>
										<a class="dropdown__link" href="">75 results</a>
										<a class="dropdown__link" href="">100 results</a>
										<a class="dropdown__link" href="">150 results</a>
										<a class="dropdown__link" href="">All Add-Ons</a>
      						</div>
                </div>
        			</div>
          	</div>
          </div>
        </div>
      </div>
      <div class="table-responsive table-responsive--collapsible">
        <table cellspacing="0">
  				<thead>
      			<tr class="app-listing__row app-listing__row--head">
							<th class="column-sort-header column-sort-header--active">
								<a href="" class="column-sort column-sort--desc">Name</a>
							</th>
							<th class="column-sort-header">
								<a href="" class="column-sort column-sort--desc">Version</a>
							</th>
							<th class="column-sort-header">
								<a class="column-sort column-sort--desc">Manage</a>
							</th>
							<th class="app-listing__header text--center">
								<label for="tbl_6092f7c1be4f4-select-all" class="hidden">Select All</label>
								<input id="tbl_6092f7c1be4f4-select-all" class="input--no-mrg" type="checkbox" title="Select All">
							</th>
						</tr>
      		</thead>
      		<tbody>
						<tr class="app-listing__row">
              <td>
                <span class="collapsed-label">Name</span>
                <a href="">
                  <div class="addon-name-table d-flex align-items-center">
                    <img src="http://expressionengine.test/themes/ee/asset/img/default-addon-icon.svg" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong>Add-On Name</strong><br><span class="meta-info text-muted">This is a description of the add-on.</span>
                    </div>
                  </div>
                </a>
              </td>
              <td>
                <span class="collapsed-label">Version</span>
                2.1.0
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <div class="button-toolbar toolbar">
                  <div class="button-group button-group-xsmall">
          	    		<a class="manual button button--default" href="" title="Manual"><span class="hidden">Manual</span></a>
          	    		<a class="settings button button--default" href="admin.php?/cp/channels/layouts/3" title="Settings"><span class="hidden">Settings</span></a>
                    <a class="button button--default" href="" title="Update">Update</a>
              	  </div>
                </div>
              </td>
              <td class="app-listing__cell app-listing__cell--input text--center">
								<label class="hidden" for="tbl_6092f7c1be4f4-2-0">Select Row</label>
								<input id="tbl_6092f7c1be4f4-2-0" class="input--no-mrg" name="selection[]" value="1" data-confirm="" type="checkbox">
							</td>
						</tr>
            <tr class="app-listing__row">
              <td>
                <span class="collapsed-label">Name</span>
                <a href="">
                  <div class="addon-name-table d-flex align-items-center">
                    <img src="http://expressionengine.test/themes/ee/asset/img/default-addon-icon.svg" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong>Add-On Name</strong><br><span class="meta-info text-muted">This is a description of the add-on.</span>
                    </div>
                  </div>
                </a>
              </td>
              <td>
                <span class="collapsed-label">Version</span>
                2.1.0
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <div class="button-toolbar toolbar">
                  <div class="button-group button-group-xsmall">
          	    		<a class="manual button button--default" href="" title="Manual"><span class="hidden">Manual</span></a>
          	    		<a class="settings button button--default" href="admin.php?/cp/channels/layouts/3" title="Settings"><span class="hidden">Settings</span></a>
                    <a class="button button--default" href="" title="Update">Update</a>
              	  </div>
                </div>
              </td>
              <td class="app-listing__cell app-listing__cell--input text--center">
								<label class="hidden" for="tbl_6092f7c1be4f4-2-0">Select Row</label>
								<input id="tbl_6092f7c1be4f4-2-0" class="input--no-mrg" name="selection[]" value="1" data-confirm="" type="checkbox">
							</td>
						</tr>
            <tr class="app-listing__row">
              <td>
                <span class="collapsed-label">Name</span>
                <a href="">
                  <div class="addon-name-table d-flex align-items-center">
                    <img src="http://expressionengine.test/themes/ee/asset/img/default-addon-icon.svg" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong>Add-On Name</strong><br><span class="meta-info text-muted">This is a description of the add-on.</span>
                    </div>
                  </div>
                </a>
              </td>
              <td>
                <span class="collapsed-label">Version</span>
                2.1.0
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <div class="button-toolbar toolbar">
                  <div class="button-group button-group-xsmall">
          	    		<a class="manual button button--default" href="" title="Manual"><span class="hidden">Manual</span></a>
          	    		<a class="settings button button--default" href="admin.php?/cp/channels/layouts/3" title="Settings"><span class="hidden">Settings</span></a>
              	  </div>
                </div>
              </td>
              <td class="app-listing__cell app-listing__cell--input text--center">
								<label class="hidden" for="tbl_6092f7c1be4f4-2-0">Select Row</label>
								<input id="tbl_6092f7c1be4f4-2-0" class="input--no-mrg" name="selection[]" value="1" data-confirm="" type="checkbox">
							</td>
						</tr>
            <tr class="app-listing__row">
              <td>
                <span class="collapsed-label">Name</span>
                <a href="">
                  <div class="addon-name-table d-flex align-items-center">
                    <img src="http://expressionengine.test/themes/ee/asset/img/default-addon-icon.svg" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong>Add-On Name</strong><br><span class="meta-info text-muted">This is a description of the add-on.</span>
                    </div>
                  </div>
                </a>
              </td>
              <td>
                <span class="collapsed-label">Version</span>
                2.1.0
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <div class="button-toolbar toolbar">
                  <div class="button-group button-group-xsmall">
          	    		<a class="manual button button--default" href="" title="Manual"><span class="hidden">Manual</span></a>
          	    		<a class="settings button button--default" href="admin.php?/cp/channels/layouts/3" title="Settings"><span class="hidden">Settings</span></a>
              	  </div>
                </div>
              </td>
              <td class="app-listing__cell app-listing__cell--input text--center">
								<label class="hidden" for="tbl_6092f7c1be4f4-2-0">Select Row</label>
								<input id="tbl_6092f7c1be4f4-2-0" class="input--no-mrg" name="selection[]" value="1" data-confirm="" type="checkbox">
							</td>
						</tr>
            <tr class="app-listing__row">
              <td>
                <span class="collapsed-label">Name</span>
                <!-- <a href=""> -->
                  <div class="addon-name-table d-flex align-items-center">
                    <img src="http://expressionengine.test/themes/ee/asset/img/default-addon-icon.svg" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong>Add-On Name</strong><br><span class="meta-info text-muted">This is a description of the add-on.</span>
                    </div>
                  </div>
                <!-- </a> -->
              </td>
              <td>
                <span class="collapsed-label">Version</span>
                2.1.0
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <a href="" class="button button--primary button--xsmall">Install</a>
              </td>
              <td class="app-listing__cell app-listing__cell--input text--center">
								<label class="hidden" for="tbl_6092f7c1be4f4-2-0">Select Row</label>
								<input id="tbl_6092f7c1be4f4-2-0" class="input--no-mrg" name="selection[]" value="1" data-confirm="" type="checkbox">
							</td>
						</tr>
            <tr class="app-listing__row">
              <td>
                <span class="collapsed-label">Name</span>
                <!-- <a href=""> -->
                  <div class="addon-name-table d-flex align-items-center">
                    <img src="http://expressionengine.test/themes/ee/asset/img/default-addon-icon.svg" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong>Add-On Name</strong><br><span class="meta-info text-muted">This is a description of the add-on.</span>
                    </div>
                  </div>
                <!-- </a> -->
              </td>
              <td>
                <span class="collapsed-label">Version</span>
                2.1.0
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <a href="" class="button button--primary button--xsmall">Install</a>
              </td>
              <td class="app-listing__cell app-listing__cell--input text--center">
								<label class="hidden" for="tbl_6092f7c1be4f4-2-0">Select Row</label>
								<input id="tbl_6092f7c1be4f4-2-0" class="input--no-mrg" name="selection[]" value="1" data-confirm="" type="checkbox">
							</td>
						</tr>
					</tbody>
      	</table>
      </div>
    </form>
  </div>
</div>














<!-- Below is the old add-ons layout -->

<div class="panel">
  <div class="panel-body">

<div class="tab-wrap">
	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

<div class="tab-bar">
	<div class="tab-bar__tabs">
		<button type="button" class="tab-bar__tab active js-tab-button" rel="t-0"><?=lang('installed')?></button>
		<button type="button" class="tab-bar__tab js-tab-button" rel="t-2">
			<?=lang('updates')?>

			<?php if (! empty($updates)) : ?>
			<span class="tab-bar__tab-notification"><?=count($updates)?></span>
			<?php endif; ?>
		</button>
	</div>
</div>

<div class="tab t-0 tab-open">

	<div class="add-on-card-list">
		<?php $addons = $installed; foreach ($addons as $addon): ?>
			<?php $this->embed('_shared/add-on-card', ['addon' => $addon, 'show_updates' => false]); ?>
		<?php endforeach; ?>
	</div>

	<?php if (count($uninstalled)): ?>
		<h4 class="line-heading"><?=lang('uninstalled')?></h4>
		<hr>

		<div class="add-on-card-list">
			<?php foreach ($uninstalled as $addon): ?>
				<?php $this->embed('_shared/add-on-card', ['addon' => $addon, 'show_updates' => false]); ?>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</div>

<div class="tab t-2">
	<div class="add-on-card-list">
		<?php foreach ($updates as $addon): ?>
			<?php $this->embed('_shared/add-on-card', ['addon' => $addon, 'show_updates' => true]); ?>
		<?php endforeach; ?>
	</div>
</div>

</div>

</div>
</div>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => $form_url,
    'title' => lang('confirm_uninstall'),
    'alert' => lang('confirm_uninstall_desc'),
    'button' => [
        'text' => lang('btn_confirm_and_uninstall'),
        'working' => lang('btn_confirm_and_uninstall_working')
    ],
    'hidden' => array(

    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
