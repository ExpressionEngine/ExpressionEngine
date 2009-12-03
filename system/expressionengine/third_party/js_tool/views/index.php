<div class="shun">
	<div class="button" style="text-align: center;"><a href="<?=BASE.AMP.$resync_url?>">Resync Database Checksums</a></div>
</div>

<div class="clear_left shun"></div>
<br />
<?php $this->table->set_template($cp_pad_table_template);?>
	<h3>Config Values:</h3>

	<div style="padding: 5px 1px;">
		<?php $this->load->view('config_toggles'); ?>
	</div>
		
	<div style="float: right">
		<strong>Select:</strong>
		<select name="select_options" id="select_options">
			<optgroup label="Recommended">
				<option value="U">Uncompressed</option>
				<option value="M">Modified</option>
				<option value="R">Resync</option>
			</optgroup>
			<optgroup label="Debug">
				<option value="E">Error</option>
				<option value="C">Compressed</option>
			</optgroup>
			<optgroup label="Shortcuts">
				<option value="UMREC">All</option>
				<option value="EC">All Debug</option>
				<option value="UMR">All Recommended</option>
				<option value="N">None</option>
			</optgroup>
		</select>
	</div>

<?=form_open($compress_url);?>

	<h3>Files</h3>
	<div style="padding: 5px 1px;">
		<?= $this->load->view('files_table'); ?>
	</div>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('compress'), 'class' => 'submit'));?>
		<span style="float: right; padding-right: 1px;"><strong>Key:</strong> ? = error, N = new, R = resync, M = modified, C = compressed</span>
	</p>

<?=form_close()?>
