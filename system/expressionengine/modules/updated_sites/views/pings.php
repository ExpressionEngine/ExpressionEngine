<?php if($ping_count > 0):?>

	<p><?=lang('total_pings')?> <?=$ping_count?></p>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=updated_sites'.AMP.'method=delete_confirm', '', $form_hidden)?>

	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
									lang('ping_name'),
									lang('ping_url'),
									lang('ping_rss'),
									lang('ping_date'),
									form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
		);

		foreach($pings as $ping)
		{
			$this->table->add_row(
									$ping['name'],
									'<a href="'.$ping['full_url'].'">'.$ping['display_url'].'</a>',
									$ping['rss'],
									$ping['date'],
									form_checkbox($ping['toggle'])
								);
		}

	?>
			<?=$this->table->generate()?>

<div class="tableFooter">
	<div class="tableSubmit">
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</div>
<span class="js_hide"><?=$pagination?></span>	
<span class="pagination" id="filter_pagination"></span>
</div>	

	<?=form_close()?>

<?php else:?>

	<p><?=lang('no_pings')?></p>

<?php endif;?>