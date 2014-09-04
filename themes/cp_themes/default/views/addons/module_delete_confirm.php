<?php extend_template('default') ?>

<?=form_open($form_action, '', $form_hidden)?>

<p><strong><?=lang('delete_module_confirm')?></strong></p>

<p><?=$module_name?></p>

<p class="notice"><?=lang('data_will_be_lost')?></p>

<p><?=form_submit('submit', lang('delete_module'), 'class="submit"')?></p>

<?=form_close()?>