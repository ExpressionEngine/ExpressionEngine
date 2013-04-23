<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('edit_preferences')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=update_preferences', '', $form_hidden)?>

	<fieldset><legend><?=lang('personal_settings')?></legend>
	<table style="width:100%">
		<tbody>
	<?php foreach($checkboxes as $checkbox):?>
		<tr><td>
		<?=form_checkbox(array('id'=>$checkbox,'name'=>$checkbox,'value'=>$checkbox, 'checked'=>($$checkbox=='y') ? TRUE : FALSE))?>
		</td><td>
		<strong><?=lang($checkbox)?></strong>
		</td></tr>
	<?php endforeach;?>
		</tbody>
	</table>
	</fieldset>
	
	<p class="submit"><?=form_submit('update_preferences', lang('update'), 'class="submit"')?></p>

	<?=form_close()?>
</div>