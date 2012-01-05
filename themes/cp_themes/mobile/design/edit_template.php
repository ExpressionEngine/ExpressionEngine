<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<?=form_open('C=design'.AMP.'M=update_template'.AMP.'tgpref='.$group_id, '', array('template_id' => $template_id, 'group_id' => $group_id))?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design<?=AMP?>M=manager" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<?=form_open('C=design'.AMP.'M=update_template'.AMP.'tgpref='.$group_id, '', array('template_id' => $template_id, 'group_id' => $group_id))?>
	<div class="hidden">
		<?php foreach($member_groups as $id => $group):?>
		<input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_y" value="y" <?=$access[$id] ? 'checked="checked"' : ''?> />
		<input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_n" value="n" <?=$access[$id] ? '' : 'checked="checked"'?> />
		<?php endforeach;?>
		<input name="group_name" class="group_name" type="text" size="15" value="<?=$template_name?>" />
		<?=form_dropdown('template_type', $template_types, $prefs['template_type'], 'class="template_type" id="template_type"')?>
		<?=form_dropdown('cache', array('y' => lang('yes'), 'n' => lang('no')), $prefs['cache'])?>
		<input class="refresh" name="refresh" type="text" size="4" value="<?=$prefs['refresh']?>" />
		<?=form_dropdown('allow_php', array('y' => lang('yes'), 'n' => lang('no')), $prefs['allow_php'])?>
		<?=form_dropdown('php_parse_location', array('i' => lang('input'), 'o' => lang('output')), $prefs['php_parse_location'])?>
		<input name="hits" class="hits" type="text" size="8" value="<?=$prefs['hits']?>" />
		<input name="template_size" class="template_size" type="text" size="4" value="<?=$prefs['template_size']?>" />
		
	</div>
	
	
	<ul>
		<li>
	<?=form_textarea(array(
							'name'	=> 'template_data',
							'id'	=> 'template_data',
							'rows'	=> $prefs['template_size'],
							'value' => $template_data,
							'style' => 'border: 0;'
					));?></li>
	</ul>
	<?=form_submit('update', lang('update'), 'class="whiteButton"')?>

	<?=form_submit('update_and_return', lang('update_and_return'), 'class="whiteButton"')?>
	<?=form_close()?>

	<ul>
		<li><a href="#preferences"><?=lang('preferences')?></a></li>
		<li><a href="#access"><?=lang('access')?></a></li>
		<li><a href="#template_notes"><?=lang('template_notes')?></a></li>
	</ul>
</div>
<?=form_close()?>

<?php
/**
 *  Template Preferences
 */
?>

<div id="preferences">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="#home" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
		<?=form_open('C=design'.AMP.'M=update_template'.AMP.'tgpref='.$group_id, '', array('template_id' => $template_id, 'group_id' => $group_id))?>
		<div class="hidden">
			<?=form_textarea(array(
									'name'	=> 'template_data',
									'id'	=> 'template_data',
									'rows'	=> $prefs['template_size'],
									'value' => $template_data,
									'style' => 'border: 0;'
							));?>		
			<?=form_dropdown('cache', array('y' => lang('yes'), 'n' => lang('no')), $prefs['cache'])?>
			<input class="refresh" name="refresh" type="text" size="4" value="<?=$prefs['refresh']?>" />
			<?=form_dropdown('allow_php', array('y' => lang('yes'), 'n' => lang('no')), $prefs['allow_php'])?>
			<?=form_dropdown('php_parse_location', array('i' => lang('input'), 'o' => lang('output')), $prefs['php_parse_location'])?>
			<input name="hits" class="hits" type="text" size="8" value="<?=$prefs['hits']?>" />
			<input name="template_size" class="template_size" type="text" size="4" value="<?=$prefs['template_size']?>" />		
		</div><?php // End Hidden Inputs?>
		
		<ul>
			<li><?=lang('preferences')?></li>
			<li><?=lang('name_of_template')?><br />
				<input name="group_name" class="group_name" type="text" size="15" value="<?=$template_name?>" /></li>
			<li><?=lang('type')?><br />
				<?=form_dropdown('template_type', $template_types, $prefs['template_type'], 'class="template_type" id="template_type"')?>
			</li>
			<li><?=lang('cache_enable')?><br />
				<?=form_dropdown('cache', array('y' => lang('yes'), 'n' => lang('no')), $prefs['cache'])?>
			</li>
			<li><?=lang('cache_enable')?><br />
				<?=lang('refresh_in_minutes')?><br />
				<input class="refresh" name="refresh" type="text" size="4" value="<?=$prefs['refresh']?>" />
			</li>
			<li><?=lang('enable_php')?><br />
				<?=form_dropdown('allow_php', array('y' => lang('yes'), 'n' => lang('no')), $prefs['allow_php'])?>
			</li>
			<li><?=lang('parse_stage')?><br />
				<?=form_dropdown('php_parse_location', array('i' => lang('input'), 'o' => lang('output')), $prefs['php_parse_location'])?>
			</li>
			<li><?=lang('hit_counter')?><br />
				<input name="hits" class="hits" type="text" size="8" value="<?=$prefs['hits']?>" />
			</li>
			<li><?=lang('template_size')?><br />
				<input name="template_size" class="template_size" type="text" size="4" value="<?=$prefs['template_size']?>" />
			</li>
		</ul>
		<?=form_submit('update', lang('update'), 'class="whiteButton"')?>

		<?=form_submit('update_and_return', lang('update_and_return'), 'class="whiteButton"')?>
		<?=form_close()?>
