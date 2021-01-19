<?php $this->extend('_templates/default-nav'); ?>

<div class="panel">
  <?=form_open($table['base_url'])?>

    <div class="panel-heading">
      <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
      <div class="form-btns form-btns-top">
        <div class="title-bar title-bar--large">
          <h3 class="title-bar__title"><?=$cp_page_title?></h3>
          <?php if ($can_create_categories):?>
          <div class="title-bar__extra-tools">
            <a class="button button--primary" href="<?=ee('CP/URL')->make('categories/groups/create')?>"><?=lang('create_new')?></a>
          </div>
          <?php endif; ?>
      		<?php if (isset($filters)) {
    echo $filters;
} ?>
        </div>
      </div>
    </div>

		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ($can_delete_categories): ?>
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
	</form>
</div>


<?php

$modal_vars = array(
    'name' => 'modal-confirm-remove',
    'form_url' => ee('CP/URL')->make('categories/remove', ee()->cp->get_url_state()),
    'hidden' => array(
        'bulk_action' => 'remove'
    )
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
