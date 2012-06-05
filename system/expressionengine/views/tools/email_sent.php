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

			<div class="heading"><h2 class="edit"><?=lang('email_success')?></h2></div>
			<div class="pageContents">
				
				<p class="go_notice">
					<?php if (isset($total_sent) AND $total_sent > 1): ?>
					<?=lang('all_email_sent_message')?>
					<?php else: ?>
					<?=lang('email_sent_message')?>
					<?php endif; ?>
				</p>
				
				<?php if (isset($total_sent)): ?>
				<p class="go_notice"><?=lang('total_emails_sent')?> <?=$total_sent?></p>
				<?php endif; ?>
				
				<?php if ($debug): ?>
					<?php foreach ($debug as $message): ?>
					<?=$message?><br />
					<?php endforeach; ?>
				<?php endif; ?>
				<div class="clear_right"></div>		
			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}


/* End of file email_sent.php */
/* Location: ./themes/cp_themes/default/tools/email_sent.php */