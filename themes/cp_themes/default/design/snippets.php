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
				<h2><?=lang('snippets')?></h2>
		</div>
		<div id="new_snippet" class="pageContents">

		<?php $this->load->view('_shared/message');?>

		<h4 class="genericHeading clear_left"><?=str_replace('%s', BASE.AMP.'C=design'.AMP.'M=global_variables', lang('snippets_explanation'))?></h4>

		<?php
			$this->table->set_template(array('table_open' => '<table class="mainTable clear_left" cellspacing="0" cellpadding="0">'));
			$this->table->set_heading(
										lang('snippets'),
										lang('snippet_syntax'),
										lang('delete')
									);
									
			if ($snippets_count >= 1)
			{
				foreach ($snippets->result() as $variable)
				{
					$this->table->add_row(
						'<a href="'.BASE.AMP.'C=design'.AMP.'M=snippets_edit'.AMP.'snippet='.$variable->snippet_name.'">'.$variable->snippet_name.'</a>', 
						'{'.$variable->snippet_name.'}', 
						'<a href="'.BASE.AMP.'C=design'.AMP.'M=snippets_delete'.AMP.'snippet_id='.$variable->snippet_id.'">'.lang('delete').'</a>'
					);
				}
			}
			else
			{
				$this->table->add_row(array('data' => lang('no_snippets'), 'colspan' => 3));
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

/* End of file snippets.php */
/* Location: ./themes/cp_themes/default/design/snippets.php */