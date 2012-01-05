<div class="clear_left shun"></div>

<?php if(count($wikis) == 0):?>

	<p class="notice"><?=lang('no_wiki')?></p>

<?php else:?>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=delete_confirm')?>

	<?php
		$this->table->set_heading(
			lang('label_name'),
			lang('short_name'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
		);

		foreach($wikis as $wiki)
		{
			$this->table->add_row(
									'<a href="'.BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=wiki'.AMP.'method=update'.AMP.'wiki_id='.$wiki['id'].'">'.$wiki['label_name'].'</a>',
									$wiki['shortname'],
									form_checkbox($wiki['toggle'])
									);
		}

	?>
			<?=$this->table->generate()?>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>

<?php endif;

/* End of file index.php */
/* Location: ./system/expressionengine/modules/wiki/views/index.php */