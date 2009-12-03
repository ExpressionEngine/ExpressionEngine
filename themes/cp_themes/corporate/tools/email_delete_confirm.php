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
	
		<div class="heading"><h2><?=lang('delete_confirm')?></h2></div>

		<div class="pageContents">

			<?=form_open('C=tools_communicate'.AMP.'M=delete_emails', '', $hidden)?>
			
			<p class="notice"><?=lang('delete_question')?></p>
			
			<ul class="subtext">
			<?php foreach ($emails as $email): ?>
				<li>&lsquo;<?=$email?>&rsquo;</li>
			<?php endforeach; ?>
			</ul>
			
			<p><?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'delete'))?></p>

			<?=form_close()?>

			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->


<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}


/* End of file email_delete_confirm.php */
/* Location: ./themes/cp_themes/corporate/tools/email_delete_confirm.php */