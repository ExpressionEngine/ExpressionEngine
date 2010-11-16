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

			<?=form_open('C=members'.AMP.'M=do_login_as_member', '', $form_hidden)?>

			<p class="notice"><?=$this->lang->line('action_can_not_be_undone')?></p>

			<p><?=$message?></p>

			<div>
				<?=form_radio(array('name'=>'return_destination','id'=>'site_homepage', 'value'=>'site'))?>
				<?=lang('site_homepage', 'site_homepage')?>
			</div>
			<?php if ($can_access_cp):?>
				<div>
					<?=form_radio(array('name'=>'return_destination','id'=>'cp', 'value'=>'cp'))?>
					<?=lang('control_panel', 'cp')?>
				</div>
			<?php endif;?>
			<div>
				<?=form_radio(array('name'=>'return_destination','id'=>'other', 'value'=>'other'))?>
				<?=lang('other', 'other')?> 
				<?=form_input(array('id'=>'other_url','name'=>'other_url','size'=>50,'value'=>$this->functions->fetch_site_index()))?>
			</div>

			<p>
				<?=form_submit('login_as_member', lang('submit'), 'class="submit"')?>
			</p>

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

/* End of file login_as_member.php */
/* Location: ./themes/cp_themes/default/members/login_as_member.php */