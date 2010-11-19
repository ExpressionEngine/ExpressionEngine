<h3><?=$update_info?></h3>

<p><strong><?=lang('update_blurb')?></strong></p><br/>

	<? if ($last_update):
		echo '<p>' . lang('last_update').$last_update . '</p><br/>';
		endif;
	?>
	
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation'.AMP.'method=import_form')?>


	<p>
		<?=lang('ip2nation_file_loc', 'ip2nation_file')?>
	</p>

	<p>
		<?=form_error('ip2nation_file')?>
		<?=form_input(array('id'=>'ip2nation_file','name'=>'ip2nation_file', 'class'=>'field', 'value'=>set_value('ip2nation_file')))?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>
	</p>

<?=form_close()?>