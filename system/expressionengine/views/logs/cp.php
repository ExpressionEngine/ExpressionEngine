<?php extend_template('default') ?>

<div class="cp_button"><a href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=cp'?>"><?=lang('clear_logs')?></a></div>
<div class="clear_left"></div>

<?php
echo $table_html;
echo $pagination_html;
?>