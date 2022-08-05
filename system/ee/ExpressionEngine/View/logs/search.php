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
		<?php if (count($logs) == 0): ?>
			<p class="no-results"><?=lang('no_search_logs_found')?></p>
		<?php else: ?>
			<div class="list-group">
			<?php foreach ($logs as $log): ?>

			<div class="list-item">
				<div class="list-item__content">
					<a href="" class="m-link float-right button button--default button--small" rel="modal-confirm-<?=$log->id?>" title="<?=lang('delete')?>"><i class="fal fa-trash-alt"><span class="hidden"><?=lang('delete')?></span></i></a>
					<div style="margin-bottom: 20px;">
						<b><?=lang('date_logged')?>:</b> <?=$localize->human_time($log->search_date)?>,
						<b><?=lang('site')?>:</b> <?=$log->getSite()->site_label?><br>
						<b><?=lang('username')?>:</b>
						<?php if ($log->member_id == 0): ?>
							--
						<?php else: ?>
							<a href="<?=ee('CP/URL')->make('myaccount', array('id' => $log->member_id))?>"><?=$log->screen_name?></a>,
						<?php endif; ?>
						<b><abbr title="<?=lang('internet_protocol')?>"><?=lang('ip')?></abbr>:</b> <?=$log->ip_address?>
					</div>
					<div class="list-item__body">
						<pre><code><?=lang('searched_for')?> "<b><?=$log->search_terms?></b>" <?=lang('in')?> <b><?=$log->search_type?></b></pre></code>
					</div>
				</div>
			</div>

			<?php endforeach; ?>
			</div>

			<?=$pagination?>
      </div>
      <div class="panel-footer">
  			<fieldset class="bulk-action-bar">
  				<button class="button button--danger m-link" rel="modal-confirm-all"><?=lang('clear_search_logs')?></button>
  			</fieldset>
      </div>
		<?php endif; ?>
<?=form_close()?>
</div>
</div>
<?php
// Individual confirm delete modals
foreach ($logs as $log) {
    $modal_vars = array(
        'name' => 'modal-confirm-' . $log->id,
        'form_url' => $form_url,
        'hidden' => array(
            'delete' => $log->id
        ),
        'checklist' => array(
            array(
                'kind' => lang('view_search_log'),
                'desc' => lang('searched_for') . ' "' . $log->search_terms . '" ' . lang('in') . ' ' . $log->search_type
            )
        )
    );

    $modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
    ee('CP/Modal')->addModal($log->id, $modal);
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
            'kind' => lang('view_search_log'),
            'desc' => lang('all')
        )
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('all', $modal);
?>
