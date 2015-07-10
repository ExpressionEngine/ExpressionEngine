<?php extend_template('default') ?>

<div class="cp_button"><a href="#"><?=lang('add_html_button')?></a></div>
<div class="clear_left"></div>

<?=form_open('C=admin_content'.AMP.'M=default_html_buttons', '', $form_hidden)?>

	<div id="add_new_html_button">
	<h3><?=lang('add_predefined_html_button')?></h3>

	<div class="markItUpHeader">
	<ul class="markItUp">
		<?php foreach($predefined_buttons as $key=>$button):?>
		<li class="<?=$button['classname']?>"><a href="<?=BASE.AMP."C=admin_content".AMP."M=default_html_buttons".AMP.'button='.$key?>"><?=$button['tag_name']?></a></li>
		<?php endforeach;?>
	</ul>
	</div>

	<div id="custom_html_button">
		<h3><?=lang('add_new_html_button')?></h3>

		<p>
			<?=lang('tag_name', "tag_name_{$i}")?> 
			<?=form_input(array('id'=>"tag_name_{$i}",'name'=>"tag_name_{$i}"))?>
		</p>
		<p>
			<?=lang('tag_open', "tag_open_{$i}")?> 
			<?=form_input(array('id'=>"tag_open_{$i}",'name'=>"tag_open_{$i}"))?>
		</p>
		<p>
			<?=lang('tag_close', "tag_close_{$i}")?> 
			<?=form_input(array('id'=>"tag_close_{$i}",'name'=>"tag_close_{$i}"))?>
		</p>
		<p>
			<?=lang('accesskey', "accesskey_{$i}")?> 
			<?=form_input(array('id'=>"accesskey_{$i}",'name'=>"accesskey_{$i}"))?>
		</p>
		<div class="shun">
			<?=form_submit('html_buttons', lang('submit'), 'class="submit"')?>
		</div>
	</div>
	</div>

	<h3><?=lang('edit').' '.lang('define_html_buttons')?></h3>

	<?php 
	$this->table->set_heading(
		'',
		lang('tag_name'),
		lang('tag_open'),
		lang('tag_close'),
		lang('accesskey'),
		lang('order'),
		array('data'=>'', 'class'=>'del_row')
	);

	if ($html_buttons->num_rows() == 0)
	{
		$this->table->add_row(array('colspan'=>7, 'data'=>lang('no_buttons')));
	}
	else
	{
		foreach($html_buttons->result() as $button)
		{
			$tag_row = ($button->tag_row == 2) ? '2' : '1';
			$i++;
			$this->table->add_row(
								'<div class="markItUpHeader"><ul class="markItUp"><li class="'.$button->classname.'"><a>'.$button->tag_name.'</a></li></ul></div>',
								form_input(array('id'=>"tag_name_{$i}",'name'=>"tag_name_{$i}",'class'=>'field','value'=>$button->tag_name)).
								form_label(lang('tag_name'), "tag_name_{$i}", array('class'=>'html_button_label')),

								form_input(array('id'=>"tag_open_{$i}",'name'=>"tag_open_{$i}",'class'=>'field','value'=>$button->tag_open)).
								form_label(lang('tag_open'), "tag_open_{$i}", array('class'=>'html_button_label')),
						
								form_input(array('id'=>"tag_close_{$i}",'name'=>"tag_close_{$i}",'class'=>'field','value'=>$button->tag_close)).
								form_label(lang('tag_close'), "tag_close_{$i}", array('class'=>'html_button_label')),

								form_input(array('id'=>"accesskey_{$i}",'name'=>"accesskey_{$i}",'class'=>'field','value'=>$button->accesskey, 'size'=>10)).
								form_label(lang('accesskey'), "accesskey_{$i}", array('class'=>'html_button_label')),

								array(
									'data'=>'<img src="'.$cp_theme_url.'images/drag.png" />'.
										"<input type='hidden' name='ajax_tag_order[]' value='".$button->id."' class='tag_order' />".
										form_input(array('id'=>"tag_order_{$i}",'name'=>"tag_order_{$i}",'class'=>'field','value'=>$button->tag_order, 'size'=>5)).
										form_label(lang('tag_order'), "tag_order_{$i}", array('class'=>'html_button_label')),
									'class'=>'tag_order'
								),
							
								array(
									'data'=>'<a href="'.BASE.AMP.'C=admin_content'.AMP.'M=delete_html_button'.AMP.'id='.$button->id.'"><img src="'.$cp_theme_url.'images/content_custom_tab_delete.png" alt="'.lang('delete').'" width="19" height="18" /></a>', 
									'class'=>'del_row'
								)
							);
		}
	}

	echo $this->table->generate();
	?>

	<p class="notice del_instructions"><?=lang('htmlbutton_delete_instructions')?></p>

	<p><?=form_submit('html_buttons', lang('submit'), 'class="submit"')?></p>

<?=form_close()?>