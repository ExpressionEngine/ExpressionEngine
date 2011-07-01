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
        
		<?php $this->load->view('_shared/message');?>

		<div class="heading"><h2><?=$cp_page_title?></h2></div>
		<div class="pageContents">

			<?=form_open('C=content_files'.AMP.'M=edit_watermark_preferences', '', $hidden)?>

			<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th width="50%"><?=lang('preference')?></th>
						<th><?=lang('setting')?></th>
					</tr>
				</thead>
				<tbody>
					<tr class="even">
						<td><?=form_label('<span class="notice">*</span> '.lang('wm_name'), 'name'); ?></td>
						<td>
							<?=form_error('name').form_input(array(
								'id'	=> 'name',
								'name'	=> 'name',
								'class'	=> 'field',
								'value' => set_value('name', $wm_name)
							)); ?>
						</td>
					</tr>
					<tr class="odd">
						<td><?=lang('<span class="notice">*</span> '.lang('wm_type'), 'wm_type')?></td>
						<td class="inline_labels">
							<?=
							lang('text', 'text').NBS.
							form_radio('wm_type', 'text', $type_text, 'id="type_text"').NBS.NBS.NBS.
							lang('image', 'image').NBS.
							form_radio('wm_type', 'image', $type_image, 'id="type_image"');  
							?>
						</td>
					</tr>
					<tr class="even">
						<?php
						// Allignment
						$options1 = array(
							'top' 	=> lang('top'),
							'mid'	=> lang('mid'),
							'bot'	=> lang('bot')
						);
						
						$options2 = array(
							'left' 		=> lang('left'),
							'center'	=> lang('center'),
							'right'		=> lang('right')
						);
						?>
						<td><?=form_label(lang('wm_alignment'), 'wm_alignment'); ?></td>
						<td>
							<?=
							form_dropdown('wm_vrt_alignment', $options1, $wm_vrt_alignment).NBS.NBS.
							form_dropdown('wm_hor_alignment', $options2, $wm_hor_alignment);
							?>
						</td>
					</tr>
					<tr class="odd">
						<?php
						$options = array('0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7,
						 '8' => 8, '9' => 9, '10' => 10, '11' => 11, '12' => 12, '13' => 13, '14' => 14, '15' => 15, 
						'16' => 16, '17' => 17, '18' => 18, '19' => 19, '20' => 20, '21' => 21, '22' => 22, '23' => 23, 
						'24' => 24, '25' => 25, '26' => 26, '27' => 27, '28' => 28, '29' => 29, '30' => 30);
						?>
						<td><?=form_label(lang('wm_padding'), 'wm_padding'); ?></td>
						<td><?=form_dropdown('wm_padding', $options, $wm_padding); ?></td>
					</tr>
					<tr class="even">
						<td><?=form_label(lang('wm_x_offset'), 'wm_x_offset'); ?></td>
						<td>
							<?=
							form_error('wm_x_offset').
							form_input(array(
								'id'	=> 'wm_x_offset',
								'name'	=> 'wm_x_offset',
								'class'	=> 'field',
								'value' => set_value('wm_x_offset', $wm_x_offset)
							)); 
							?>
						</td>
					</tr>
					<tr class="odd">
						<td><?=form_label(lang('wm_y_offset'), 'wm_y_offset'); ?></td>
						<td>
							<?=
							form_error('wm_y_offset').
							form_input(array(
								'id'	=> 'wm_y_offset',
								'name'	=> 'wm_y_offset',
								'class'	=> 'field',
								'value' => set_value('wm_y_offset', $wm_y_offset)
							));
							?>
						</td>
					</tr>
					<tr class="even text_type">
						<td><?=form_label(lang('wm_text'), 'wm_text'); ?></td>
						<td>
							<?=
							form_error('wm_text').
							form_input(array(
								'id'	=> 'wm_text',
								'name'	=> 'wm_text',
								'class'	=> 'field',
								'value' => set_value('wm_text', $wm_text)
							));
							?>
						</td>
					</tr>
					<tr class="odd text_type">
						<td><?=lang('wm_use_font', 'wm_use_font'); ?></td>
						<td>
							<?=
							lang('yes', 'wm_use_font_y').NBS.
							form_radio('wm_use_font', 'y', $font_yes).NBS.NBS.NBS.
							lang('no', 'wm_use_font_n').NBS.
							form_radio('wm_use_font', 'n', $font_no); 
							?>
						</td>
					</tr>
				
					<tr class="even text_type">
						<td><?=form_label(lang('wm_font'), 'wm_font'); ?></td>
						<td><?=form_dropdown('wm_font', $font_options, $wm_font); ?></td>
					</tr>

					<tr class="odd text_type">
						<td><?=form_label(lang('wm_font_size'), 'wm_font_size'); ?></td>
						<td>
							<?=
							form_error('wm_font_size').
							form_input(array(
								'id'	=> 'wm_font_size',
								'name'	=> 'wm_font_size',
								'class'	=> 'field',
								'value' => set_value('wm_font_size', $wm_font_size)
							));
							?>
						</td>
					</tr>

					<tr class="even text_type">
						<td><?=form_label(lang('wm_font_color'), 'wm_font_color'); ?></td>
						<td>
							<?=
							form_error('wm_font_color').
							form_input(array(
								'id'	=> 'wm_font_color',
								'name'	=> 'wm_font_color',
								'class'	=> 'field color {hash:true}',
								'value' => set_value('wm_font_color', $wm_font_color)
							));
							?>
						</td>
					</tr>
			
				
					<tr class="odd text_type">
						<td><?=lang('wm_use_drop_shadow', 'wm_use_drop_shadow'); ?></td>
						<td>
							<?=
							lang('yes', 'wm_use_drop_shadow_y').NBS.
							form_radio('wm_use_drop_shadow', 'y', $use_drop_shadow_yes).NBS.NBS.NBS.
							lang('no', 'wm_use_drop_shadow_n').NBS.
							form_radio('wm_use_drop_shadow', 'n', $use_drop_shadow_no);
							?>
						</td>
					</tr>

					<tr class="even text_type">
						<?php
						//Dropshadow Distance
						$options = array('0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7,
						 '8' => 8, '9' => 9, '10' => 10, '11' => 11, '12' => 12, '13' => 13, '14' => 14, '15' => 15, 
						'16' => 16, '17' => 17, '18' => 18, '19' => 19, '20');
						?>
						<td><?=form_label(lang('wm_shadow_distance'), 'wm_shadow_distance'); ?></td>
						<td><?=form_dropdown('wm_shadow_distance', $options, $wm_shadow_distance); ?></td>
					</tr>

					<tr class="odd text_type">
						<td><?=form_label(lang('wm_shadow_color'), 'wm_shadow_color'); ?></td>
						<td>
							<?=
							form_error('wm_shadow_color').
							form_input(array(
								'id'	=> 'wm_shadow_color',
								'name'	=> 'wm_shadow_color',
								'class'	=> 'field color {hash:true}',
								'value' => set_value('wm_shadow_color', $wm_shadow_color)
							));
							?>
						</td>
					</tr>
				
					<tr class="even image_type">
						<td><?=form_label(lang('wm_image_path'), 'wm_image_path'); ?></td>
						<td>
							<?=
							form_error('wm_image_path').
							form_input(array(
								'id'	=> 'wm_image_path',
								'name'	=> 'wm_image_path',
								'class'	=> 'field',
								'value' => set_value('wm_image_path', $wm_image_path)
							));
							?>
						</td>
					</tr>

					<tr class="odd image_type">
						<td><?=form_label(lang('wm_opacity'), 'wm_opacity'); ?></td>
						<td><?=form_dropdown('wm_opacity', $opacity_options, $wm_opacity); ?></td>
					</tr>

					<tr class="even image_type">
						<td><?=form_label(lang('wm_x_transp'), 'wm_x_transp'); ?></td>
						<td>
							<?=
							form_error('wm_x_transp').
							form_input(array(
								'id'	=> 'wm_x_transp',
								'name'	=> 'wm_x_transp',
								'class'	=> 'field',
								'value' => set_value('wm_x_transp', $wm_x_transp)
							));
							?>
						</td>
					</tr>

					<tr class="odd image_type">
						<td><?=form_label(lang('wm_y_transp'), 'wm_y_transp'); ?></td>
						<td>
							<?=
							form_error('wm_y_transp').
							form_input(array(
								'id'	=> 'wm_y_transp',
								'name'	=> 'wm_y_transp',
								'class'	=> 'field',
								'value' => set_value('wm_y_transp', $wm_y_transp)
							));
							?>
						</td>
					</tr>
				
					<!-- <tr class="even">
						<td>
							<?=
							form_label(
								lang('wm_test_image_path'),
								'wm_test_image_path'
							).
							BR.lang('wm_test_explain'); 
							?>
						</td>
						<td>
							<?=
							form_error('wm_test_image_path').
							form_input(array(
								'id'	=> 'wm_test_image_path',
								'name'	=> 'wm_test_image_path',
								'class'	=> 'field',
								'value' => set_value('wm_test_image_path', $wm_test_image_path)
							));
							?>
						</td>
					</tr> -->
				</tbody>
			</table>

				<p class="notice">* <?=lang('required_fields')?></p>

				<p>
					<?=form_submit('submit', lang($lang_line), 'class="submit"')?>
					<?php // &nbsp; &nbsp; <?=form_submit('submit', lang('wm_test'), 'class="submit"') ?>
				</p>

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

/* End of file watermark_settings.php */
/* Location: ./themes/cp_themes/default/content/files/watermark_settings.php */