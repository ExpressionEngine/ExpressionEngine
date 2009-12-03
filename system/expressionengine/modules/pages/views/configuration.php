
	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=pages'.AMP.'method=save_configuration')?>
    <?php 
    $this->table->set_template($cp_pad_table_template);
    $this->table->set_heading(
        array('data' => lang('preference'), 'style' => 'width:50%;'),
        lang('setting')
    );
    
    foreach($configuration_fields as $config)
    {
        $this->table->add_row(array(
                form_label($config['label'], $config['field_name']),
                form_dropdown($config['field_name'], 
                              $config['options'], 
                              $config['value'], 
                              'id="'.$config['field_name'].'"'
                              )
            )
        );
    }
    
    echo $this->table->generate();
    ?>
	<?=form_submit(array('name' => 'submit', 'value' => lang('update'), 'class' => 'submit'))?>

	<?=form_close()?>
<?php
/* End of file configuration.php */
/* Location: ./system/expressionengine/modules/pages/views/configuration.php */