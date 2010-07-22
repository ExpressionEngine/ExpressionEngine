<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=category_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>
	
	
		<?=form_open('C=admin_content'.AMP.'M=category_update', '', $form_hidden)?>

		<div class="label">
			<?=form_label(lang('category_name'), 'cat_name')?>
		</div>
		<ul>
			<li><?=form_input(array('id'=>'cat_name','name'=>'cat_name','class'=>'fullfield','value'=>set_value('cat_name', $cat_name)))?>
<?=form_error('cat_name')?>
				</li>
		</ul>

		<div class="label">
			<?=form_label(lang('category_url_title'), 'cat_url_title')?>
		</div>
		<ul>
			<li>form_input(array('id'=>'cat_url_title','name'=>'cat_url_title','class'=>'fullfield','value'=>set_value('cat_url_title', $cat_url_title)))?>
				<?=form_error('cat_url_title')?>
				</li>
		</ul>

		<div class="label">
			<?=form_label(lang('category_description'), 'cat_description')?>
		</div>
		<ul>
			<li><?=form_textarea(array('id'=>'cat_description','name'=>'cat_description','class'=>'fullfield','value'=>set_value('cat_description', $cat_description)))?></li>
		</ul>

		<div class="label">
			<?=form_label(lang('category_image'), 'cat_image')?><br /><?=lang('category_img_blurb')?>
		</div>
		<ul>
			<li><?=form_input(array('id'=>'cat_image','name'=>'cat_image','class'=>'fullfield','value'=>set_value('cat_image', $cat_image)))?></li>
		</ul>

		<div class="label">
			<?=form_label(lang('category_parent'), 'parent_id')?>
		</div>
		<ul>
			<?php 
			$options['0'] = $this->lang->line('none');
			foreach($parent_id_options as $val)
			{
				$indent = ($val['5'] != 1) ? repeater(NBS.NBS.NBS.NBS, $val['5']) : '';
				$options[$val['0']] = $indent.$val['1']; 
			}	
			echo '<li>'.form_dropdown('parent_id', $options, $parent_id, 'id="parent_id"').'</li>';
			?>

		</ul>


				<?php foreach($cat_custom_fields as $field):?>
					<div class="publish_field publish_<?=$field['field_type']?>" id="hold_field_<?=$field['field_id']?>" style="width: <?=(isset($field['width'])) ? $field['width'] : '100%' ?>;">

						<div class="label">
							<label for="<?=$field['field_id']?>">
								<?php if ($field['field_required'] == 'y'):?><span class="required">*</span><?php endif;?>
								<?=$field['field_label']?>
							</label> 
							<?=form_error('field_id_'.$field['field_id'])?>
						</div>

						<?php
						// only text field types get these options
						if($field['field_type'] == 'text'):
							$data = array(
              					'name'        => 'field_id_'.$field['field_id'],
              					'id'          => 'field_id_'.$field['field_id'],
              					'value'       => set_value('field_id_'.$field['field_id'], $field['field_content']),
              					'maxlength'   => $field['field_maxl'],
              					'size'        => '50',
              					'style'       => 'width:50%',
            					);						
						?>
						<ul>
							<li><?=form_input($data)?></li>
						</ul>
						<?php elseif ($field['field_type'] == 'textarea'):
							$data = array(
              					'name'        => 'field_id_'.$field['field_id'],
              					'id'          => 'field_id_'.$field['field_id'],
              					'value'       => set_value('field_id_'.$field['field_id'], $field['field_content']),
              					'rows'   	  => $field['rows'],
              					'cols'        => '50',
              					'style'       => 'width:50%',
            					);
						?>
						<ul>
							<li><?=form_textarea($data)?></li>
						</ul>
						<?php elseif ($field['field_type'] == 'select'):
						?>
						<ul>
							<li><?=form_dropdown('field_id_'.$field['field_id'], $field['field_options'], set_value('field_id_'.$field['field_id'], $field['field_content']))?></li>
						</ul>
						<?php endif;?>

						<?php
						if($field['field_show_fmt'] == 'y'):
						?>
						<div class="label">			
							Formatting:
						</div>	
						<ul>
							<li><?=form_dropdown('field_ft_'.$field['field_id'], $custom_format_options, set_value('field_ft_'.$field['field_id'], $field['field_fmt']))?></li>
						</ul>
						<?php endif;?>

				<?php endforeach;?>

		<p><?=form_submit('category_edit', lang($submit_lang_key), 'class="whiteButton"')?></p>

		<?=form_close()?>	
	
	
	
		


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file category_edit.php */
/* Location: ./themes/cp_themes/default/admin/category_edit.php */