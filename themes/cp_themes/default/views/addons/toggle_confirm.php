<?php extend_template('default') ?>

<?=form_open($form_action, '', $form_hidden)?>

<p><strong><?=$message?></strong></p>

<p class="notice"><?=lang('toggle_extension_confirmation')?></p>

<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?=form_close()?>