<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=do_ip_search')?>

	<?php $this->load->view('_shared/message');?>
	<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
			array('data' => '&nbsp;', 'style' => 'width:50%;'),
			'&nbsp;'
		);

		$this->table->add_row(array(
			lang('ip_address', 'ip_address').'<br />'.
			lang('ip_search_instructions'),
			form_input(array(
				'id'	=> 'ip_address',
				'name'	=> 'ip_address'
			))
		));
      
        echo $this->table->generate();
    ?>
	<p>
		<?=form_submit('user_ban_sumbit', lang('submit'), 'class="submit"')?>
	</p>
<?=form_close()?>
