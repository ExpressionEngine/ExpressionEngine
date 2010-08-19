<?php

echo form_open($action_url);

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

$this->table->add_row(array(
		lang('comment_word_censoring', 'comment_word_censoring'),
		'<span class="checks">'.
			form_checkbox('comment_word_censoring', 'y', $comment_word_censoring).NBS.lang('yes').
		'</span>'		
	)
);


$this->table->add_row(array(
		lang('comment_moderation_override', 'comment_moderation_override'),
		'<span class="checks">'.
			form_checkbox('comment_moderation_override', 'y', $comment_moderation_override).NBS.lang('yes').
		'</span>'		
	)
);

echo $this->table->generate();

?>

	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>

<?=form_close()?>