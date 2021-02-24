<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>
<div class="panel">
	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
      <div class="panel-heading">
        <div class="form-btns form-btns-top">
          <div class="title-bar title-bar--large">
      			<h3 class="title-bar__title"><?=$heading['user']?></h3>
            <div class="title-bar__extra-tools">
      				<a class="button button--primary" href="<?=$create_url?>"><?=lang('new')?></a>
            </div>
          </div>
        </div>
      </div>
      <div class="panel-body">
			<div class="app-notice-wrap"><?=ee('CP/Alert')->get('user-alerts')?></div>
			<?php $this->embed('_shared/table-list', ['data' => $requests['user'], 'filters' => $filters['user']]); ?>
			<?php $this->embed('ee:_shared/form/bulk-action-bar', [
			    'options' => [
			        [
			            'value' => "",
			            'text' => '-- ' . lang('with_selected') . ' --'
			        ],
			        [
			            'value' => "remove",
			            'text' => lang('delete'),
			            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-delete"'
			        ]
			    ],
			    'modal' => true
			]); ?>
      </div>
		</form>
	</div>
</div>

<?php if (! empty($requests['app'])) : ?>
<div class="panel">
	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
      <div class="panel-heading">
        <div class="form-btns form-btns-top">
          <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?=$heading['app']?></h3>
          </div>
        </div>
      </div>
      <div class="panel-body">
  			<div class="app-notice-wrap">
  				<?=ee('CP/Alert')->get('app-alerts')?>
  				<?=ee('CP/Alert')->get('no-cookie-consent')?>
  			</div>
  			<?php $this->embed('_shared/table-list', ['data' => $requests['app'], 'filters' => $filters['app']]); ?>
      </div>
		</form>
	</div>
</div>
<?php endif; ?>

<?php

$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => ee('CP/URL')->make('settings/consents', ee()->cp->get_url_state()),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
