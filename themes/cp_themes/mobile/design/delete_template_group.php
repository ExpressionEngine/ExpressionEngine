<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<p class="pad container"><?=lang('choose_delete_template_group')?></p>

	<ul class="rounded">
		<?php foreach ($template_groups as $group):?>
			<li><a href="<?=BASE.AMP.'C=design'.AMP.'M=template_group_delete_confirm'.AMP.'group_id='.$group['group_id']?>"><?=$group['group_name']?></a></li>
		<?php endforeach;?>
	</ul>



</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file delete_template_group.php */
/* Location: ./themes/cp_themes/mobile/design/delete_template_group.php */