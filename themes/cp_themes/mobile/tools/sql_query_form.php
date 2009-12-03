<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="sql_query_form" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools_data<?=AMP?>M=sql_manager"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
    <?=form_open('C=tools_data'.AMP.'M=sql_run_query')?>
	<div class="label" style="margin-top:15px">
		<?=lang('sql_query_instructions', 'sql_query_instructions')?><br />
	        <strong><?=lang('advanced_users_only')?></strong>
	</div>
    <ul>
    <li><?=form_textarea(array('name' => 'thequery', 'id' => 'thequery', 'rows' => 10, 'style' => "width:100%", 'class' => 'shun'))?></li>
    <li><?=form_checkbox(array('name' => 'debug', 'id' => 'debug', 'value' => 'y', 'class' => 'shun', 'title' => lang('sql_query_debug')))?></li>
    </ul>
	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'whiteButton'))?>
    <?=form_close()?>
    
</div>
<?php
/* End of file sql_manager.php */
/* Location: ./themes/cp_themes/mobile/tools/sql_query_form.php */
