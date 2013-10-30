<?php extend_template('default') ?>

<?=form_open($form_action, '', array('doit' => 'y'))?>

<p><strong><?=lang('delete_fieldtype_confirm')?></strong></p>

<p class="notice"><?=lang('fieldtype_data_will_be_lost')?></p>

<p><?=form_submit('submit', lang('delete_fieldtype'), 'class="submit"')?></p>

<?=form_close()?>