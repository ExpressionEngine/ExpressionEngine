<?=form_open($form_action)?>
<?php foreach($damned as $list):?>
	<?=form_hidden('delete[]', $list)?>
<?php endforeach;?>

<p class="shun"><?=lang('referrer_delete_question')?></p>

<?php if (isset($list_names)):?>
	<p class="go_notice"><?=implode(", ", $list_names)?></p>
<?php endif;?>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>


<h3><?=lang('blacklist_question')?></h3>

<p><?=$add_urls?> <?=form_checkbox('add_urls', 'y', FALSE, 'id="add_urls"')?></p>
<p><?=$add_ips?> <?=form_checkbox('add_ips', 'y', FALSE, 'id="add_ips"')?></p>
<p><?=$add_agents?> <?=form_checkbox('add_agents', 'y', FALSE, 'id="add_agents"')?></p>


<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>