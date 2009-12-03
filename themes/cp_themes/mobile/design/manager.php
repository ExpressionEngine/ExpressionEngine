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

	<ul>
		<?php foreach ($template_groups as $row): ?>
		<li><a href="#template_group_<?=$row['group_id']?>"><?php if ($row['is_site_default'] == 'y'):?>*&nbsp;<?php endif?><?=$row['group_name']?></a></li>
		<?php endforeach; ?>
	</ul>	

	<ul>
		<li>*&nbsp;</span><?=lang('default_template_group')?> <span class="defaultGroupName"><?=$default_group?></li>
	</ul>
</div>

<?php foreach ($templates as $group):
	$temp = current($group);
	$group_id = $temp['group_id'];
	$group_name = $temp['group_name'];
	unset($temp);
?>
<div id="template_group_<?=$group_id?>">
    <div class="toolbar">
        <h1><?=$group_name?></h1>
        <a href="#home" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<ul>
	<?php foreach ($group as $template): ?>
		
		<li><a href="<?=BASE.AMP.'C=design'.AMP.'M=edit_template'.AMP.'id='.$template['template_id']?>"><?=$template['template_name']?></a></li>

	<?php endforeach;?>
	</ul>
</div>
<?php endforeach; ?>
?>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file manager.php */
/* Location: ./themes/cp_themes/mobile/design/manager.php */