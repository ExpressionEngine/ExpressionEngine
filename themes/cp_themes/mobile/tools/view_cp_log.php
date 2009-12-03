<?php
if ($EE_view_disable !== TRUE)
{
    $this->load->view('_shared/header');
}
?>
<div id="cp_log" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<?php				
  	if ($cp_data->num_rows() > 0):

		foreach ($cp_data->result() as $data):?>
		<ul>
			<li><strong><?=lang('member_id')?>:</strong> <?=$data->member_id?></li>
			<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'member_id='. $data->member_id?>"><?=$data->username?></a></li>
			<li><strong><?=lang('ip_address')?>:</strong> <?=$data->ip_address?></li>
			<li><strong><?=lang('date')?>:</strong> <?=date('Y-m-d h:m A', $data->act_date)?></li>
			<li><strong><?=lang('site_search')?>:</strong> <?=$data->site_label?></li>
			<li><strong><?=lang('action')?>:</strong> <?=$data->action?></li>
		</ul>
		
		<?php endforeach;
	else: ?>
		<div class="container pad"><?=lang('no_search_results')?></div>
	<?php endif;?>
<a class="whiteButton" href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=cp'?>"><?=lang('clear_logs')?></a>
</div>


<?php
/* End of file view_cp_log.php */
/* Location: ./themes/cp_themes/mobile/tools/view_cp_log.php */