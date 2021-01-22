<?php $this->extend('_templates/default-nav'); ?>

<div class="box panel">
  <div class="tbl-ctrls">
  <?=form_open($table['base_url'])?>
  <div class="panel-heading">
<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
    <div class="form-btns form-btns-top">
      <div class="title-bar title-bar--large">
        <h3 class="title-bar__title"><?=$cp_page_title?></h3>
        <div class="title-bar__extra-tools">
          <a class="button button--primary" href="<?=ee('CP/URL')->make('files/watermarks/create')?>"><?=lang('create_new')?></a>
        </div>
      </div>
    </div>
  </div>



		<?php $this->embed('_shared/table', $table); ?>
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
	</form>
</div>
</div>
<?php

$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => ee('CP/URL')->make('files/watermarks/remove', ee()->cp->get_url_state()),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