</div>

<?php
/**
 *  Template Access
 */
?>

<div id="access">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="#home" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
		<?=form_open('C=design'.AMP.'M=update_template'.AMP.'tgpref='.$group_id, '', array('template_id' => $template_id, 'group_id' => $group_id))?>
		<div class="hidden">
			<?=form_textarea(array(
									'name'	=> 'template_data',
									'id'	=> 'template_data',
									'rows'	=> $prefs['template_size'],
									'value' => $template_data,
									'style' => 'border: 0;'
							));?>		
			<input name="group_name" class="group_name" type="text" size="15" value="<?=$template_name?>" />
			<?=form_dropdown('template_type', $template_types, $prefs['template_type'], 'class="template_type" id="template_type"')?>
			<?=form_dropdown('cache', array('y' => lang('yes'), 'n' => lang('no')), $prefs['cache'])?>
			<input class="refresh" name="refresh" type="text" size="4" value="<?=$prefs['refresh']?>" />
			<?=form_dropdown('allow_php', array('y' => lang('yes'), 'n' => lang('no')), $prefs['allow_php'])?>
			<?=form_dropdown('php_parse_location', array('i' => lang('input'), 'o' => lang('output')), $prefs['php_parse_location'])?>
			<input name="hits" class="hits" type="text" size="8" value="<?=$prefs['hits']?>" />
			<input name="template_size" class="template_size" type="text" size="4" value="<?=$prefs['template_size']?>" />		
		</div> <?php // End Hidden Inputs?>
		
		<ul>
			<li><?=lang('access')?></li>
			<?php foreach($member_groups as $id => $group):?>
			<li><?=$group->group_title?>&nbsp;&nbsp;&nbsp;<span style="float:right;"><?=lang('yes')?> <input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_y" value="y" <?=$access[$id] ? 'checked="checked"' : ''?> /> &nbsp; <?=lang('no')?> <input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_n" value="n" <?=$access[$id] ? '' : 'checked="checked"'?> /></span></li>
			<?php endforeach;?>
		</ul>
	<?=form_submit('update', lang('update'), 'class="whiteButton"')?>

	<?=form_submit('update_and_return', lang('update_and_return'), 'class="whiteButton"')?>
	<?=form_close()?>
</div>


<?php
/**
 *  Template Notes
 */
?>

<div id="template_notes">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="#home" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
<?=form_open('C=design'.AMP.'M=update_template'.AMP.'tgpref='.$group_id, '', array('template_id' => $template_id, 'group_id' => $group_id))?>
	<div class="hidden">
		<?=form_textarea(array(
								'name'	=> 'template_data',
								'id'	=> 'template_data',
								'rows'	=> $prefs['template_size'],
								'value' => $template_data,
								'style' => 'border: 0;'
						));?>	
		<?php foreach($member_groups as $id => $group):?>
		<input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_y" value="y" <?=$access[$id] ? 'checked="checked"' : ''?> />
		<input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_n" value="n" <?=$access[$id] ? '' : 'checked="checked"'?> />
		<?php endforeach;?>		
		<input name="group_name" class="group_name" type="text" size="15" value="<?=$template_name?>" />
		<?=form_dropdown('template_type', $template_types, $prefs['template_type'], 'class="template_type" id="template_type"')?>
		<?=form_dropdown('cache', array('y' => lang('yes'), 'n' => lang('no')), $prefs['cache'])?>
		<input class="refresh" name="refresh" type="text" size="4" value="<?=$prefs['refresh']?>" />
		<?=form_dropdown('allow_php', array('y' => lang('yes'), 'n' => lang('no')), $prefs['allow_php'])?>
		<?=form_dropdown('php_parse_location', array('i' => lang('input'), 'o' => lang('output')), $prefs['php_parse_location'])?>
		<input name="hits" class="hits" type="text" size="8" value="<?=$prefs['hits']?>" />
		<input name="template_size" class="template_size" type="text" size="4" value="<?=$prefs['template_size']?>" />
	</div> <?php // End Hidden Inputs?>
	<ul>
		<li><?=lang('template_notes_desc')?></li>
		<li>
			<?=form_textarea(array(
				'name'	=> 'template_notes',
				'id'	=> 'template_notes',
				'class'	=> 'field',
				'value'	=> $template_notes
			))?>
		</li>
	</ul>
	<?=form_submit('update', lang('update'), 'class="whiteButton"')?>
		
	<?=form_submit('update_and_return', lang('update_and_return'), 'class="whiteButton"')?>
	<?=form_close()?>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_template.php */
/* Location: ./themes/cp_themes/mobile/design/edit_template.php */