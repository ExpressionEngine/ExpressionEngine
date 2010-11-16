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

			<?=form_open('C=admin_content'.AMP.'M=status_group_update', '', $form_hidden)?>

			<p>
			<?=form_label(lang('name_of_status_group'), 'status_group_name')?>
			<?=form_input(array('id'=>'status_group_name','name'=>'group_name','class'=>'field','value'=>$group_name))?>
			</p>

			<p><?=form_submit('edit_status_group_name', lang($submit_lang_key), 'class="submit"')?></p>

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

/* End of file status_group_edit.php */
/* Location: ./themes/cp_themes/default/admin/status_group_edit.php */