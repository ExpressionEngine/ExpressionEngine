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

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

		<?=form_open('C=admin_content'.AMP.'M=category_update', '', $form_hidden)?>

			<p>
				<?=form_label(lang('category_name'), 'cat_name')?>
				<?=form_input(array('id'=>'cat_name','name'=>'cat_name','class'=>'fullfield','value'=>set_value('cat_name', $cat_name)))?>
				<?=form_error('cat_name')?>
			</p>

			<p>
				<?=form_label(lang('category_url_title'), 'cat_url_title')?>
				<?=form_input(array('id'=>'cat_url_title','name'=>'cat_url_title','class'=>'fullfield','value'=>set_value('cat_url_title', $cat_url_title)))?>
				<?=form_error('cat_url_title')?>
			</p>

		<p>
			<?=form_label(lang('category_description'), 'cat_description')?>
			<?=form_textarea(array('id'=>'cat_description','name'=>'cat_description','class'=>'fullfield','rows'=>'10','value'=>set_value('cat_description', $cat_description)))?>
		</p>

		<p>
			<?=form_label(lang('category_image'), 'cat_image')?><br /><?=lang('category_img_blurb')?>
			<?=form_input(array('id'=>'cat_image','name'=>'cat_image','class'=>'fullfield','value'=>set_value('cat_image', $cat_image)))?>
		</p>

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
								<?php if ($field['field_required'] == 'y'):?><span class="required">*</span><?php endif;?>
								<?=$field['field_label']?>
							</label> 
<?=form_error('field_id_'.$field['field_id'])?>							
						</p>

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
			<p><?=form_input($data)?></p>							
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
			<p><?=form_textarea($data)?></p>

						<?php elseif ($field['field_type'] == 'select'):
						?>
			
			<p><?=form_dropdown('field_id_'.$field['field_id'], $field['field_options'], set_value('field_id_'.$field['field_id'], $field['field_content']))?>
			</p>
						<?php endif;?>

						<?php
						if($field['field_show_fmt'] == 'y'):
						?>						
						<p>
							Formatting:
			<?=form_dropdown('field_ft_'.$field['field_id'], $custom_format_options, set_value('field_ft_'.$field['field_id'], $field['field_fmt']))?>
						</p>
						<?php endif;?>

				<?php endforeach;?>

		<p><?=form_submit('category_edit', lang($submit_lang_key), 'class="submit"')?></p>

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

/* End of file category_edit.php */
/* Location: ./themes/cp_themes/default/admin/category_edit.php */