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

		<div class="heading"><h2 class="edit">
		<span id="filter_ajax_indicator" style="visibility:hidden; float:right;"><img src="<?=$cp_theme_url?>images/indicator2.gif" style="padding-right:20px;" /></span>			
		<?=$cp_page_title?></h2></div>
		<div class="pageContents">
			
		<?php $this->load->view('_shared/message'); ?>

		<?=form_open('C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=email')?>
			
			<?=$table_html?>
			
			<?php if ( ! empty($rows)): ?>
				<div class="tableFooter">
					<div class="tableSubmit">
						<?=form_submit('email_logs', lang('delete'), 'class="submit"')?>
					</div>		
				
					<?=$pagination_html?>
				</div> <!-- tableFooter -->
			<?php endif ?>
			
		<?=form_close()?>

		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_email_log.php */
/* Location: ./themes/cp_themes/default/tools/view_email_log.php */