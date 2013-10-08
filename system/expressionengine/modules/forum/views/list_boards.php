<?php extend_view('_wrapper') ?>

<?php
$this->table->set_heading(
	array('data' => lang('board_id'),				'style' => 'width: 8%'),
	array('data' => lang('board_label'),			'style'	=> 'width: 30%'),
	array('data' => lang('board_name'),				'style' => 'width: 30%'),
	array('data' => lang('forum_board_enabled'),	'style' => 'width: 10%'),
	array('data' => lang('edit_forum_board'),		'style' => 'width: 12%'),
	array('data' => lang('delete'),					'style' => 'width: 10%')
);

foreach($boards as $board)
{
	$this->table->add_row(
		$board['board_id'],
		$board['board_label'],
		$board['board_name'],
		($board['board_enabled'] == 'y') ? lang('yes') : lang('no'),
		'<a href="'.$_base.AMP.'method=forum_prefs'.AMP.'board_id='.$board['board_id'].'">'.lang('edit_forum_board').'</a>',
		($board['board_id'] == 1) ? '---' : '<a href="'.$_base.AMP.'method=delete_board_confirm'.AMP.'board_id='.$board['board_id'].'">'.lang('delete').'</a>'
	);
}

$this->table->add_row(array(
			'colspan'	=> 6, 
			'data'		=> '<a href="'.$_base.AMP.'method=new_board">'.lang('add_forum_board').'</a>'
));

echo $this->table->generate();
$this->table->clear();
?>

<div class="shun"></div>

<?php
$this->table->set_heading(
	array('data' => lang('board_id'),				'style' => 'width: 8%'),
	array('data' => lang('board_alias_label'),		'style'	=> 'width: 30%'),
	array('data' => lang('board_alias_name'),		'style' => 'width: 30%'),
	array('data' => lang('forum_board_enabled'),	'style' => 'width: 10%'),
	array('data' => lang('edit_alias'),				'style' => 'width: 12%'),
	array('data' => lang('delete'),					'style' => 'width: 10%')
);


foreach($aliases as $alias)
{
	$this->table->add_row(
		$alias['board_id'],
		$alias['board_label'],
		$alias['board_name'],
		($alias['board_enabled'] == 'y') ? lang('yes') : lang('no'),
		'<a href="'.$_base.AMP.'method=forum_prefs'.AMP.'board_id='.$board['board_id'].'">'.lang('edit_alias').'</a>',
		($alias['board_id'] == 1) ? '---' : '<a href="'.$_base.AMP.'method=delete_board_confirm'.AMP.'board_id='.$alias['board_id'].'">'.lang('delete').'</a>'
	);
}



$this->table->add_row(array(
			'colspan'	=> 6, 
			'data'		=> '<a href="'.$_base.AMP.'method=new_board'.AMP.'alias=y">'.lang('add_forum_board_alias').'</a>'
));

// No boards? No aliases.
if (count($boards))
{
	echo $this->table->generate();
}
?>