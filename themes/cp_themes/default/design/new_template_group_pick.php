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
			<h2><?=lang('new_template_form')?></h2>
	</div>
	
	<div class="pageContents">
	    <?php if (!$template_groups):?>

		<h3><?=lang('no_templates_available'); ?></h3>
        <?php else: ?>
        
        <h3><?=lang('template_group_choose')?></h3>
		<ul class="bullets">
    			<?php foreach ($template_groups as $group):?>
    				<li><a href="<?=BASE.AMP.'C=design'.AMP.'M='.$link_to_method.AMP.'group_id='.$group['group_id']?>"><?=$group['group_name']?></a></li>
    			<?php endforeach;?>
		</ul>
        <?php endif; ?>
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
/* Location: ./themes/cp_themes/default/design/new_template_group_pick.php */