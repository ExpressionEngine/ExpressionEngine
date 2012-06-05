<?php extend_template('default') ?>

<?=form_open($form_action)?>

<?php
$this->table->set_heading(
						array('data' => lang('component'), 'style' => 'width:40%;'),
						array('data' => lang('status')),
						array('data' => lang('current_status'))
						);

foreach ($components as $comp => $info)
{
	$fields = form_radio('install_'.$comp, 'install', ! $info['installed'], 'id="install_'.$comp.'"').NBS.
		form_label(lang('install'), 'install_'.$comp).
		NBS.NBS.NBS.NBS.NBS.
		form_radio('install_'.$comp, 'uninstall', $info['installed'], 'id="uninstall_'.$comp.'"').NBS.
		form_label(lang('uninstall'), 'uninstall_'.$comp);
	
	if (isset($required[$comp]) && count($required[$comp]))
	{
		$fields = lang('required_by').NBS.implode(',', $required[$comp]);
	}
	
	$this->table->add_row(
							lang($comp),
							$fields,
							$info['installed'] ? lang('installed') : lang('not_installed')
						);
}
echo $this->table->generate();
$this->table->clear();
?>


<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?=form_close()?>