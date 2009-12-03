<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
		<div class="contents">

		<div class="heading"><h2 class="edit"><?=lang('member_cfg')?> <?=lang('general_cfg')?></h2></div>
		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>

			<?=form_open('C=members'.AMP.'M=update_config', array('id'=>'member_group_details'))?>
			
			<div>

				<?php
				$this->table->set_template($cp_pad_table_template);
				$this->table->template['thead_open'] = '<thead class="visualEscapism">';
				foreach ($menu_head as $prefname=>$prefs):
				?>

					<h3 class="accordion"><?=lang($prefname)?></h3>
					<div style="padding: 5px 1px;">
						<?php					
							foreach ($prefs as $pref)
							{
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
					</div>

				<?php endforeach;?>

			</div>
			<p><?=form_submit('submit', lang('update'), 'class="submit"')?></p>
			<?=form_close()?>
		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file preferences.php */
/* Location: ./themes/cp_themes/default/members/preferences.php */