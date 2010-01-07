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
		
		<div class="heading">
				<h2><?=$cp_page_title?></h2>
		</div>
        <div class="pageContents">
		<?php $this->load->view('_shared/message');?>

		<?php
			$this->table->set_template($cp_table_template);
			$this->table->set_heading($table_headings);
			echo $this->table->generate($fieldtypes);
		?>

        </div>
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file fieldtypes.php */
/* Location: ./themes/cp_themes/default/addons/fieldtypes.php */