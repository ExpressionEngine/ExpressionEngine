<?php extend_template('default') ?>

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
				<td><?=form_label(lang('wm_padding'), 'wm_padding'); ?></td>
				<td><?=form_dropdown('wm_padding', range(0, 30), $wm_padding); ?></td>
			</tr>
			<tr class="even">
				<td><?=form_label(lang('wm_hor_offset'), 'wm_hor_offset'); ?></td>
				<td>
					<?=
					form_error('wm_hor_offset').
					form_input(array(
						'id'	=> 'wm_hor_offset',
						'name'	=> 'wm_hor_offset',
						'class'	=> 'field',
						'value' => set_value('wm_hor_offset', $wm_hor_offset)
					)); 
					?>
				</td>
			</tr>
			<tr class="odd">
				<td><?=form_label(lang('wm_vrt_offset'), 'wm_vrt_offset'); ?></td>
				<td>
					<?=
					form_error('wm_vrt_offset').
					form_input(array(
						'id'	=> 'wm_vrt_offset',
						'name'	=> 'wm_vrt_offset',
						'class'	=> 'field',
						'value' => set_value('wm_vrt_offset', $wm_vrt_offset)
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
				?>
				<td><?=form_label(lang('wm_shadow_distance'), 'wm_shadow_distance'); ?></td>
				<td><?=form_dropdown('wm_shadow_distance', range(0, 20), $wm_shadow_distance); ?></td>
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