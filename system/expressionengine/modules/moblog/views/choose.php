<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=moblog'.AMP.'method=create_modify', '', array('basis_flag'=>'basis_flag'))?>

<?=form_label(lang('moblog_basis'), 'basis')?>
<?=form_dropdown('basis', $options)?>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
</p>

<?=form_close()?>