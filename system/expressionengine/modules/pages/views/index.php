<?php if(isset($pages) && count($pages) > 0):?>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages'.AMP.'method=delete_confirm')?>

	<?php
		$this->table->set_heading(
			lang('page'),
			lang('view_page'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
		);

		foreach($pages as $page)
		{
			$this->table->add_row(
									$page['indent'].'<a href="'.BASE.AMP.'C=content_publish'.AMP.'M=entry_form'.AMP.'entry_id='.$page['entry_id'].'">'.$page['page'].'</a>',
									'<a href="'.$page['view_url'].'">'.lang('view_page').'</a>',
									form_checkbox($page['toggle'])
									);
		}

	?>
			<?=$this->table->generate()?>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>

<?php else: ?>
	<p class="notice"><?=lang('no_pages')?></p>
<?php endif;?>