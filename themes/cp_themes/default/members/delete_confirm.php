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

				<?=form_open('C=members'.AMP.'M=member_delete')?>
	
				<?php foreach($damned as $member_id):?>
					<?=form_hidden('delete[]', $member_id)?>
				<?php endforeach;?>

				<p><strong><?=lang('delete_members_confirm')?></strong></p>
		
				<?=$user_name?>
		
				<p class="notice"><?=lang('action_can_not_be_undone')?></p>

				<?php if(count($heirs) == 1):?>
				<p><?=lang('heir_to_member_entries', 'heir').BR.form_dropdown('heir', $heirs)?></p>
				<?php elseif(count($heirs) > 1):?>
				<p><?=lang('heir_to_members_entries', 'heir').BR.form_dropdown('heir', $heirs)?></p>
				<?php endif;?>

				<p><?=form_submit('delete_members', lang('delete'), 'class="submit"')?></p>
	
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

/* End of file delete_confirm.php */
/* Location: ./themes/cp_themes/default/members/delete_confirm.php */