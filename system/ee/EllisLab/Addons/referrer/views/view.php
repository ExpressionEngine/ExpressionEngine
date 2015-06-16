<?php if(count($referrers) > 0 OR isset($search['value'])):?>


	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=referrer'.AMP.'method=view')?>
	<p>
		<?=form_input($search).NBS.NBS?> <?=form_submit(array('name' => 'submit', 'value' => lang('search'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>

	<p>
		<?=lang('total_referrers').NBS.NBS.$num_referrers?>
	</p>
<?php if(count($referrers) > 0):?>
	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=referrer'.AMP.'method=delete_confirm')?>

	<?php
		$this->table->set_heading(
			lang('referrer_from'),
			lang('referrer_ip'),
			lang('ref_user_agent'),
			lang('referrer_to'),
			lang('referrer_date'),
			form_checkbox('select_all', 'true', FALSE, 'class="toggle_all" id="select_all"').NBS.lang('delete', 'select_all')
		);

		foreach($referrers as $referrer)
		{
			$this->table->add_row(
									'<a href="'.$referrer['from_link'].'">'.$referrer['from_url'].'</a>',
									$referrer['referrer_ip'],
									$referrer['user_agent'],
									'<a href="'.$referrer['to_link'].'">'.$referrer['to_url'].'</a> ('.lang('site').': '.$referrer['site'].')',
									$referrer['date'],
									form_checkbox($referrer['toggle'])
									);
		}

	?>
			<?=$this->table->generate()?>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</p>

	<?=$pagination?>

	<?=form_close()?>

<?php endif; endif;?>