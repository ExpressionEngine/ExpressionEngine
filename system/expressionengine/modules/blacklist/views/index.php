
<ul class="bullets" style="margin-bottom:15px">
	<li style="width:25%; float: left;"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=view_blacklist'?>"><?=lang('ref_view_blacklist')?></a></li>
	<li>
	<?php if ($license_number != ''):?>
		<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=ee_blacklist'?>"><?=lang('ee_blacklist')?></a>
	<?php else:?>
		<span class="notice"><?=lang('ee_blacklist')?> (<?=lang('requires_license_number')?>)</span>
	<?php endif;?>
	</li>
	<li style="width:25%; float: left;"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=view_whitelist'?>"><?=lang('ref_view_whitelist')?></a></li>
	<li>
	<?php if ($license_number != ''):?>
		<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=ee_whitelist'?>"><?=lang('ee_whitelist')?></a>
	<?php else:?>
		<span class="notice"><?=lang('ee_whitelist')?> (<?=lang('requires_license_number')?>)</span>
	<?php endif;?>
	</li>
</ul>

<?php if ($allow_write_htaccess):?>

	<h3><?=lang('write_htaccess_file')?></h3>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=save_htaccess_path')?>

	<p>
		<?=lang('htaccess_server_path', 'htaccess_path')?> 
		<?=form_input('htaccess_path', set_value('htaccess_path', $htaccess_path), 'size="35"')?>
		<?=form_error('htaccess_path')?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>

<?php endif;?>