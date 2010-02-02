<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>

<div id="view_email_log" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a class="back" href="<?=BASE.AMP?>C=tools"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<div class="label" style="margin-top:15px">
		<label for="toggle_all"><?=form_checkbox(array('id'=>'toggle_all','name'=>'toggle_all','value'=>'toggle_all','checked'=>FALSE))?></label>
	</div>
	<?=form_open('C=tools_logs'.AMP.'M=delete_email')?>
	<?php
	
	if ($emails_count > 0):

		foreach ($emails->result() as $data):?>
			<ul>
				<li><strong><?=lang('email_title')?>:</strong> <a href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=view_email'.AMP.'id='.$data->cache_id?>"><?=$data->subject?></a></li>
				<li><strong><?=lang('from')?>:</strong> <a href="<?=BASE.AMP.'C=myaccount'.AMP.'member_id='. $data->member_id ?>"><?=$data->member_name?></a></li>
				<li><strong><?=lang('to')?>:</strong> <?=$data->recipient_name?></li>
				<li><strong><?=lang('date')?>:</strong> <?=date("Y-m-d h:m A", $data->cache_date)?></li>
				<li><?=form_checkbox(array('id'=>'delete_box_'.$data->cache_id,'name'=>'toggle[]','value'=>$data->cache_id, 'class'=>'toggle_email', 'checked'=>FALSE))?></li>
			</ul>				
		<?php endforeach;?>
		
		<?php if ($pagination): ?>					
			<?=$pagination?>
		<?php endif; ?>		
		
		<?=form_submit('email_logs', lang('delete'), 'class="whiteButton"')?>

		<?=form_close()?>

	<?php else:?>
		<div class="container pad">
			<p><?=lang('no_cached_email')?></p>
		</div>
	<?php endif;?>

</div>


<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file view_email_log.php */
/* Location: ./themes/cp_themes/mobile/tools/view_email_log.php */