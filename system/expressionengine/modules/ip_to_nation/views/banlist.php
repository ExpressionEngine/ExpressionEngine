
<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=ip_to_nation'.AMP.'method=update')?>

	<p><?=lang('ban_info')?></p>

	<ul class="inline_labels">
	<?php foreach($countries as $country):?>
		<li>
			<?=form_checkbox($country['code'], 'y', $country['status'], "id='code_{$country['code']}'")?> 
			<label for="code_<?=$country['code']?>"><?=$country['name']?></label>
		</li>
	<?php endforeach;?>
	</ul>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>
	</p>

<?=form_close()?>
