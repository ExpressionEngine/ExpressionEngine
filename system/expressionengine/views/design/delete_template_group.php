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
			<h2><?=lang('delete_template_group')?></h2>
	</div>
	
	<div class="pageContents">

		<p><?=lang('choose_delete_template_group')?></p>

		<ul class="bullets">
			<?php foreach ($template_groups as $group):?>
				<li><a href="<?=BASE.AMP.'C=design'.AMP.'M=template_group_delete_confirm'.AMP.'group_id='.$group['group_id']?>"><?=$group['group_name']?></a></li>
			<?php endforeach;?>
		</ul>

	</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file new_template.php */
/* Location: ./themes/cp_themes/default/design/new_template.php */