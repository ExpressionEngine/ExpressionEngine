<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=addons" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<?php
		// Local Plugins Table

		$heading = array();
		$heading[] = count($plugins).' '.lang('plugin_installed');
		$heading[] = lang('pi_version');
		
		if ($is_writable)
		{
			$heading[] = form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"');
		}

		$this->table->set_heading($heading);


		if (count($plugins) >= 1)
		{
			foreach ($plugins as $key => $plugin)
			{
				$row = array();
				$row[] = '<a href="'.BASE.AMP.'C=addons_plugins'.AMP.'M=info'.AMP.'name='.$key.'">'.$plugin['pi_name'].'</a>';
				$row[] = $plugin['pi_version'];
				if ($is_writable)
				{
					$row[] = form_checkbox('toggle[]', $key, FALSE, 'class="toggle"');
				}
				$this->table->add_row($row);
			}
		}
		else
		{
			$this->table->add_row(array('data' => lang('no_plugins_exist'), 'colspan' => 2));
		}
	?>
		
	<?php
		if ($is_writable)
		{
			echo form_open('C=addons_plugins'.AMP.'M=remove_confirm');
		}
	?>

	<div id="local_plugins">
		<?=$this->table->generate()?>

		<?php
			if ($is_writable)
			{
				echo '<p>'.form_submit('remove_plugins', lang('plugin_remove'), 'class="whiteButton"').'</p>';
			}
		?>
	</div>

	<?php
		if ($is_writable)
		{
			echo form_close();
		}
	?>


		<ul id="remote_plugins">

	<?php if (count($remote) > 1):?>
	
		<li><?=lang('plugins')?>:
			<?php if ($sort == 'alpha'): ?>
				<a href="<?=$sort_url?>"><?=lang('plugin_by_date')?></a> / <?=lang('plugin_by_letter')?>				
			<?php else:?>
				<?=lang('plugin_by_date')?> / <a href="<?=$sort_url?>"><?=lang('plugin_by_letter')?></a>
			<?php endif;?>

		</li>
			
		<?php foreach($remote as $item):?>
		
			<li class="<?=alternator('even', 'odd')?>">
				<a href="<?=$item['link']?>"><?=$item['title']?></a><br />
				
				<?=$item['description']?>
				
				<?php if ($remote_install):?>
					<br /><a href="<?=BASE.AMP.'C=addons_plugins'.AMP.'M=install'.AMP.'file='.$item['dl_url']?>"><?=lang('plugin_install')?></a>
				<?php endif;?>
			</li>
		<?php endforeach;?>
	<?php else:?>	
			<li>Plugin Feed Disabled in Beta Version.</li>
	<?php endif;?>
		</ul>
		<?php if (count($remote) > 1):?>
			<?=$this->pagination->create_links()?>
		<?php endif;?>

</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file plugin_manager.php */
/* Location: ./themes/cp_themes/mobile/addons/plugin_manager.php */