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

			<div class="heading"><h2 class="edit"><?=lang('recount_stats')?></h2></div>
			<div class="pageContents">
			
			<?php $this->load->view('_shared/message');?>
			
			<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
									lang('source'),
									lang('records'),
									lang('action')
								);
			foreach ($sources as $source => $count)
			{
				$this->table->add_row(
										lang($source),
										$count,
										'<a href="'.BASE.AMP.'C=tools_data'.AMP.'M=recount_stats'.AMP.'TBL='.$source.'">'.lang('do_recount').'</a>'
									);
			}
			?>
			
			<p><?=lang('recount_info')?></p>

			<?=$this->table->generate()?>

			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file recount_stats.php */
/* Location: ./themes/cp_themes/default/tools/recount_stats.php */