<h3><?=lang('updated_sites_configurations')?></h3>

<div class="clear_left shun"></div>
<?php if(count($pings) > 0):?>
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method=delete_confirm')?>

<?php
	$this->table->set_template($cp_table_template);
	$this->table->set_heading(
		lang('updated_sites_config_name').'/'.lang('edit'),
		lang('view_pings'),
		lang('updated_sites_config_url'),
		form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
	);

	foreach($pings as $ping)
	{
		$this->table->add_row(
								'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method=modify'.AMP.'id='.$ping['id'].'">'.$ping['name'].'</a>',
								'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method=pings'.AMP.'id='.$ping['id'].'">'.lang('view_pings').'</a>',
								$ping['url'],
								form_checkbox($ping['toggle'])
								);
	}
?>
		<?=$this->table->generate()?>
<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>
<?php endif;?>