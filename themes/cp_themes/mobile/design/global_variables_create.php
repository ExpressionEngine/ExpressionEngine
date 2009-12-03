<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design<?=AMP?>M=global_variables" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<div class="container pad">

		<?=form_open('C=design'.AMP.'M=global_variables_create')?>

			<p>
			<label for="variable_name"><?=lang('global_variable_name')?></label><br />
			<?=lang('template_group_instructions') . ' ' . lang('undersores_allowed')?><br />
			<?=form_input(array('id'=>'variable_name','name'=>'variable_name','size'=>40,'class'=>'field'))?>				
			</p>

			<p>
			<label for="variable_data"><?=lang('variable_data')?></label><br />
			<?=form_textarea(array('id'=>'variable_data','name'=>'variable_data','cols'=>40,'rows'=>10,'class'=>'field'))?>
			</p>

			<p><?=form_submit('template', lang('update'), 'class="whiteButton"')?></p>

		<?=form_close()?>


	</div>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file global_variables_create.php */
/* Location: ./themes/cp_themes/mobile/design/global_variables_create.php */