<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members<?=AMP?>M=custom_profile_fields" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>


	<?=form_open('C=members'.AMP.'M=update_field_order')?>

	<?php

	if (count($fields) > 0)
	{		
		foreach ($fields as $field):?>
		<div class="label">
			<strong><?=lang('field_id')?>:</strong> <?=$field['id']?><br />
			<strong><?=lang('fieldlabel')?>:</strong> <?=$field['label']?><br />
			<strong><?=lang('fieldname')?>:</strong> <?=$field['name']?>
		</div>
		<ul class="rounded">
			<li><?=form_input($field)?></li>
		</ul>
		<?php endforeach;
	}

	?>
	
	<?=form_submit('', lang('update'), 'class="whiteButton"')?>

	<?=form_close()?>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_field_order.php */
/* Location: ./themes/cp_themes/mobile/members/edit_field_order.php */