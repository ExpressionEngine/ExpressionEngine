<?php extend_template('default-nav') ?>

<div class="tbl-ctrls">
	<?=form_open('')?>
		<h1><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h1>
		<?php $this->view('_shared/alerts')?>
		<section class="item-wrap email">
			<div class="item">
				<ul class="toolbar">
					<li class="remove"><a href="<?=cp_url('logs/email/delete/'.$email->cache_id)?>" title="remove"></a></li>
				</ul>
				<h3><b>Date:</b> <?=ee()->localize->human_time($email->cache_date)?><br><b>From:</b> <a href="<?=cp_url('myaccount', array('id' => $email->member_id))?>"><?=$email->member_name?></a><br><b>To:</b> <?=$email->recipient_name?><br><b>Subject:</b> <?=$email->subject?></h3>
				<div class="message">
					<?=$email->message?>
				</div>
			</div>
		</section>
	<?=form_close()?>
</div>