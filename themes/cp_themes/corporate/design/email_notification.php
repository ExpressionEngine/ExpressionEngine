<?php
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
		
				<div class="heading"><h2><?=$cp_page_title?></h2></div>
		<div class="pageContents">
		<?php $this->load->view('_shared/message');?>
			<?php if ($specialty_email_templates_summary->num_rows() < 1):?>

				<p class="notice"><?=lang('unable_to_find_templates')?></p>

			<?php else:?>

				<ul class="menu_list">
				<?php foreach ($specialty_email_templates_summary->result() as $template):?>
					<li<?=alternator(' class="odd"', '')?>>
						<a href="<?=BASE.AMP.'C=design'.AMP.'M=edit_email_notification'.AMP.'template='.$template->template_name?>">
							<?=lang($template->template_name)?>
						</a>
					</li>
				<?php endforeach;?>
				</ul>

			<?php endif;?>

		</div>

	</div> 
	<!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file email_notification.php */
/* Location: ./themes/cp_themes/default/design/email_notification.php */