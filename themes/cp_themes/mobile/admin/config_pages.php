<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_system" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

<?php $this->load->view('_shared/message');?>

<?=form_open($form_action, '', array('return_location' => $return_loc))?>
	
<?php

foreach ($fields as $name => $details):
	$pref = '';

	switch ($details['type'])
	{
		case 's':
			$label = lang($name, $name);
			
			if (is_array($details['value']))
			{
				$pref = form_dropdown($name, $details['value'], $details['selected'], 'id="'.$name.'"');
			}
			else
			{
				$pref = '<span class="notice">'.lang('not_available').'</span>';
			}
			
			break;
		case 'r':
			$label = lang($name, $name);
			
			if (is_array($details['value']))
			{
				foreach ($details['value'] as $options)
				{
					$pref .= form_radio($options).NBS.lang($options['label'], $options['id']).NBS.NBS.NBS.NBS;
				}
			}
			else
			{
				$pref = '<span class="notice">'.lang('not_available').'</span>';
			}
			
			break;
		case 't':
			$label = lang($name, $name);
			$pref = form_textarea($details['value']);
			break;
		case 'f':
			$label = lang($name, $name);
			break;
		case 'i':
			$label = lang($name, $name);
			$pref = form_input(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => 120)));
			break;
	}
?>						


		<div class="label">
		<?=$label?><br />
		<?=(($details['subtext'] != '') ? $details['subtext'] : '')?>
		</div>
		<ul class="rounded">
			<li><?=$pref?></li>
		</ul>


<?php endforeach;?>
<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'whiteButton'))?>
<?=form_close()?>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_members.php */
/* Location: ./themes/cp_themes/mobile/admin/config_pages.php */
