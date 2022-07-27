<?php $this->extend('_templates/default-nav') ?>
<div class="panel">
<div class="tbl-ctrls">
<?=form_open($form_url)?>

  <div class="panel-heading">
  	<div class="title-bar">
  		<h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
  		<?php if (isset($filters)) {
    echo $filters;
} ?>
  	</div>
  </div>
  <div class="panel-body">
    <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
    <section>
  		<?php if (empty($rows)): ?>
  			<p class="no-results"><?=lang('no_developer_logs_found')?></p>
  		<?php else: ?>
  			<div class="list-group">
  			<?php foreach ($rows as $row): ?>
  			<div class="list-item">
  				<div class="list-item__content">
  					<a href="" class="m-link button button--default button--small float-right" rel="modal-confirm-<?=$row['log_id']?>" title="<?=lang('delete')?>"><i class="fal fa-trash-alt"><span class="hidden"><?=lang('delete')?></span></i></a>

  					<div style="margin-bottom: 20px;"><b><?=lang('date_logged')?>:</b> <?=$row['timestamp']?></div>
  					<div class="list-item__body">
  						<pre><code><?=$row['description']?></pre></code>
  					</div>
  				</div>
  			</div>
  			<?php endforeach; ?>
  			</div>

  			<?=$pagination?>
      </section>
    </div>
    <div class="panel-footer">
      <div class="form-btns">
        <button class="button button--danger m-link" rel="modal-confirm-all"><?=lang('clear_developer_logs')?></button>
      </div>
    </div>
		<?php endif; ?>

<?=form_close()?>
</div>
</div>

<?php
// Individual confirm delete modals
foreach ($rows as $row) {
    $modal_vars = array(
        'name' => 'modal-confirm-' . $row['log_id'],
        'form_url' => $form_url,
        'hidden' => array(
            'delete' => $row['log_id']
        ),
        'checklist' => array(
            array(
                'kind' => lang('view_developer_log'),
                'desc' => $row['description']
            )
        )
    );

    $modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
    ee('CP/Modal')->addModal($row['log_id'], $modal);
}

// Confirm delete all modal
$modal_vars = array(
    'name' => 'modal-confirm-all',
    'form_url' => $form_url,
    'hidden' => array(
        'delete' => 'all'
    ),
    'checklist' => array(
        array(
            'kind' => lang('view_developer_log'),
            'desc' => lang('all')
        )
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('all', $modal);
?>
