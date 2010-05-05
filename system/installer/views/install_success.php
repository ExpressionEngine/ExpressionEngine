<h2><?php echo $this->lang->line('ee_has_been_installed'); ?></h2>

<div class="shade">

<p class="important"><?php echo $this->lang->line('delete_via_ftp'); ?></p>

<p class="pad"><?php echo $this->lang->line('folder_is_located_at'); ?> <em><?php echo $installer_path; ?></em></p>

<p class="important"><strong><?php echo $this->lang->line('no_access_until_delete'); ?></strong></p>

</div>

<?php if($errors > 0):?>
	<h3 class="important"><?php echo $this->lang->line('module_errors_occurred'); ?></h3>
	<ul>
		<?php foreach($error_messages as $module=>$messages):?>
			<li><strong><?php echo $module_names[$module]['name'];?></strong>
				<ul>
					<?php foreach($messages as $message):?>
					<li><?php echo $message;?></li>
					<?php endforeach;?>
				</ul>
			</li>
		<?php endforeach;?>
	</ul>
<?php endif;?>

<h2><?php echo $this->lang->line('bookmark_links'); ?></h2>

<p><a href="<?php echo $cp_url; ?>" target="_blank"><?php echo $this->lang->line('cp_located_here'); ?></a></p>

<p><a href="<?php echo $site_url; ?>" target="_blank"><?php echo $this->lang->line('site_located_here'); ?></a></p>
