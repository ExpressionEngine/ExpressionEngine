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
			<?=lang('view_developer_log')?></h2></div>
			<div class="pageContents">
			<?php $this->load->view('_shared/message');?>
			
			<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
				lang('log_id'),
				lang('date'),
				lang('log_message')
			);
		
			if ($logs->num_rows() > 0):
			
				foreach ($logs->result_array() as $data)
				{
					$new = ($data['viewed'] == 'n') ? 'new' : '';
					
					$log_id = array(
						'data'	=> $data['log_id'],
						'class'	=> $new
					);
					$date = array(
						'data'	=> date('Y-m-d h:i A', $data['timestamp']),
						'class'	=> $new
					);
					$message = array(
						'data'	=> (isset($data['function'])) ? $this->logger->build_deprecation_language($data) : $data['description'],
						'class'	=> $new
					);
					
					$this->table->add_row($log_id, $date, $message);
				}
			?>
			
				<div class="cp_button"><a href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=cp'?>"><?=lang('clear_logs')?></a></div>
				<div class="clear_left"></div>
					
				<?=$this->table->generate()?>
				
			<?php else:?>

				<p><?=lang('no_search_results')?></p>

			<?php endif;?>
		
			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_cp_log.php */
/* Location: ./themes/cp_themes/default/tools/view_cp_log.php */