<?php if ($is_installed == TRUE) : ?>

<p><?php echo str_replace('%x', $installed_version, $this->lang->line('your_version')); ?></p>

<p><?php echo str_replace('%x', $version, $this->lang->line('ready_to_update')); ?></p>

<p onclick="return confirm('<?php echo $this->lang->line('backup_confirmation');?>');"><?php echo $link; ?></p>

<?php else: ?>

<h5><?php echo $this->lang->line('preflight_done'); ?></h5>

<p><?php echo str_replace('%x', $version, $this->lang->line('ready_to_install')); ?></p>

<p><?php echo $link; ?></p>

<?php endif; 
/* End of file optionselect.php */
/* Location: ./system/expressionengine/installer/views/optionselect.php */