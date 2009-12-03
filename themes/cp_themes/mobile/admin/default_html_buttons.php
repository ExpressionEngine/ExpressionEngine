<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>

		<ul class="cp_button">
			<li><a class="animate" href="#"><?=lang('add_html_button')?></a></li>
		</ul>


		<?=form_open('C=admin_content'.AMP.'M=default_html_buttons', '', $form_hidden)?>

		<div id="add_new_html_button">
			<h3 class="pad"><?=lang('add_predefined_html_button')?></h3>
			<div class="markItUpHeader">
				<ul class="markItUp">
				<?php foreach($predefined_buttons as $key=>$button):?>
					<li class="<?=$button['classname']?>"><a href="<?=BASE.AMP."C=admin_content".AMP."M=default_html_buttons".AMP.'button='.$key?>"><?=$button['tag_name']?></a></li>
				<?php endforeach;?>
				</ul>
			</div>
			
			<div id="custom_html_button">
				<h3 class="pad"><?=lang('add_new_html_button')?></h3>
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

		<h3 class="pad"><?=lang('edit').' '.lang('define_html_buttons')?></h3>

		<?php 
		if ($html_buttons->num_rows() == 0)
		{
			echo '<p class="container pad">'.lang('no_buttons').'</p>';
		}
		else
		{
			foreach($html_buttons->result() as $button):?>
				
				<div class="markItUpHeader pad container"><ul class="markItUp"><li class="<?=$button->classname?>"><a><?=$button->tag_name?></a></li></ul></div>
				
				<div class="label">
					<?=lang('tag_name', 'tag_name')?>
				</div>
				<ul>
					<li><?=form_input(array('id'=>"tag_name_{$i}",'name'=>"tag_name_{$i}",'class'=>'field','value'=>$button->tag_name))?></li>
				</ul>
				<div class="label">
					<?=lang('tag_open', 'tag_open')?>
				</div>
				<ul>
					<li><?=form_input(array('id'=>"tag_open_{$i}",'name'=>"tag_open_{$i}",'class'=>'field','value'=>$button->tag_open))?></li>
				</ul>
				<div class="label">
					<?=lang('tag_close', 'tag_close')?>
				</div>
				<ul>
					<li><?=form_input(array('id'=>"tag_close_{$i}",'name'=>"tag_close_{$i}",'class'=>'field','value'=>$button->tag_close))?></li>
				</ul>
				<div class="label">
					<?=form_label(lang('accesskey'), "accesskey_{$i}", array('class'=>'html_button_label'))?>
				</div>
				<ul>
					<li><?=form_input(array('id'=>"accesskey_{$i}",'name'=>"accesskey_{$i}",'class'=>'field','value'=>$button->accesskey, 'size'=>10))?></li>
				</ul>
				<ul>
					<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=delete_html_button'.AMP.'id='.$button->id?>"><?=lang('delete')?></a> 
				</ul>
				<hr />
			<?php endforeach;
		}
		?>

		<p class="notice del_instructions"><?=lang('htmlbutton_delete_instructions')?></p>

		<p><?=form_submit('html_buttons', lang('submit'), 'class="whiteButton"')?></p>

		<?=form_close()?>






</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file default_html_buttons.php */
/* Location: ./themes/cp_themes/default/admin/default_html_buttons.php */