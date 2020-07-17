<div class="panel">
  <div class="tbl-ctrls">
  	<?=form_open($form_url)?>
      <div class="panel-heading">
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

    		<div class="title-bar js-filters-collapsible">
    			<h3 class="title-bar__title"><?=$cp_heading?></h3>
    			<?php if (isset($filters)) echo $filters; ?>
    		</div>

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
  	<?=form_close()?>
  </div>
</div>
