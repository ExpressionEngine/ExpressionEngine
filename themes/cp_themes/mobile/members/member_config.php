<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<?php foreach ($menu_head as $prefname=>$prefs):?>
		<h3><?=lang($prefname)?></h3>
	<ul>
		<?php foreach ($prefs as $pref): 
			
			// preferences sometimes have subtext, other times not
			$preference = '<strong>'.$pref['preference'].'</strong>';
			
			if ($pref['preference_subtext'] != '')
			{
				$preference .= '<br />'.$pref['preference_subtext'];
			}
	
			if ($pref['preference_controls']['type']=='dropdown')
			{
				$controls = form_dropdown($pref['preference_controls']['id'], $pref['preference_controls']['options'], $pref['preference_controls']['default']);
			}
			elseif ($pref['preference_controls']['type']=='radio')
			{
				$controls = '';
				
				foreach ($pref['preference_controls']['radio'] as $radio)
				{
					$controls .= form_radio($radio['radio']).' '.$radio['label'].NBS.NBS.NBS.NBS.NBS;
				}

			}
			else
			{
				$controls = form_input($pref['preference_controls']['data']);
			}			
		
		?>
			
		<li><?=$preference?><br />
			<?=$controls?></li>
		<?php endforeach; ?>
		
	</ul>
	<?php endforeach; ?>
	<p><?=form_submit('submit', lang('update'), 'class="whiteButton"')?></p>
	<?=form_close()?>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file member_config.php */
/* Location: ./themes/cp_themes/mobile/members/member_config.php */