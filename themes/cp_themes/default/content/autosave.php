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
	
		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

			<?php if (count($entries) < 1):?>
				<p class="notice"><?=lang('no_autosave_data')?></p>
			<?php else:
				
				$this->table->set_template($cp_table_template);
				$this->table->set_heading($table_headings);

				foreach ($entries as $row)
				{
					$this->table->add_row($row);
				}
				echo $this->table->generate();
				$this->table->clear();

			endif;?>

		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit.php */
/* Location: ./themes/cp_themes/default/content/edit.php */

/* End of file autosave_options.php */
/* Location: ./themes/cp_themes/default/content/autosave_options.php */