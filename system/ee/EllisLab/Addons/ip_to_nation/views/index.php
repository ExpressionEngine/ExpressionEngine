<h3><?=lang('ip_search')?></h3>
	
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation')?>

	<?php if ($country != ''):?>
		<p><span class="notice"><?=lang('ip_result')?></span> <?=$country?></p>
	<?php elseif ($error !== FALSE): ?>
		<p><span class="notice"><?=$error?></span></p>
	<?php endif ?>

	<p>
		<?=lang('ip_search_inst', 'ip')?>
	</p>

	<p>
		<?=form_input('ip', $ip, 'id="ip"')?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
	</p>

	<p>
		<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation'.AMP.'method=banlist'?>"><?=lang('manage_banlist')?></a>
	</p>

<?=form_close()?>
