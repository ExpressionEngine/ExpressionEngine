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

		<div class="heading"><h2><?=$cp_page_title?></h2></div>
        
		<div class="pageContents">

			<?=form_open('C=admin_content'.AMP.'M=status_group_update', '', $form_hidden)?>
		<table id="prefs" class="mainTable" cellspacing="0" cellpadding="0" border="0" summary="Status Group Edit">
			<tbody>
				<tr>
					<td style="width:50%;">
						<?=form_label(lang('name_of_status_group'), 'status_group_name')?>
					</td>
					<td>
						<?=form_input(array('id'=>'status_group_name','name'=>'group_name','class'=>'fullfield','value'=>$group_name))?>
					</td>
				</tr>
			</tbody>
		</table>
		
		<p class="centerSubmit"><?=form_submit(array('name' => 'edit_status_group_name', 'value' => lang('submit'), 'class' => 'submit'))?></p>
		
  		<?=form_close()?>
  		
			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file status_group_edit.php */
/* Location: ./themes/cp_themes/corporate/admin/status_group_edit.php */