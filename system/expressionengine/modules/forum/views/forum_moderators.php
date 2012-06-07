<?php
$parent_id = NULL;

if (count($forums))
{
	foreach($forums as $row)
	{
		if ($row['forum_is_cat'] == 'y')
		{
			if ($parent_id)
			{
				echo $this->table->generate();
				$this->table->clear();
			}
			
			$this->table->set_heading(
				array('data' => $row['forum_name'],					'style' => 'width: 30%'),
				array('data' => lang('forum_moderator_add'),		'style'	=> 'width: 15%'),
				array('data' => lang('forum_moderators'),			'style' => 'width: 55%')
			);
		
			$parent_id = $row['forum_id'];
		}
		else
		{
			if (count($row['mods']))
			{
				$data = '<table class="templateTable templateEditorTable" id="templateWarningsList" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">';
				
				foreach($row['mods'] as $item)
				{
					$data .= '<tr>';
					
					$data .= '<td style="width: 33%;">'.$item['data'].'</td>';
					$data .= '<td style="width: 33%;"><a href="'.$_id_base.AMP.'method=forum_edit_moderator'.AMP.'forum_id='.$row['forum_id'].AMP.'mod_id='.$item['mod_id'].'">'.lang('edit').'</a></td>';
					$data .= '<td style="width: 33%;"><a href="'.$_id_base.AMP.'method=forum_remove_moderator_confirm'.AMP.'forum_id='.$row['forum_id'].AMP.'mod_id='.$item['mod_id'].'">'.lang('remove').'</a></td>';
					
					$data .='</tr>';
				}
				
				$data .= '</table>';
			}
			else
			{
				$data = lang('forum_no_mods');
			}
			
			$this->table->add_row(
				array(
					'data'		=> $row['forum_name'],
					'valign'	=> 'top'
				),
				array(
					'data'		=> '<a href="'.$_id_base.AMP.'method=forum_edit_moderator'.AMP.'forum_id='.$row['forum_id'].'">'.str_replace(' ', NBS, lang('forum_moderator_add')),
					'valign'	=> 'top'
				),
				array(
					'data'		=> $data
				)
			);
		}
	}

	echo $this->table->generate();
}
?>