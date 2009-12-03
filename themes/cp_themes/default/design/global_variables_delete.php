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
				<h2><?=lang('delete_global_variable')?></h2>
		</div>
		<div id="new_global_var" class="pageContents">

			<?=form_open('C=design'.AMP.'M=global_variables_delete')?>
				<?=form_hidden('delete_confirm', TRUE)?>
				<?=form_hidden('variable_id', $variable_id)?>
				<p><?=lang('delete_this_variable')?> <strong><?=$variable_name?></strong></p>
				<p><strong class="notice"><?=lang('action_can_not_be_undone')?></strong></p>
				<p><?=form_submit('template', lang('yes'), 'class="submit"')?></p>
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

/* End of file global_variables_delete.php */
/* Location: ./themes/cp_themes/default/design/global_variables_delete.php */