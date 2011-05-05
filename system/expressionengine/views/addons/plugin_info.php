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

		<div class="heading">
				<h2><?=lang('plugins')?></h2>
		</div>
		<div class="pageContents">

			<?php
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(lang('plugin_information'), '');

				foreach($plugin as $key => $data)
				{
					$this->table->add_row(
						lang($key) ? lang($key) : ucwords(str_replace("_", " ", $key)),
						$data
					);
				}

				echo $this->table->generate();
			?>

			<div class="clear_right"></div>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file plugin_info.php */
/* Location: ./themes/cp_themes/default/addons/plugin_info.php */