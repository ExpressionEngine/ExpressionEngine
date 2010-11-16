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

		<?php
		
		if ($emails_count > 0):
			$this->table->set_template($cp_table_template);
			$this->table->set_heading(
				lang('email_title'),
				lang('from'),
				lang('to'),
				lang('date'),
				'<label>'.form_checkbox(array('id'=>'toggle_all','name'=>'toggle_all','value'=>'toggle_all','checked'=>FALSE)).'</label>'
			);
	
			foreach ($emails->result() as $data)
			{
				$this->table->add_row(
									'<a href="'.BASE.AMP.'C=tools_logs'.AMP.'M=view_email'.AMP.'id='.$data->cache_id.'">'.$data->subject.'</a>',
									'<a href="'.BASE.AMP.'C=myaccount'.AMP.'member_id='. $data->member_id .'">'.$data->member_name.'</a>',
									$data->recipient_name,
									date("Y-m-d h:i A", $data->cache_date),
									form_checkbox(array('id'=>'delete_box_'.$data->cache_id,'name'=>'toggle[]','value'=>$data->cache_id, 'class'=>'toggle_email', 'checked'=>FALSE))
								);					
			}
		?>

			<?=form_open('C=tools_logs'.AMP.'M=delete_email')?>
			
			<?=$this->table->generate()?>
			
			
		<div class="tableFooter">
			<div class="tableSubmit">
					<?=form_submit('email_logs', lang('delete'), 'class="submit"')?>
			</div>		
					<?php if ($pagination): ?>					
						<span class="js_hide"><?=$pagination?></span>
					<?php endif; ?>
						<span class="pagination" id="filter_pagination"></span>
		</div> <!-- tableFooter -->
			
			<?=form_close()?>

		<?php else:?>

			<p><?=lang('no_cached_email')?></p>

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

/* End of file view_email_log.php */
/* Location: ./themes/cp_themes/default/tools/view_email_log.php */