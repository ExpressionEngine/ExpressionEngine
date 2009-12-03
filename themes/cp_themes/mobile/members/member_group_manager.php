<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="member_group_manager" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>


		<?php $this->load->view('_shared/message');?>
	<?php
	
		foreach ($groups as $group):?>
		<div class="label">
			<?=lang('group_title', 'group_title')?>: <?=($group['can_access_cp'] == 'y') ? $group['title'] : $group['title']?><br />
			<?=lang('security_lock', 'security_lock')?>: <?=$group['security_lock']?><br />
			<?=lang('group_id', 'group_id')?>: <?=$group['group_id']?><br />
			
		</div>
		<ul>
			<li><a href="<?=BASE.AMP.'C=members'.AMP.'M=edit_member_group'.AMP.'group_id='.$group['group_id']?>"><?=lang('edit_group')?></a></li>
			<li>(<?=$group['member_count'].') <a href="'.BASE.AMP.'C=members'.AMP.'M=view_all_members'.AMP.'group_id='.$group['group_id'].'">'.lang('view')?></a></li>
			<li><?=($group['delete']) ? '<a href="'.BASE.AMP.'C=members'.AMP.'M=delete_member_group_conf'.AMP.'group_id='.$group['group_id'].'">'.lang('delete').'</a>' : '--'?>
		</ul>
	
		<?php endforeach;?>
		<div class="container pad"><strong class="notice">* <?=lang('member_has_cp_access')?></strong></div>

		<?=$paginate?>
	
		<?=form_open('C=members'.AMP.'M=edit_member_group')?>
		<div class="label">
			<?=lang('create_group_based_on_old', 'clone_id')?> 
		</div>
		<ul>
			<li><?=form_dropdown('clone_id', $clone_group_options)?></li>
		</ul>
		<?=form_submit('submit', lang('submit'), 'class="whiteButton"')?>
		<?=form_close()?>	
	
	

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_members.php */
/* Location: ./themes/cp_themes/default/members/member_group_manager.php */