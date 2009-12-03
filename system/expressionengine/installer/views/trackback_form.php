<?php 
if ($not_readable === TRUE)
{
	echo '<div class="shade">';
	echo '<h2 class="important">'.$this->lang->line('error').'</h2>';
	echo '<h5 class="important">'.$this->lang->line('trackback_not_writable').'</h5>';
	echo '</div>';
}
?>

<form method='post' action='<?php echo $action; ?>' id="installForm">

<div class="shade">
	<h2><?php echo $this->lang->line('trackback_removal_options'); ?></h2>
	<p><?php echo $this->lang->line('trackback_gone_in_200'); ?></p>
	<p class="important"><?php echo $this->lang->line('trackbacks_not_recoverable'); ?></p>
	
	<h5><?php echo $this->lang->line('convert_to_comments'); ?></h5>
	<p>
		<input type="radio" class='radio' name="convert_to_comments" value="n" id="convert_to_comments_n" <?php echo $convert_to_comments == 'n' ? 'checked="checked"' : ''; ?> /> <label for="convert_to_comments_n"><?php echo $this->lang->line('no'); ?></label>
		<input type="radio" class='radio' name="convert_to_comments" value="y" id="convert_to_comments_y" <?php echo $convert_to_comments == 'y' ? 'checked="checked"' : ''; ?> /> <label for="convert_to_comments_y"><?php echo $this->lang->line('yes'); ?></label><br />
	</p>
	
	<h5><?php echo $this->lang->line('archive_trackbacks'); ?></h5>
	<p>
		<input type="radio" class='radio' name="archive_trackbacks" value="n" id="archive_trackbacks_n" <?php echo $archive_trackbacks == 'n' ? 'checked="checked"' : ''; ?> /> <label for="archive_trackbacks_n"><?php echo $this->lang->line('no'); ?></label>
		<input type="radio" class='radio' name="archive_trackbacks" value="y" id="archive_trackbacks_y" <?php echo $archive_trackbacks == 'y' ? 'checked="checked"' : ''; ?> /> <label for="archive_trackbacks_y"><?php echo $this->lang->line('yes'); ?></label><br />
	</p>
	
	<div id="zip_path_container">
	<h5><?php echo $this->lang->line('trackback_zip_path'); ?></h5>
	<p><?php echo $this->lang->line('path_must_be_writable'); ?></p>
	<p><input type='text' name='trackback_zip_path' id="trackback_zip_path" value='<?php echo $trackback_zip_path; ?>' /> trackback.zip</p>
	</div>
</div>

<p><?php echo form_submit('', $this->lang->line('update_ee'), 'class="submit"'); ?></p>

<?php echo form_close(); 

/* End of file trackback_form.php */
/* Location: ./system/expressionengine/installer/views/trackback_form.php */