<?php extend_template('default') ?>

<?=form_open($form_action, '', $form_hidden)?>

	<p><?=lang('category_order_confirm_text')?></p>

	<p class="notice"><?=lang('category_sort_warning')?></p>

	<p><?=form_submit('submit', lang('update'), 'class="submit"')?></p>

<?=form_close()?>