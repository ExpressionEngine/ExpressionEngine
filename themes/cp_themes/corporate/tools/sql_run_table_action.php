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
			
			<div class="heading"><h2 class="edit"><?=lang($action)?></h2></div>
		
			<div class="pageContents">
				<?php
					$this->table->set_template($cp_pad_table_template);
					$this->table->set_heading($headings);
				?>
				<div class="cupRunnethOver shun"><?=$this->table->generate($results)?></div>				
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