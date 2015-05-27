<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('profile_form')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=update_profile', '', $form_hidden)?>

	<p>
		<span><?=lang('birthday')?></span>
		<?=form_dropdown('bday_y', $bday_y_options, $bday_y, 'id="bday_y"')?> 
		<?=form_dropdown('bday_m', $bday_m_options, $bday_m, 'id="bday_m"')?> 
		<?=form_dropdown('bday_d', $bday_d_options, $bday_d, 'id="bday_d"')?> 
	</p>

	<p>
		<?=lang('url', 'url')?>
		<?=form_input(array('id'=>'url','name'=>'url','class'=>'field','value'=>$url,'maxlength'=>150))?>
	</p>

	<p>
		<?=lang('location', 'location')?>
		<?=form_input(array('id'=>'location','name'=>'location','class'=>'field','value'=>$location,'maxlength'=>50))?>
	</p>

	<p>
		<?=lang('occupation', 'occupation')?>
		<?=form_input(array('id'=>'occupation','name'=>'occupation','class'=>'field','value'=>$occupation,'maxlength'=>80))?>
	</p>

	<p>
		<?=lang('interests', 'interests')?>
		<?=form_input(array('id'=>'interests','name'=>'interests','class'=>'field','value'=>$interests,'maxlength'=>120))?>
	</p>

	<p>
		<?=lang('aol_im', 'aol_im')?>
		<?=form_input(array('id'=>'aol_im','name'=>'aol_im','class'=>'field','value'=>$aol_im,'maxlength'=>50))?>
	</p>

	<p>
		<?=lang('icq', 'icq')?>
		<?=form_input(array('id'=>'icq','name'=>'icq','class'=>'field','value'=>$icq,'maxlength'=>50))?>
	</p>

	<p>
		<?=lang('yahoo_im', 'yahoo_im')?>
		<?=form_input(array('id'=>'yahoo_im','name'=>'yahoo_im','class'=>'field','value'=>$yahoo_im,'maxlength'=>50))?>
	</p>

	<p>
		<?=lang('msn_im', 'msn_im')?>
		<?=form_input(array('id'=>'msn_im','name'=>'msn_im','class'=>'field','value'=>$msn_im,'maxlength'=>50))?>
	</p>

	<p>
		<?=lang('bio', 'bio')?>
		<?=form_textarea(array('id'=>'bio','rows'=> 12,'name'=>'bio','class'=>'field','value'=>$bio))?>
	</p>

	<?php foreach($custom_profile_fields as $field):?>
	<p>
		<?=$field?>
	</p>
	<?php endforeach;?>

	<p class="submit"><?=form_submit('edit_profile', lang('update'), 'class="submit"')?></p>

	<?=form_close()?>
</div>