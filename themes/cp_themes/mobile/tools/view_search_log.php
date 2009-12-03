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

        if ($search_data->num_rows() > 0):

        	foreach ($search_data->result() as $data):
				$screen_name = ($data->screen_name != '') ? '<a href="'.BASE.AMP.'C=myaccount'.AMP.'member_id='. $data->member_id .'">'.$data->screen_name.'</a>' : '';
			?>

			<ul>
				<li><strong><?=lang('screen_name')?>:</strong> <?=$screen_name?></li>
				<li><strong><?=lang('ip_address')?>:</strong> <?=$data->ip_address?></li>
				<li><strong><?=lang('date')?>:</strong> <?=date('Y-m-d h:m A', $data->search_date)?></li>
				<li><strong><?=lang('site')?>:</strong> <?=$data->site_label?></li>
				<li><strong><?=lang('searched_in')?>:</strong> <?=$data->search_type?></li>
				<li><strong><?=lang('search_terms')?>:</strong> <?=$data->search_terms?></li>
			</ul>
        	<?php endforeach;
		else: ?>
			<div class="container pad"><?=lang('no_search_results')?></div>
		<?php endif;?>    
<a class="whiteButton" href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=search'?>"><?=lang('clear_logs')?></a>    
</div>


<?php
/* End of file view_cp_log.php */
/* Location: ./themes/cp_themes/mobile/tools/view_cp_log.php */