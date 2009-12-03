<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=status_group_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>


		<?php foreach ($channel_data->result() as $channel):?>
			<div class="label">
				<strong><?=lang('channel_id')?>:</strong> <?=$channel->channel_id?><br />
				<strong><?=lang('channel_name')?>:</strong> <?=$channel->channel_title?><br />
				<strong><?=lang('channel_short_name')?>:</strong> <?=$channel->channel_name?>
			</div>
			<ul class="rounded">
				<li><a href="<?=BASE.AMP?>C=admin_content<?=AMP?>M=channel_edit<?=AMP?>channel_id=<?=$channel->channel_id?>"><?=lang('edit_preferences')?></a></li>
				<li><a href="<?=BASE.AMP?>C=admin_content<?=AMP?>M=channel_edit_group_assignments<?=AMP?>channel_id=<?=$channel->channel_id?>"><?=lang('edit_groups')?></a></li>
			</ul>
		<?php endforeach;?>
</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file channel_management.php */
/* Location: ./themes/cp_themes/default/admin/channel_management.php */