<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="sql_manager" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=homepage#tools"><?=lang('tools')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

<?php foreach ($sql_info as $name => $value): ?>
<div class="label">
	<strong><?=lang($name)?></strong>
</div>
<ul class="rounded">
	<li><?=$value?></li>
</ul>
<?php endforeach; ?>


    <h2><?=lang('sql_utilities')?></h2>

        <ul id="sql_manager" title="<?=lang('sql_utilities')?>" class="rounded">
        	<li><a target="_self" href="<?=BASE.AMP."C=tools_data".AMP."M=sql_view_database"?>"><?=lang('sql_view_database')?></a></li>
        	<li><a target="_self" href="<?=BASE.AMP."C=tools_data".AMP."M=sql_query_form"?>"><?=lang('sql_query_form')?></a></li>
        	<li><a target="_self" href="<?=BASE.AMP."C=tools_data".AMP."M=sql_status"?>"><?=lang('sql_status')?></a></li>
        	<li><a target="_self" href="<?=BASE.AMP."C=tools_data".AMP."M=sql_system_vars"?>"><?=lang('sql_system_vars')?></a></li>
        	<li><a target="_self" href="<?=BASE.AMP."C=tools_data".AMP."M=sql_processlist"?>"><?=lang('sql_processlist')?></a></li>
        </ul>
</div>
<?php
/* End of file sql_manager.php */
/* Location: ./themes/cp_themes/mobile/tools/sql_manager.php */