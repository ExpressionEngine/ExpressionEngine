<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=update_config', array('id'=>'member_group_details'))?>
	
	<div>

		<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->template['thead_open'] = '<thead class="visualEscapism">';
		foreach ($menu_head as $prefname => $prefs):
		?>
			<?php					
				foreach ($prefs as $pref)
				{
					$this->table->set_caption(lang($prefname));
					// preferences sometimes have subtext, other times not
					$preference = $pref['preference'];
						
					if ($pref['preference_subtext'] != '')
					{
						$preference .= '<div class="subtext">'.$pref['preference_subtext'].'</div>';
					}
				
					if ($pref['preference_controls']['type']=='dropdown')
					{
						$controls = form_dropdown($pref['preference_controls']['id'], $pref['preference_controls']['options'], $pref['preference_controls']['default']);
					}
					elseif ($pref['preference_controls']['type']=='radio')
					{
						$controls = '';
							
						foreach ($pref['preference_controls']['radio'] as $radio)
						{
							$controls .= form_radio($radio['radio']).' '.$radio['label'].NBS.NBS.NBS.NBS.NBS;
						}

					}
					else
					{
						$controls = form_input($pref['preference_controls']['data']);
					}
						
					$this->table->set_heading(lang('preference'), lang('setting'));
					$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));
				}
					
				echo $this->table->generate();
				// Clear out of the next one
				$this->table->clear();
			?>

		<?php endforeach;?>

	</div>
	<p><?=form_submit('submit', lang('update'), 'class="submit"')?></p>
<?=form_close()?>