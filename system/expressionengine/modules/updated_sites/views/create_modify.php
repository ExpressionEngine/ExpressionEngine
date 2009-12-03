<h3><?=lang('configuration_options')?></h3>
<?=form_open($form_action, '', $form_hidden)?>
<?php 
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

$this->table->add_row(array(
        form_error('updated_sites_pref_name').
        lang('updated_sites_pref_name', 'updated_sites_pref_name'),
        form_input(array(
            'id'    => 'updated_sites_pref_name',
            'name'  => 'updated_sites_pref_name',
            'class' => 'field',
            'value' => set_value('updated_sites_pref_name', $updated_sites_pref_name)
            )
        )
    )
);

$this->table->add_row(array(
        form_error('updated_sites_short_name').
        lang('updated_sites_short_name', 'updated_sites_short_name').' <em>'.lang('single_word_no_spaces').'</em>',
        form_input(array(
            'id'    => 'updated_sites_short_name',
            'name'  => 'updated_sites_short_name',
            'class' => 'field',
            'value' => set_value('updated_sites_short_name', $updated_sites_short_name)
            )
        )
    )
);


$this->table->add_row(array(
        form_error('updated_sites_allowed').
        lang('updated_sites_allowed', 'updated_sites_allowed').'<br />'.
        lang('updated_sites_allowed_subtext'),
        form_textarea(array(
            'id'    => 'updated_sites_allowed',
            'rows'  => 5,
            'cols'  => 60,
            'name'  => 'updated_sites_allowed',
            'class' => 'field',
            'value' => set_value('updated_sites_allowed', $updated_sites_allowed)
            )
        )
    )
);

$this->table->add_row(array(
        form_error('updated_sites_prune').
        lang('updated_sites_prune', 'updated_sites_prune'),
        form_input(array(
            'id'    => 'updated_sites_prune',
            'name'  => 'updated_sites_prune',
            'class' => 'field',
            'value' => set_value('updated_sites_prune', $updated_sites_prune)
            )
        )
    )
);

echo $this->table->generate();
?>



	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang($submit_text), 'class' => 'submit'))?>
	</p>

<?=form_close()?>