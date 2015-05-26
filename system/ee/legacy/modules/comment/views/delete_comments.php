<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=comment'.AMP.'method=delete_comment', '', $hidden)?>

<p><?=lang($message)?></p>
<p class="notice"><?=lang('action_can_not_be_undone')?></p>

<?php		
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('entry_title'), 'style' => 'width:20%;'),
    lang('comment'), 
    array('data' => lang('ip_address'), 'style' => 'width:20%;')
);

foreach ($comments as $comment_data)
{
	$this->table->add_row(array($comment_data['entry_title'], $comment_data['comment'], $comment_data['ip_address']));
}

echo $this->table->generate();
?>			

<p><?=form_submit('delete_comments', lang('delete'), 'class="submit"')?></p>
<?php if ($blacklist_installed): ?>
<div class="notice blacklist">
<?=lang('blacklist').NBS.form_checkbox('add_to_blacklist', 'y')?>
</div>
<?php endif; ?>
<?=form_close()?>

