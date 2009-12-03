<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

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
			
			<div class="heading"><h2 class="edit"><?=lang('sql_view_database')?></h2></div>
		
			<div class="pageContents">
				<?=form_open('C=tools_data'.AMP.'M=sql_run_table_action')?>
				<?php
					$this->table->set_template($cp_pad_table_template);
					$this->table->set_heading(
												array('data' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"'), 'width' => '4%'),
												array('data' => lang('table_name'), 'width' => '45%'),
												lang('browse'),
												lang('records'),
												lang('size')
											);

					foreach ($status as $table)
					{
						$this->table->add_row(
												'<input class="toggle" type="checkbox" name="table[]" value="'.$table['name'].'" />',
												"<strong>{$table['name']}</strong>",
												'<a href="'.$table['browse_link'].'">'.lang('browse').'</a>',
												$table['rows'],
												$table['size']
											);

					}
					
					$this->table->add_row(
											'&nbsp;',
											"<strong>{$tables}&nbsp;".lang('tables').'</strong>',
											'&nbsp;',
											"<strong>{$records}</strong>",
											"<strong>{$total_size}</strong>"
										);
										
					$this->table->add_row(
											form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"'),
											array('data' => lang('select_all'), 'colspan' => 4)
										);
				?>
				<div class="cupRunnethOver shun"><?=$this->table->generate()?></div>
				<?=form_dropdown('table_action', array('OPTIMIZE' => lang('optimize_table'), 'REPAIR' => lang('repair_table')))?>
				&nbsp;<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
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

/* End of file sql_view_database.php */
/* Location: ./themes/cp_themes/default/tools/sql_view_database.php */