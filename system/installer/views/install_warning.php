<h2 class="important"><?php echo $this->lang->line('install_detected'); ?></h2>

<div class="shade">

<p class="pad"><?php echo $this->lang->line('install_detected_msg'); ?></p>

<p class="important"><?php echo $this->lang->line('continuing_will_destroy'); ?></p>

<p class="pad"><?php echo $this->lang->line('do_not_click_if_updating'); ?></p>

<p class="pad"><?php echo $this->lang->line('click_if_sure'); ?></p>

<form action="<?php echo $action; ?>" method="post">
<input type="hidden" name="install_override" value="y" />
<?php echo $hidden_fields; ?>

<p class="pad"><input type="submit" value=" <?php echo $this->lang->line('yes_install_ee'); ?> "></p>

</form>

</div>