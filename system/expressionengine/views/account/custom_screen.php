<?php $this->load->view('account/_account_header');?>

	<div>
		<?=form_open($action, '', $form_hidden)?>
			<?=$content?>
		<?=form_close()?>
	</div>

<?php $this->load->view('account/_account_footer');