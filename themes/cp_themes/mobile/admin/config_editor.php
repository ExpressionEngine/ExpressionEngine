<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="translate" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=admin_system"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/message');?>

	<div class="container pad"><?=lang('advanced_users_only')?></div>

	<?=form_open ('C=admin_system'.AMP.'M=config_editor_process', '', $hidden)?>

	

	<?php foreach ($config_items as $config_item => $config_value):?>
		<?php if ($config_value === TRUE):?>
			<div class="label">
				<label><?='$config[\''.$config_item.'\']'?></label>
			</div>
			<ul>
				<li>
					<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'TRUE', 'checked'=>TRUE))?>
					<?=form_label(lang('yes'), $config_item.'_true')?>
					<?=repeater(NBS, 5)?>
					<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'FALSE'))?>
					<?=form_label(lang('no'), $config_item.'_false')?></li>
			</ul>
		<?php elseif ($config_value === FALSE):?>
			<div class="label">
				<label><?='$config[\''.$config_item.'\']'?></label>
			</div>
			<ul>
				<li>
				<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'TRUE'))?>
				<?=form_label(lang('yes'), $config_item.'_true')?>
				<?=repeater(NBS, 5)?>
				<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'FALSE', 'checked'=>TRUE))?>
				<?=form_label(lang('no'), $config_item.'_false')?>
				</li>
			</ul>
		<?php elseif ($config_value == 'y' OR $config_value == 'Y'):?>
			<div class="label">
				<label><?='$config[\''.$config_item.'\']'?></label>
			</div>
			<ul>
				<li>
				<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'y', 'checked'=>TRUE))?>
				<?=form_label(lang('yes'), $config_item.'_true')?>
				<?=repeater(NBS, 5)?>
				<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'n'))?>
				<?=form_label(lang('no'), $config_item.'_false')?>
				</li>
			</ul>
		<?php elseif ($config_value == 'n' OR $config_value == 'N'):?>
			<div class="label">
				<label><?='$config[\''.$config_item.'\']'?></label>
			</div>
			<ul>
				<li>
				<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'y'))?>
				<?=form_label(lang('yes'), $config_item.'_true')?>
				<?=repeater(NBS, 5)?>
				<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'n', 'checked'=>TRUE))?>
				<?=form_label(lang('no'), $config_item.'_false')?>
				</li>
			</ul>
		<?php else:?>
			<div class="label">
				<?=form_label('$config[\''.$config_item.'\']', $config_item)?>
			</div>
			<ul>
				<li>
				<?=form_input(array('id'=>$config_item,'name'=>$config_item,'style'=>'width:100%;','value'=>$config_value))?>
			</li>
				</ul>
		<?php endif;?>
	<?php endforeach;?>
		<div class="label">
			<label>$config['<?=form_input('config_name')?>']</label>
		</div>
		<ul>
			<li>
				<label style="display:none;" for="config_setting"><?=lang('setting')?></label>
				<?=form_input(array('name'=>'config_setting', 'id'=>'config_setting', 'style'=>'width:100%;'))?>
			</li>
			</ul>

	<p><?=form_submit('update', lang('update'), 'class="whiteButton"')?></p>

	<?=form_close()?>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_email_log.php */
/* Location: ./themes/cp_themes/mobile/admin_system/translate.php */