<?php
$this->table->set_heading(
	array('data' => lang('forum_rank_title'),			'style' => 'width: 35%'),
	array('data' => lang('forum_rank_min_posts'),		'style'	=> 'width: 15%'),
	array('data' => lang('forum_rank_stars'),			'style' => 'width: 20%'),
	array('data' => lang('edit'),						'style' => 'width: 15%'),
	array('data' => lang('delete'),						'style' => 'width: 15%')
);

foreach($ranks as $rank)
{
	$this->table->add_row(
		$rank['rank_title'],
		$rank['rank_min_posts'],
		str_repeat('<img src="'.$star.'" />', $rank['rank_stars']),
		'<a href="'.$_base.AMP.'method=forum_edit_rank'.AMP.'rank_id='.$rank['rank_id'].'">'.lang('edit').'</a>',
		'<a href="'.$_base.AMP.'method=forum_delete_rank_confirm'.AMP.'rank_id='.$rank['rank_id'].'">'.lang('delete').'</a>'
	);
}

echo $this->table->generate();
$this->table->clear();
?>

<?php $this->load->view('rank_form')?>