<?php extend_template('default') ?>

<?php if (isset($ping_errors) and is_array($ping_errors)):?>
<?=lang('xmlrpc_ping_errors')?>
<ul>
	<?php foreach($ping_errors as $v):?>
	<li><?=$v['0']?> - <?=$v['1']?></li>
	<?php endforeach;?>
</ul>
<?php endif;?>

<p><a href="<?=$entry_link?>"><?=lang('click_to_view_your_entry')?></a></p>