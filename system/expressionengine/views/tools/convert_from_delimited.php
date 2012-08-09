<?php extend_template('default') ?>

<?=form_open('C=tools_utilities'.AMP.'M=pair_fields')?>

	<p><?=lang('convert_from_delimited_blurb')?></p>		

	<h3><?=lang('import_info')?></h3>
	<p class="go_notice"><?=lang('info_blurb')?></p>

	<h3><?=lang('delimited_file_loc')?></h3>
	<p>
		<label for="file_loc_blurb"><?=lang('file_loc_blurb')?></label> 
		<?=form_input(array('id'=>'member_file','name'=>'member_file', 'class'=>'field', 'value'=>set_value('member_file')))?>
		<?=form_error('member_file')?>
	</p>
	

	<h3><?=lang('delimiter')?></h3>
	<p><?=lang('delimiter_blurb')?><br />

		<?php
		$data = array(
		  'name'        => 'delimiter',
		  'id'          => 'tab',
		  'value'       => 'tab',
		  'checked'     => set_radio('delimiter', 'tab', TRUE)
		);
		echo form_radio($data);?>
		<label for="relationships"><?=lang('tab')?></label><br />
		<?php
		$data = array(
		  'name'        => 'delimiter',
		  'id'          => 'comma',
		  'value'       => 'comma',
		  'checked'		=> set_radio('delimiter', 'comma', FALSE)
		);
		echo form_radio($data);?>
		<label for="relationships"><?=lang('comma')?></label><br />
		<?php
		$data = array(
		  'name'        => 'delimiter',
		  'id'          => 'other',
		  'value'       => 'other',
		  'checked'		=> set_radio('delimiter', 'other', FALSE)
		);
		echo form_radio($data);?>
		<label for="relationships"><?=lang('other')?></label> 
		<?=form_input(array('id'=>'delimiter_special','name'=>'delimiter_special','size'=>15, 'value'=>set_value('delimiter_special')))?>
		<br />
		<?=form_error('delimiter_special')?>
	</p>



	<h3><?=lang('enclosure')?></h3>
	<p><?=lang('enclosure_blurb')?></p>
	<p class="go_notice"><?=lang('enclosure_example')?></p>
	<p>
		<label for="enclosure_label"><?=lang('enclosure_label')?></label> 
		<?=form_input(array('id'=>'enclosure_label','name'=>'enclosure','size'=>15, 'value'=>set_value('enclosure')))?>
		<?=form_error('enclosure')?>
	</p>


	<p><?=form_submit('convert_from_delimited', lang('submit'), 'class="submit"')?></p>

<?=form_close()?>