<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	//$this->load->view('_shared/main_menu');
	//$this->load->view('_shared/sidebar');
	//$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"style="width:100%;">
	<div class="contents">
		
		<br /><br />
		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		
		<?php if($type == 'cleared'):?>
	    	<div class="pageContents">
				<p><strong><?=lang('history_cleared')?></strong></p>
			</div>
		<?php elseif($type == 'clear'):?>
		    <div class="pageContents">

				<?=form_open('C=design'.AMP.'M=clear_revision_history', '', $form_hidden)?>

				<p><strong><?=lang('clear_revision_history_info')?></strong></p>
		
				<p><?=$template_group?>/<?=$template_name?></p>
		
				<p class="notice"><?=lang('action_can_not_be_undone')?></p>

				<p><?=form_submit('delete_template', lang('delete'), 'class="submit"')?></p>
	
				<?=form_close()?>

			</div>

	<?php else:?>

		<div id="templateEditor" class="formArea">

			<div class="clear_left" id="template_details" style="margin-bottom: 0">
				<p>
				<?=$template_group?>/<?=$template_name?> (<?=$revision_date?>)
			</div>

			<div id="template_create" class="pageContents">
		
			<?=form_textarea(array(
				'name'	=> 'template_data',
				'id'	=> 'template_data',
				'cols'	=> '100',
				'rows'	=> '20',
				'value'	=> $revision_data,
				'style' => 'border: 0;'
			));?>

			</div>
		</div>
	<?php endif;?>

	<div align="center"><a href="JavaScript:window.close();"><b><?=lang('close_window')?></b></a></div>


	</div> <!-- contents -->
</div> <!-- mainContent -->


<?php
if ($EE_view_disable !== TRUE)
{
	//$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}



/* End of file revision_history.php */
/* Location: ./themes/cp_themes/default/design/revision_history.php */