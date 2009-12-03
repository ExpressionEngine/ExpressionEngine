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
				<p class="notice"><?=$no_result?></p>
			<?php else:
				
				$this->table->set_template($cp_table_template);
				$this->table->set_heading($left_column, $right_column);

				foreach ($entries as $left => $right)
				{
					$this->table->add_row($left, $right);
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

/* End of file index.php */
/* Location: ./themes/cp_themes/default/content/recent_list.php */