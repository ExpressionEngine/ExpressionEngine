<?php extend_view('_wrapper') ?>

<span class="cp_button"><a href="<?=$_id_base.AMP.'method=forum_edit'.AMP.'is_cat=1'?>"><?=lang('forum_cat_add_new')?></a></span>
<span class="cp_button"><a href="<?=$_id_base.AMP.'method=forum_resync'?>"><?=lang('forum_resync')?></a></span>
<div class="shun clear_left"></div>

<?php
$parent_id = NULL;

if (count($forums))
{
	foreach($forums as $row)
	{
		$arrows  = '<a href="'.$_id_base.AMP.'method=forum_move'.AMP.'dir=up'.AMP.'forum_id='.$row['forum_id'].'"><img src="'.PATH_CP_GBL_IMG.'arrow_up_sm.gif" border="0"  width="12" height="12" alt="" title="" /></a>'.NBS;
		$arrows .= '<a href="'.$_id_base.AMP.'method=forum_move'.AMP.'dir=dn'.AMP.'forum_id='.$row['forum_id'].'"><img src="'.PATH_CP_GBL_IMG.'arrow_down_sm.gif" border="0"  width="12" height="12" alt="" title="" /></a>';
	
		switch($row['forum_status'])
		{
			case 'o' : $status = '<span class="go_notice">'.lang('forum_open').'</span>';
				break;
			case 'c' : $status = '<span class="notice">'.lang('forum_closed').'</span>';
				break;
			case 'a' : $status = lang('forum_archived');
				break;
		}
	
		if ($row['forum_is_cat'] == 'y')
		{
			if ($parent_id)
			{
				$this->table->add_row(array(
					'colspan'	=> 6, 
					'data'		=> '<a href="'.$_base.AMP.'method=forum_edit'.AMP.'board_id='.$_board_id.AMP.'parent_id='.$parent_id.'">'.lang('forum_add_new').'</a>'
				));
			
				echo $this->table->generate();
				$this->table->clear();
			}
		
			$this->table->set_heading(
				array(
					'data' => $row['forum_name'].NBS.$row['forum_description'],
					'style' => 'width: 42%; cursor: auto;',
				),
				array(
					'data' => $status,
					'style'	=> 'width: 12%; cursor: auto;'
				),
				array(
					'data' => $arrows,
					'style' => 'width: 8%; cursor: auto;'
				),
				array(
					'data' => '<a href="'.$_id_base.AMP.'method=forum_edit'.AMP.'forum_id='.$row['forum_id'].AMP.'is_cat=1">'.lang('edit').'</a>',
					'style' => 'width: 12%; cursor: auto;'
				),
				array(
					'data' => '<a href="'.$_id_base.AMP.'method=forum_permissions'.AMP.'forum_id='.$row['forum_id'].AMP.'is_cat=1">'.lang('forum_permissions').'</a>',
					'style' => 'width: 12%; cursor: auto;'
				),
				array(
					'data' => '<a href="'.$_id_base.AMP.'method=forum_delete_confirm'.AMP.'forum_id='.$row['forum_id'].AMP.'is_cat=1">'.lang('delete').'</a>',
					'style' => 'width: 12%; cursor: auto;'
				)
			);
		
			$parent_id = $row['forum_id'];
		}
		else
		{
			$this->table->add_row(
				'<strong>'.$row['forum_name'].'</strong><br>'.$row['forum_description'],
				$status,
				$arrows,
				array(
					'data' => '<a href="'.$_id_base.AMP.'method=forum_edit'.AMP.'forum_id='.$row['forum_id'].AMP.'parent_id='.$row['forum_parent'].'">'.lang('edit').'</a>',
					'style' => 'width: 12%'
				),
				array(
					'data' => '<a href="'.$_id_base.AMP.'method=forum_permissions'.AMP.'forum_id='.$row['forum_id'].'">'.lang('forum_permissions').'</a>',
					'style' => 'width: 12%'
				),
				array(
					'data' => '<a href="'.$_id_base.AMP.'method=forum_delete_confirm'.AMP.'forum_id='.$row['forum_id'].'">'.lang('delete').'</a>',
					'style' => 'width: 12%'
				)
			);
		}
	}

	$this->table->add_row(array(
		'colspan'	=> 6, 
		'data'		=> '<a href="'.$_base.AMP.'method=forum_edit'.AMP.'board_id='.$_board_id.AMP.'parent_id='.$parent_id.'">'.lang('forum_add_new').'</a>'
	));
	echo $this->table->generate();
}
?>