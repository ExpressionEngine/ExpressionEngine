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
			
			<div class="heading"><h2><?=lang('sql_query_form')?></h2></div>

			<div class="pageContents">
				<p><?=lang('sql_query_instructions')?></p>
				<p class="notice"><?=lang('advanced_users_only')?></p>
				<?=form_open('C=tools_data'.AMP.'M=sql_run_query')?>
				<p><?=form_textarea(array('name' => 'thequery', 'id' => 'thequery','rows' => 10, 'class' => 'fullfield'))?></p>
				<p>
					<?=form_checkbox(array('name' => 'debug', 'id' => 'debug', 'value' => 'y', 'class' => 'shun'))?>
					<?=lang('sql_query_debug', 'debug')?>
				</p>
				<p><?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?></p>
				<?=form_close()?>
				
			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file sql_query_form.php */
/* Location: ./themes/cp_themes/corporate/tools/sql_query_form.php */