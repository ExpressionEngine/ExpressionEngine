<?php extend_template('default') ?>

<?php if ($can_rebuild):?>

	<div class="cp_button"><a href="<?=BASE.AMP.'C=search'.AMP.'M=build_index'?>"><?=lang('rebuild_search_index')?></a></div>
	<div class="clear_left"></div>
<?php endif;

if ($num_rows > 0):

	$list = array();

	foreach ($search_data as $data)
	{
		$list[] = "<a href='{$data['url']}'>{$data['name']}</a>";
	}
?>

	<?=ul($list, array('class' => 'bullets'))?>

<?php else:?>

	<p><?=lang('no_search_results')?></p>
	<p><?=lang('searched_for')?> <?=$keywords?></p>

<?php endif;?>