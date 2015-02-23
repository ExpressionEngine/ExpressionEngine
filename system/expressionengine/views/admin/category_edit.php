<?php extend_template('default') ?>

<?=form_open_multipart('C=admin_content'.AMP.'M=category_update', '', $form_hidden)?>
	<p>
		<?=form_label(lang('category_name'), 'cat_name')?>
		<?=form_input(array(
			'id'	=> 'cat_name',
			'name'	=> 'cat_name',
			'class'	=> 'fullfield',
			'value'	=> set_value('cat_name', $cat_name)
		))?>
		<?=form_error('cat_name')?>
	</p>

	<p>
		<?=form_label(lang('category_url_title'), 'cat_url_title')?>
		<?=form_input(array(
			'id'	=> 'cat_url_title',
			'name'	=> 'cat_url_title',
			'class'	=> 'fullfield',
			'value'	=> set_value('cat_url_title', $cat_url_title)
		))?>
		<?=form_error('cat_url_title')?>
	</p>

	<p>
		<?=form_label(lang('category_description'), 'cat_description')?>
		<?=form_textarea(array(
			'id'	=> 'cat_description',
			'name'	=> 'cat_description',
			'class'	=> 'fullfield',
			'rows'	=> '10',
			'value'	=> set_value('cat_description', $cat_description)
		))?>
		<?=form_error('cat_description')?>
	</p>

	<div class="category_field">
		<?=form_label(lang('category_image'), 'cat_image')?>
		<?=$cat_image?>
		<?php if (isset($cat_image_error) AND $cat_image_error !== ''): ?>
			<span class="notice"><?=$cat_image_error?></span>
			<br />
		<?php endif ?>
	</div>

	<div class="clear"></div>

	<p>
		<?=form_label(lang('category_parent'), 'parent_id')?><br />
		<?php
		$options['0'] = $this->lang->line('none');
		foreach($parent_id_options as $val)
		{
			$indent = ($val['5'] != 1) ? repeater(NBS.NBS.NBS.NBS, $val['5']) : '';
			$options[$val['0']] = $indent.$val['1'];
		}
		echo form_dropdown('parent_id', $options, $parent_id, 'id="parent_id"');
		?>
	</p>

	<?php foreach($cat_custom_fields as $field):?>
		<p>
			<label for="<?=$field['field_id']?>">
				<?php if ($field['field_required'] == 'y'):?>
					<span class="required">*</span>
				<?php endif;?>
				<?=$field['field_label']?>
			</label>
			<?=form_error('field_id_'.$field['field_id'])?>
			<br />
			<?php if ($field['field_type'] == 'text'): ?>
				<?=form_input(array(
					'name'		=> 'field_id_'.$field['field_id'],
					'id'		=> 'field_id_'.$field['field_id'],
					'value'		=> set_value(
						'field_id_'.$field['field_id'],
						$field['field_content']
					),
					'maxlength'	=> $field['field_maxl'],
					'size'		=> '50',
					'style'		=> 'width:50%',
				))?>
			<?php elseif ($field['field_type'] == 'textarea'): ?>
				<?=form_textarea(array(
					'name'		=> 'field_id_'.$field['field_id'],
					'id'		=> 'field_id_'.$field['field_id'],
					'value'		=> set_value(
						'field_id_'.$field['field_id'],
						$field['field_content']
					),
					'rows'		=> $field['rows'],
					'cols'		=> '50',
					'style'		=> 'width:50%',
				))?>
			<?php elseif ($field['field_type'] == 'select'): ?>
				<?=form_dropdown(
					'field_id_'.$field['field_id'],
					$field['field_options'],
					set_value(
						'field_id_'.$field['field_id'],
						$field['field_content']
					)
				)?>
			<?php endif;?>

			<?php if($field['field_show_fmt'] == 'y'): ?>
				<br />
				<?=lang('formatting')?>
				<?=form_dropdown(
					'field_ft_'.$field['field_id'],
					$custom_format_options,
					set_value(
						'field_ft_'.$field['field_id'],
						$field['field_fmt']
					)
				)?>
			<?php endif;?>
		</p>
	<?php endforeach;?>

	<p>
		<?=form_submit('category_edit', lang($submit_lang_key), 'class="submit"')?>
	</p>
<?=form_close()?>
