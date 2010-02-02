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

	if ($this->config->item('enable_throttling') == 'n'):?>
		<div class="container pad"><?=lang('throttling_disabled')?></div>
	<?php
	elseif ($throttle_data->num_rows() > 0):

		foreach ($throttle_data->result() as $data):?>
			<ul>
				<li><strong><?=lang('ip_address')?>:</strong> <?=$data->ip_address?></li>
				<li><strong><?=lang('hits')?>:</strong> <?=$data->hits?></li>
				<li><strong><?=lang('last_activity')?>:</strong> <?=ate("Y-m-d h:m A", $data->last_activity)?></li>
			</ul>

		<?php endforeach;?>
		<?php if ($blacklist_installed): ?>
			<a class="whiteButton" href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=blacklist_throttled_ips'?>"><?=lang('blacklist_all_ips')?></a>
		<?php endif; ?>
		
		<?php if ($pagination): ?>					
			<?=$pagination?>
		<?php endif; ?>		
		
		<?php else: ?>
			<div class="container pad"><?=lang('no_throttle_logs')?></div>
		<?php endif;?>
</div>
<?php
/* End of file view_cp_log.php */
/* Location: ./themes/cp_themes/mobile/tools/view_throttle_log.php */