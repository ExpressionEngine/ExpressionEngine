<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=search" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<?php if ($can_rebuild):?>
	
		<div class="pad container"><a href="<?=BASE.AMP.'C=search'.AMP.'M=build_index'?>"><?=lang('rebuild_search_index')?></a></div>
	
	<?php endif;

	if ($num_rows > 0):

		$list = array();
	
		foreach ($search_data as $data)
		{
			$list[] = "<a href='{$data['url']}'>{$data['name']}</a>";
		}
	?>

		<?=ul($list, array('class' => 'rounded'))?>

	<?php else:?>
		<p class="pad"><?=lang('no_search_results')?></p>
		<p class="pad"><?=lang('searched_for')?> <?=$keywords?></p>

	<?php endif;?>


</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file results.php */
/* Location: ./themes/cp_themes/mobile/search/results.php */