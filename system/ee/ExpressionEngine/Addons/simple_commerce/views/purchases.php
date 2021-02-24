<div class="panel">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>

    <div class="panel-heading">
      <div class="app-notice-wrap">
  			<?=ee('CP/Alert')->get('purchases-table')?>
  		</div>
      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
      		<h3 class="title-bar__title"><?=lang('all_purchases')?>
      			<ul class="toolbar">
      				<li class="download"><a href="<?=ee('CP/URL')->make('addons/settings/simple_commerce/export_purchases')?>" title="<?=lang('export_purchases')?>"></a></li>
      			</ul>
      		</h3>
          <div class="title-bar__extra-tools">
            <a class="button button--primary" href="<?=ee('CP/URL')->make('addons/settings/simple_commerce/create-purchase')?>"><?=lang('create_new')?></a>
          </div>
        </div>
      </div>
    </div>
		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if (! empty($table['columns']) && ! empty($table['data'])): ?>
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
		<?php endif; ?>
	<?=form_close();?>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => ee('CP/URL')->make('addons/settings/simple_commerce/remove-purchase'),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
