<?php extend_template('default') ?>

<?=form_open('C=tools_data'.AMP.'M=clear_caching')?>
	<p>
		<?php
		$data = array(
		  'name'        => 'type',
		  'id'          => 'page',
		  'value'       => 'page'
		);
		echo form_radio($data);?>
		<?=lang('page_caching', 'page')?>
	</p>
	<p>
		<?php
		$data = array(
		  'name'        => 'type',
		  'id'          => 'tag',
		  'value'       => 'tag'
		);
		echo form_radio($data);?>
		<?=lang('tag_caching', 'tag')?>
	</p>
	<p>
		<?php
		$data = array(
		  'name'        => 'type',
		  'id'          => 'db',
		  'value'       => 'db'
		);
		echo form_radio($data);?>
		<?=lang('db_caching', 'db')?>
	</p>
	<p>
		<?php
		$data = array(
			'name'		=> 'type',
			'id'		=> 'all',
			'value'		=> 'all',
			'checked'	=> TRUE	
		);
		echo form_radio($data);?>
		<?=lang('all_caching', 'all')?>
	</p>

	<p><?=form_submit('clear_caching', lang('submit'), 'class="submit"')?></p>
<?=form_close()?>