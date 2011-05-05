<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

			<?=form_open($form_action, '', $form_hidden)?>

	<?php
		$this->table->set_template($cp_table_template);
		$this->table->set_heading(
			lang('formatting_options'),
			''
		);

		foreach($format_options as $key => $val)
		{
			$this->table->add_row(
					$val['name'],
					lang('yes').NBS.form_radio($key, 'y', (($val['selected'] == 'y') ? TRUE :  FALSE)).NBS.NBS.lang('no').NBS.form_radio($key, 'n', (($val['selected'] == 'n') ? TRUE : FALSE))
				);
		}

	?>
	<?=$this->table->generate()?>

	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

	<?=form_close()?>
			
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_formatting_options.php */
/* Location: ./themes/cp_themes/default/admin/edit_formatting_options.php */