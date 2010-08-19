			<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=delete_comment', '', $hidden)?>

			<p class="notice"><?=lang('action_can_not_be_undone')?></p>

			<p><?=form_submit('delete_comments', lang('delete'), 'class="submit"')?></p>

			<?=form_close()?>

