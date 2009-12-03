<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=myaccount" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php //$this->load->view('_shared/message');?>

		<?=form_open('C=myaccount'.AMP.'M=quicklinks_update', '', $form_hidden)?>

		<div class="pad">
			<p><?=lang('quick_link_description')?> <?=lang('quick_link_description_more')?></p>
			<p><?=lang('quicklinks_delete_instructions')?></p>
		</div>

		<?php 

		foreach($quicklinks as $i => $quicklink):?>
			<ul>
				<li><?=form_input(array('name'=>"title_$i", 'value'=>$quicklink['title'], 'size'=>40))?></li>
				<li><?=form_input(array('name'=>"link_$i", 'value'=>$quicklink['link'], 'size'=>40))?></li>
				<li><?=form_input(array('name'=>"order_$i", 'value'=>$quicklink['order'], 'size'=>3))?></li>
			</ul>

		<?php endforeach;?>
		
		<ul>
			<li><?=form_input(array('name'=>"title_$blank_count", 'value'=>'', 'size'=>40, 'placeholder' => lang('link_title')))?></li>
			<li><?=lang('link_url')?><br />
				<?=form_input(array('name'=>"link_$blank_count", 'value'=>'http://', 'size'=>40))?></li>
			<li><?=lang('link_order')?><br />
				<?=form_input(array('name'=>"order_$blank_count", 'value'=>$blank_count, 'size'=>3))?>
		</ul>
		
		<p class="submit"><?=form_submit('quicklinks_update', lang('submit'), 'class="whiteButton"')?></p>

		<?=form_close()?>



</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file quicklinks.php */
/* Location: ./themes/cp_themes/default/account/quicklinks.php */