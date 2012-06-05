<?php extend_template('default') ?>

<?php
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
	lang('source'),
	lang('records'),
	lang('action')
);

foreach ($sources as $source => $count)
{
	$this->table->add_row(
		lang($source),
		$count,
		'<a href="'.BASE.AMP.'C=tools_data'.AMP.'M=recount_stats'.AMP.'TBL='.$source.'">'.lang('do_recount').'</a>'
	);
}
?>

<p><?=lang('recount_info')?></p>

<?=$this->table->generate()?>