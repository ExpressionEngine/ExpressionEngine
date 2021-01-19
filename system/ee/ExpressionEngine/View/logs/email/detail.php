<?php $this->extend('_templates/default-nav') ?>
<div class="panel">
<div class="tbl-ctrls">
  <div class="panel-heading">
    <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
    <div class="form-btns form-btns-top">
      <div class="title-bar title-bar--large">
        <h3 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h3>
      </div>
    </div>
  </div>
  <div class="panel-body">
	<section class="item-wrap email">
		<div class="item">
			<ul class="toolbar">
				<li class="remove"><a href="" class="m-link" rel="modal-confirm-delete" title="<?=lang('delete')?>"></a></li>
			</ul>
			<h3><b>Date:</b> <?=$localize->human_time($email->cache_date)?><br><b>From:</b> <a href="<?=ee('CP/URL')->make('myaccount', array('id' => $email->member_id))?>"><?=$email->member_name?></a><br><b>To:</b> <?=$email->recipient_name?><br><b>Subject:</b> <?=$email->subject?></h3>
			<div class="message">
				<?=$email->message?>
			</div>
		</div>
	</section>
</div>
</div>
</div>

<?php
$modal_vars = array(
    'name' => 'modal-confirm-delete',
    'form_url' => ee('CP/URL')->make('logs/email'),
    'hidden' => array(
        'delete' => $email->cache_id
    ),
    'checklist' => array(
        array(
            'kind' => lang('view_email_logs'),
            'desc' => lang('sent_to') . ' ' . $email->recipient_name . ', ' . lang('subject') . ': ' . $email->subject
        )
    )
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
