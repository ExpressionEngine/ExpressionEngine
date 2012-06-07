<div>
	<?=form_open($form_action, '', $hidden)?>

	<p><strong><?=lang('delete_board_confirmation')?></strong></p>
			
	<p class="notice"><?=lang('delete_board_confirmation_message')?></p>

	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

	<?=form_close()?>
</div>