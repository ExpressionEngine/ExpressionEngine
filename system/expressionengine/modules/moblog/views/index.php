<?php if ($total_rows):?>
	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog'.AMP.'method=delete_confirm')?>

	<?=$table_html?>
	<?=$pagination_html?>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>
<?php else:?>
	<p class="notice"><?=lang('no_moblogs')?></p>
<?php endif;?>