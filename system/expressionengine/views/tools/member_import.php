<?php extend_template('default') ?>

<h4 style="margin-bottom:10px"><?=lang('member_import_welcome')?></h4>

<?php 

$this->table->set_template($cp_pad_table_template);
$this->table->add_row(array(
		lang('import_from_xml').' '.lang('import_from_xml_blurb'),
		'<a title="'.lang('import_from_xml').'" href="'.BASE.AMP.'C=tools_utilities'.AMP.'M=import_from_xml">'.lang('import_from_xml').'</a>'					
	)
);

$this->table->add_row(array(
		lang('convert_from_delimited').' '.lang('convert_from_delimited_blurb'),
		'<a title="'.lang('convert_from_delimited').'" href="'.BASE.AMP.'C=tools_utilities'.AMP.'M=convert_from_delimited">'.lang('convert_from_delimited').'</a>'
	)
);



echo $this->table->generate();
?>