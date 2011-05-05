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
		<?php $this->load->view('_shared/message')?>

		<div class="heading">
			<h2 class="edit"><span id="filter_ajax_indicator" style="visibility:hidden; float:right;"><img src="<?=$cp_theme_url?>images/indicator2.gif" style="padding-right:20px;" /></span>
			<?=$cp_page_title?></h2>
		</div>
		<div class="pageContents">

			<?=form_open('C=addons_fieldtypes'.AMP.'M=global_settings'.AMP.'ft='.$_ft_name)?>
				<?=$_ft_settings_body?>
				<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
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

/* End of file fieldtype_global_settings.php */
/* Location: ./themes/cp_themes/default/addons/fieldtype_global_settings.php */