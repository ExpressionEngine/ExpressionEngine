<?php extend_template('default') ?>

<?=form_open('C=design'.AMP.'M=snippets_delete')?>
	<?=form_hidden('delete_confirm', TRUE)?>
	<?=form_hidden('snippet_id', $snippet_id)?>
	<p><?=lang('delete_this_snippet')?> <strong><?=$snippet_name?></strong></p>
	<p><strong class="notice"><?=lang('action_can_not_be_undone')?></strong></p>
	<p><?=form_submit('template', lang('yes'), 'class="submit"')?></p>
<?=form_close()?>