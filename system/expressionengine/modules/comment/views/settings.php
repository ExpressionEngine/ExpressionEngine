<?php

echo form_open($action_url);

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

foreach(array('comment_word_censoring', 'comment_moderation_override', 'comment_smart_notifications') as $setting)
{
	$this->table->add_row(array(
			lang($setting, $setting),
			'<span class="checks">'.
				form_checkbox($setting, 'y', ${$setting}).NBS.lang('yes').
			'</span>'		
		)
	);
}

echo $this->table->generate();

?>

	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>

<?=form_close()?>