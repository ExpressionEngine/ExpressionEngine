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
			
			<div class="heading"><h2 class="edit"><?=lang($type)?></h2></div>
			<div class="pageContents">
				
				<?php $this->load->view('_shared/message');?>
				
				<?=form_open($form_action, '', array('return_location' => $return_loc))?>
					
				<?php
				$this->table->set_template($cp_pad_table_template);
				$this->table->set_heading(
											array('data' => lang('preference'), 'style' => 'width:50%;'),
											lang('setting')
										);

				foreach ($fields as $name => $details)
				{
					$pref = '';

					switch ($details['type'])
					{
						case 's':
							$label = lang($name);
							
							if (is_array($details['value']))
							{
								$pref = form_dropdown($name, $details['value'], $details['selected'], 'id="'.$name.'"');
							}
							else
							{
								$pref = '<span class="notice">'.lang('not_available').'</span>';
							}
							
							break;
						case 'r':
							$label = lang($name);
							
							if (is_array($details['value']))
							{
								foreach ($details['value'] as $options)
								{
									$pref .= form_radio($options).NBS.lang($options['label'], $options['id']).NBS.NBS.NBS.NBS;
								}
							}
							else
							{
								$pref = '<span class="notice">'.lang('not_available').'</span>';
							}
							
							break;
						case 't':
							$label = lang($name, $name);
							$pref = form_textarea($details['value']);
							break;
						case 'f':
							$label = lang($name, $name);
							break;
						case 'i':
							$label = lang($name, $name);
							$pref = form_input(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => 120)));
							break;
					}

					$this->table->add_row(
										"<strong>{$label}</strong>".(($details['subtext'] != '') ? "<div class='subtext'>{$details['subtext']}</div>" : ''),
										$pref
										);
				}
				
				echo $this->table->generate();
				?>
				<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
				<?=form_close()?>
					
				<div class="clear_right"></div>		
			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file config_pages.php */
/* Location: ./themes/cp_themes/default/admin/config_pages.php */