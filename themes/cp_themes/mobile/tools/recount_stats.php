<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>

<div id="recount_stats" class="current">
	<div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools">Back</a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	
	<?php $this->load->view('_shared/right_nav')?>

	<?php $this->load->view('_shared/message');?>

		<?php

		foreach ($sources as $source => $count):?>
		<div class="label">
			<?=lang($source, $source)?>
		</div>
		<ul>
			<li><strong><?=lang('records')?>:</strong> <?=$count?></li>
			<li><a href="<?=BASE.AMP.'C=tools_data'.AMP.'M=recount_stats'.AMP.'TBL='.$source?>"><?=lang('do_recount')?></a></li>
		</ul>
		<?php endforeach;?>
		
		<div class="container pad"><?=lang('recount_info')?></div>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file recount_stats.php */
/* Location: ./themes/cp_themes/mobile/tools/recount_stats.php */