<div class="cp_button"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api'.AMP.'method=create'?>"><?=lang('metaweblog_create_new')?></a></div>

<h3><?=lang('metaweblog_configurations')?></h3>

<div class="clear_left shun"></div>

<?php if(count($metaweblogs) > 0):?>
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api'.AMP.'method=delete_confirm')?>

<?php
	$this->table->set_heading(
		lang('metaweblog_config_name').'/'.lang('edit'),
		lang('metaweblog_config_url'),
		(count($metaweblogs) == 1) ? lang('delete') : form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
	);

	foreach($metaweblogs as $metaweblog)
	{
		$this->table->add_row(
								'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=metaweblog_api'.AMP.'method=modify'.AMP.'id='.$metaweblog['id'].'">'.$metaweblog['name'].'</a>',
								$metaweblog['url'],
								form_checkbox($metaweblog['toggle'])
								);
	}

?>
		<?=$this->table->generate()?>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>
<?php else:?>
	<p class="notice"><?=lang('no_metaweblog_configs')?></p>
<?php endif;?>