<?php extend_template('default') ?>

<?php if($blacklist_installed): ?>
	<div class="cp_button"><a href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=blacklist_throttled_ips'?>"><?=lang('blacklist_all_ips')?></a></div>
	<div class="clear_left"></div>
<?php endif;?>
	
<?php if ($this->config->item('enable_throttling') == 'n'):?>
		<p><?=lang('throttling_disabled')?></p>
<?php else:
	echo $table_html;
	echo $pagination_html;
endif;?>