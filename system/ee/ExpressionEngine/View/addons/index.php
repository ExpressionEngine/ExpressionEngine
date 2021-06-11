<?php $this->extend('_templates/default-nav', array(), 'outer_box'); ?>

<div class="panel panel__no-main-title">
  <div class="tbl-ctrls">
       <form>
      <div class="panel-heading">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

        
          
            

            <?php if (! empty($updates) && count($updates) == 1 ) : ?>
              <div id = "alert_banner" class="alert">
              <div class="alert__icon"><i class="fas fa-info-circle fa-fw"></i></div>
              <?php
              echo '<div id = "alert" href="" class="alert__close">';
              echo '<a onclick="hide()" id = "alert_close" class="fas fa-times alert__close-icon"></a>';
              echo '</div>';
              ?>
                <p class="alert__title">Update Available <br> 
                There is <strong><?=count($updates)?></strong> installed add-on with updates available. <a onclick = "typeFilter('app-listing__row_update')">Show me...</a></p>
                <p></p>
              </div>
            <?php endif; ?>

            <?php if (! empty($updates) && count($updates) > 1 ) : ?>
              <div id = "alert_banner" class="alert">
              <div class="alert__icon"><i class="fas fa-info-circle fa-fw"></i></div>
              <?php
              echo '<div id = "alert" href="" class="alert__close">';
              echo '<a onclick="hide()" id = "alert_close" class="fas fa-times alert__close-icon"></a>';
              echo '</div>';
              ?>
                <p class="alert__title">Updates Available <br> 
                There are <strong><?=count($updates)?></strong> installed add-on with updates available. <a onclick = "typeFilter('app-listing__row_update')">Show me...</a></p>
                <p></p>
              </div>
            <?php endif; ?>

            <script> 
              function hide() { 
                document.getElementById("alert_banner").style.display='none'; 
                return false;
              }
            </script> 
            
          
        
        


        <div class="form-btns form-btns-top">
          <div class="title-bar js-filters-collapsable title-bar--large">
            <h3 class="title-bar__title">Add-Ons</h3>
            <div class="filter-bar">
    					<div class="filter-bar__item ">
        				<div class="filter-search-bar__item ">
                	<button type="button" class="has-sub filter-bar__button js-dropdown-toggle button button--default button--small" data-filter-label="status" title="status">status</button>
                	<div class="dropdown">
                    <div class="dropdown__scroll">
					            <a class="dropdown__link" onclick = "typeFilter('app-listing__row')" >Installed</a>
                      <a class="dropdown__link"  onclick = "typeFilter('app-listing__row_uninstalled')">Uninstalled</a>
                      <a class="dropdown__link"  onclick = "typeFilter('app-listing__row_update')">Update Available</a>
            				</div>
                	</div>
              	</div>
        			</div>
    					<div class="filter-bar__item filter-search-form">
        				<div class="filter-search-bar__item">
                	<div class="search-input">
                		<input id = "Search_Term" class="search-input__input input--small" type="text" name="filter_by_keyword" value="" placeholder="Search" onkeyup="searchFilter()">
                	</div>
                </div>
        			</div>

              <script>
                function searchFilter() {
                var input, filter, table, tr, td, i, txtValue;
                input = document.getElementById("Search_Term");
                filter = input.value.toUpperCase();
                table = document.getElementById("main_Table");
                tr = table.getElementsByTagName("tr");
                for (i = 0; i < tr.length; i++) {
                  td = tr[i].getElementsByTagName("strong")[0];
                  if (td) {
                    txtValue = td.textContent || td.innerText;
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                      tr[i].style.display = "";
                    } else {
                      tr[i].style.display = "none";
                    }
                  }       
                }
              }

              function typeFilter(input){
                var table, vr;
                table = document.getElementById("main_Table");
                tr = table.getElementsByTagName("tr");

                for (i = 0; i < tr.length; i++) {
                   if(tr[i].className == input){
                    tr[i].style.display = "";
                   }else {
                      tr[i].style.display = "none";
                   }
                }
                return false;
              }
              </script>



    					<div class="filter-bar__item ">

        			</div>
          	</div>
          </div>
        </div>
      </div>
      <div class="table-responsive table-responsive--collapsible">
        <table id = "main_Table" cellspacing="0">
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
								<label for="tbl_6092f7c1be4f4-select-all" >Uninstall </label>
								
							</th>
						</tr>
      		</thead>
           
      		<tbody>

           <?php $addons = $installed; foreach ($addons as $addon):?> 

            <?php if (isset($addon['update'])){
              echo '<tr  class="app-listing__row_update">';
            }             
            else{
              echo '<tr  class="app-listing__row">';
            }?>
              
             

              <td>
                <span class="collapsed-label">Name</span>
                <a href="">
                  <div class="addon-name-table d-flex align-items-center">
                    <img src="<?= $addon["icon_url"]?>" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong><?= $addon["name"] ?></strong><br><span class="meta-info text-muted"> <?= $addon["description"] ?> </span>
                    </div>
                  </div>
                </a>
              </td>
              <td>
                <span class="collapsed-label">Version</span>
                <?= $addon["version"] ?>
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <div class="button-toolbar toolbar">
                  <div class="button-group button-group-xsmall">

                  <?php if (isset($addon['manual_url'])) : ?>
			            <a href="<?= $addon['manual_url'] ?>" class="manual button button--default"<?php if ($addon['manual_external']) {
                  echo 'rel="external"';
                  } ?>></a>
		              <?php endif; ?>
                  
                  <?php if (isset($addon['settings_url'])): ?><a class="settings button button--default" href="<?= $addon['settings_url'] ?>" title="Settings"><span class="hidden">Settings</span></a> <?php endif; ?>
                   
                    <?php if (isset($addon['update'])): ?> <a href="" data-post-url="<?=$addon['update_url']?>" class="button button--primary button--small">
			                  <?php echo sprintf(lang('update_to_version'), '<br />' . $addon['update']); ?>
		                    </a> <?php endif; ?>

                    
              	  </div>
                </div>

              </td>


              <td class="app-listing__cell app-listing__cell--input text--center">
							      <?php if (ee('Permission')->hasAll('can_admin_addons') && $addon['installed']) : ?> 
                      <!-- <a class="dropdown__link dropdown__link--danger m-link" href="" rel="modal-confirm-remove" data-action-url="<?= $addon['remove_url'] ?>" data-confirm="<?= $addon['name'] ?>"><?= lang('uninstall') ?></a> -->
                      <a href="<?= $addon['remove_url'] ?>" class="button button--primary button--small" onclick= "return confirm('Are you sure you want to delete <?= $addon['name'] ?>')"><?= lang('uninstall') ?></a>
                    <?php endif; ?>
							</td>

						</tr>
			      
            <?php endforeach; ?>

            <?php $addons = $installed; foreach ($uninstalled as $addon):?> 
              <tr class="app-listing__row_uninstalled">
              <td>
                <span class="collapsed-label">Name</span>

                  <div class="addon-name-table d-flex align-items-center">
                    <img src="<?= $addon["icon_url"]?>" alt="Add-On Name" class="addon-icon-table">
                    <div>
                      <strong><?= $addon["name"] ?></strong><br><span class="meta-info text-muted"><?= $addon["description"] ?></span>
                    </div>
                  </div>

              </td>
              <td>
                <span class="collapsed-label">Version</span>
                <?= $addon["version"] ?>
              </td>
              <td>
                <span class="collapsed-label">Manage</span>
                <a href="" data-post-url="<?= $addon['install_url'] ?>" class="button button--primary button--small"><?= lang('install') ?></a>
              </td>
              <td class="app-listing__cell app-listing__cell--input text--center">
								<p> -- </p>
							</td>
						</tr>

            <?php endforeach; ?>

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






<!-- Below is the old add-ons layout -->




