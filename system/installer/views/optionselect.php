<?php if ($is_installed == TRUE) : ?>

<p><?php echo str_replace('%x', $installed_version, lang('your_version')); ?></p>

<p><?php echo str_replace('%x', $version, lang('ready_to_update')); ?></p>

<p onclick="return confirm('<?=lang('backup_confirmation')?>');"><?=$link?></p>

<?php else: ?>

<h5><?=lang('preflight_done')?></h5>

<p><?php echo str_replace('%x', $version, lang('ready_to_install')); ?></p>

<p><?=$link?></p>

<?php endif;
/* End of file optionselect.php */
/* Location: ./system/expressionengine/installer/views/optionselect.php */