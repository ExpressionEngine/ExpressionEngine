<?php extend_template('default') ?>

<?=form_open('C=design'.AMP.'M=snippets_update')?>
	<?php if ($snippet_id):?>
		<div><?=form_hidden('snippet_id', $snippet_id)?></div>
	<?php endif;?>

	<p>
	<label for="snippet_name"><?=lang('snippet_name')?></label><br />
	<?=lang('variable_name_instructions')?><br />
	<?=form_input(array('id'=>'snippet_name','name'=>'snippet_name','size'=>70,'class'=>'field','value'=>$snippet_name))?>
	</p>

	<p>
	<label for="snippet_contents"><?=lang('variable_data')?></label><br />
	<?=form_textarea(array('id'=>'snippet_contents','name'=>'snippet_contents','cols'=>70,'rows'=>20,'class'=>'fullfield','value'=>$snippet_contents))?>
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