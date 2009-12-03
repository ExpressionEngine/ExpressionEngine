<h2><?php echo $this->lang->line('running_current'); ?></h2>

<div class="shade">

<p class="important"><?php echo $this->lang->line('delete_via_ftp'); ?></p>

<p class="pad"><?php echo $this->lang->line('folder_is_located_at'); ?> <em><?php echo $installer_path; ?></em></p>

<p class="important"><strong><?php echo $this->lang->line('no_access_until_delete'); ?></strong></p>

</div>

<h2><?php echo $this->lang->line('bookmark_links'); ?></h2>

<p><a href="<?php echo $cp_url; ?>" target="_blank"><?php echo $this->lang->line('cp_located_here'); ?></a></p>

<p><a href="<?php echo $site_url; ?>" target="_blank"><?php echo $this->lang->line('site_located_here'); ?></a></p>