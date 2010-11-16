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
			<?=lang('view_cp_log')?></h2></div>
			<div class="pageContents">
			<?php $this->load->view('_shared/message');?>
				
			<?php
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(
									lang('member_id'),
									lang('username'),
									lang('ip_address'),
									lang('date'),
									lang('site_search'),
									lang('action')
								);

			if ($cp_data->num_rows() > 0):
			
				foreach ($cp_data->result() as $data)
				{
					$this->table->add_row(
										$data->member_id,
										'<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $data->member_id .'">'.$data->username.'</a>',
										$data->ip_address,
										date('Y-m-d h:i A', $data->act_date),
										$data->site_label,
										$data->action
									);
				}
			?>
			
				<div class="cp_button"><a href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=cp'?>"><?=lang('clear_logs')?></a></div>
				<div class="clear_left"></div>

				<?=$this->table->generate()?>

				<span class="js_hide"><?=$pagination?></span>
				<span class="pagination" id="filter_pagination"></span>

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