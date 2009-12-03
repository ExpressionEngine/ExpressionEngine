<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design<?=AMP?>M=snippets" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<div class="container pad">
		<?=form_open('C=design'.AMP.'M=snippets_update')?>
			<?php if ($snippet_id):?>
				<div><?=form_hidden('snippet_id', $snippet_id)?></div>
			<?php endif;?>

			<p>
			<label for="snippet_name"><?=lang('snippet_name')?></label><br />
			<?=lang('variable_name_instructions')?><br />
			<?=form_input(array('id'=>'snippet_name','name'=>'snippet_name','size'=>40,'class'=>'field','value'=>$snippet_name))?>				
			</p>
			
			<p>
			<label for="snippet_contents"><?=lang('variable_data')?></label><br />
			<?=form_textarea(array('id'=>'snippet_contents','name'=>'snippet_contents','cols'=>40,'rows'=>10,'class'=>'fullfield','value'=>$snippet_contents))?>
			</p>
			
			<?php if ($msm):?>
				<p>
				<label for="snippet_name"><?=lang('available_to_sites')?></label><br />
				<label><?=form_radio('site_id', 0, $all_sites).NBS.lang('all')?></label>&nbsp;&nbsp;
				<label><?=form_radio('site_id', $site_id, ( ! $all_sites)).NBS.lang('this_site_only')?></label>
				</p>
			<?php else:?>
				<div><?=form_hidden('site_id', $site_id)?></div>
			<?php endif;?>
			
			<p><?=form_submit('update', lang('update'), 'class="submit"')?> <?=form_submit('update_and_return', lang('update_and_return'), 'class="submit"')?></p>
		<?=form_close()?>	

	</div>
</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file snippets_edit.php */
/* Location: ./themes/cp_themes/mobile/design/snippets_edit.php */