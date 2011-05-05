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
			<div class="heading"><h2 class="edit"><?=lang('sql_manager')?></h2></div>
	        <div class="pageContents">
			<?php
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(array('data' => lang('sql_info'), 'width' => '50%'), lang('value'));

				foreach ($sql_info as $name => $value)
				{
					$this->table->add_row(
											"<strong>".lang($name)."</strong>",
											"{$value}"
										);

				}

				echo $this->table->generate();
			?>
			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file sql_manager.php */
/* Location: ./themes/cp_themes/default/tools/sql_manager.php */