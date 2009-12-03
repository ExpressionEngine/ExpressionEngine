<?=form_open($form_action)?>
<?php foreach($damned as $list):?>
	<?=form_hidden('delete[]', $list)?>
<?php endforeach;?>

<p class="shun"><?=lang($question_key)?></p>

<?php if (isset($list_names)):?>
	<p class="go_notice"><?=implode(", ", $list_names)?></p>
<?php endif;?>

<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
</p>

<?=form_close()?>