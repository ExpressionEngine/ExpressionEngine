<?php
	$this->table->set_heading('&nbsp;', lang('filename'), lang('filepath'), lang('checksum'));

	foreach($js_tool_files as $file_info)
	{
		$dom_id = ($file_info['status'] == '?') ? 'E' : $file_info['status'];
		$modified_column = $file_info['status'].form_checkbox(array('name' => 'compress[]', 'class' => 'compression_checkbox compression_'.$dom_id, 'value' => $file_info['id']));

		$this->table->add_row(
			array('style'=> 'width:1%;', 'data'=> $modified_column),
			array('style'=> 'width:25%', 'data' => $file_info['filename']),
			$file_info['filepath'],
			array('style'=> 'width:5%;', 'data' => $file_info['checksum'])
		);
	}
	echo $this->table->generate();
	$this->table->clear();
?>