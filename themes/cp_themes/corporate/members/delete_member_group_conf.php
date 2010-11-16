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

				<?=form_open('C=members'.AMP.'M=delete_member_group'.AMP.'group_id='.$group_id, '', $form_hidden)?>

				<p><strong><?=$this->lang->line('delete_member_group_confirm')?></strong></p>
				
				<p><em><?=$group_title?></em></p>
				
				<p class="notice"><?=$this->lang->line('action_can_not_be_undone')?></p>

				<?php if ($member_count > 0):?>
					<p><?=str_replace('%x', $member_count , $this->lang->line('member_assignment_warning'))?>
					<p><?=form_dropdown('new_group_id', $new_group_id)?></p>
				<?php endif;?>

				<p><?=form_submit('delete', $this->lang->line('delete'), 'class="submit"')?></p>
	
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

/* End of file delete_member_group_conf.php */
/* Location: ./themes/cp_themes/default/members/delete_member_group_conf.php */