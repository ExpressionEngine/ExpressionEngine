<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading"><h2><?=lang('plugins')?></h2></div>

		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>

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
					<a href="<?=$item['link']?>"><?=$item['title']?></a>	<?php if ($remote_install):?>
						<div class="installPlugin"><a href="<?=BASE.AMP.'C=addons_plugins'.AMP.'M=install'.AMP.'file='.$item['dl_url']?>"><?=lang('plugin_install')?></a></div>
					<?php endif;?><br />
					
					<?=$item['description']?>
					
				
				</li>
				
			<?php endforeach;?>
			
			<li>
				<?=$this->pagination->create_links()?>
			</li>
		<?php else: ?>
			<li><?=lang('plugins_not_available')?></li>
		<?php endif;?>
			</ul>
		
			<?php
				// Local Plugins Table

				$heading = array();
				$heading[] = count($plugins).' '.lang('plugin_installed');
				$heading[] = lang('pi_version');
				
				if ($is_writable)
				{
					$heading[] = form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"');
				}

				$this->table->set_template($cp_pad_table_template);
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
<p align="right">
				<?php
					if ($is_writable)
					{
						echo form_submit('submit', lang('plugin_remove'), 'class="delete"');
						
					}
				?>
			</div>
</p>
			<?php
				if ($is_writable)
				{
					echo form_close();
				}
			?>
			<div class="clear_right"></div>
		</div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file plugin_manager.php */
/* Location: ./themes/cp_themes/corporate/addons/plugin_manager.php */