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
			
			<div class="heading"><h2 class="edit"><?=lang('sql_query_form')?></h2></div>
		
			<div class="pageContents">
				<p><?=lang('sql_query_instructions')?></p>
				<p><strong><?=lang('advanced_users_only')?></strong></p>
				<?=form_open('C=tools_data'.AMP.'M=sql_run_query')?>
				<div><?=form_textarea(array('name' => 'thequery', 'id' => 'thequery', 'rows' => 10, 'style' => "width:100%", 'class' => 'shun'))?></div>
				<div>
					<?=form_checkbox(array('name' => 'debug', 'id' => 'debug', 'value' => 'y', 'class' => 'shun'))?>
					<?=lang('sql_query_debug', 'debug')?>
				</div>
				<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
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

/* End of file sql_query_form.php */
/* Location: ./themes/cp_themes/default/tools/sql_query_form.php */