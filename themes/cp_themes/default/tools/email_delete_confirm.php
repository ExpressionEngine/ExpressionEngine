<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">
	
		<div class="heading">
			<h2 class="edit"><?=lang('delete_confirm')?></h2>
		</div>
		<div class="pageContents">

			<?=form_open('C=tools_communicate'.AMP.'M=delete_emails', '', $hidden)?>
			
			<p class="notice"><?=lang('delete_question')?></p>
			
			<p>
			<?php foreach ($emails as $email): ?>
			<?=$email?><br />
			<?php endforeach; ?>
			</p>
			
			<p><?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?></p>

			<?=form_close()?>

		</div>
	</div><!-- contents -->
</div><!-- mainContent -->


<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}


/* End of file email_delete_confirm.php */
/* Location: ./themes/cp_themes/default/tools/email_delete_confirm.php */