<h3><?=lang('channeler_configurations')?></h3>

<div class="clear_left shun"></div>
<?php if(count($blogger_prefs) > 0):?>
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api'.AMP.'method=delete_confirm')?>

<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		lang('blogger_config_name').'/'.lang('edit'),
		lang('blogger_config_url'),
		form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
	);

	foreach($blogger_prefs as $blogger_pref)
	{
		$this->table->add_row(
								'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api'.AMP.'method=modify'.AMP.'id='.$blogger_pref['id'].'">'.$blogger_pref['name'].'</a>',
								$blogger_pref['url'],
								form_checkbox($blogger_pref['toggle'])
								);
	}

?>
		<?=$this->table->generate()?>
<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>
<?php else:?>
	<p class="notice"><?=lang('no_blogger_configs')?></p>
<?php endif;?>