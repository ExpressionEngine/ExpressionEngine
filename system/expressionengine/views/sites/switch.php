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
    <?php if ($can_admin_sites): ?>
	<?php $this->load->view('_shared/right_nav')?>
	<?php endif; ?>
	<div class="contents">
		
		<div class="heading"><h2 class="edit"><?=lang('site_management')?></h2></div>
		<div class="pageContents">
			<div class="clear_left"></div>
			
	
			<?php
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(lang('choose_site'));
			
				foreach ($sites as $site_id => $site_name)
				{
					$this->table->add_row(
											'<a href="'.BASE.AMP."C=sites".AMP."site_id=".$site_id.'">'.$site_name.'</a>'
										);
				}
			
				echo $this->table->generate();
			?>
		
			<div class="tableFooter">
	
			</div>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/accessories/index.php */