<div class="panel">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
    <div class="panel-heading">
      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
      		<h3 class="title-bar__title"><?=lang('metaweblog_settings')?></h3>
          <div class="title-bar__extra-tools">
            <a class="button button--primary" href="<?=ee('CP/URL')->make('addons/settings/metaweblog_api/create')?>"><?=lang('create_new')?></a>
          </div>
        </div>
      </div>
    </div>

		<?=ee('CP/Alert')->get('metaweblog-form')?>

		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php $this->embed('ee:_shared/form/bulk-action-bar', [
		    'options' => [
		        [
		            'value' => "",
		            'text' => '-- ' . lang('with_selected') . ' --'
		        ],
		        [
		            'value' => "remove",
		            'text' => lang('delete'),
		            'attrs' => ' data-confirm-trigger="selected" rel="modal-confirm-remove"'
		        ]
		    ],
		    'modal' => true
		]); ?>
	<?=form_close();?>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => ee('CP/URL')->make('addons/settings/metaweblog_api/remove'),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
