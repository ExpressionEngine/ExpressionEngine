<div id="filterMenu">

	<div class="group">

		<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=view')?>

		<div>
			<label for="email" class="js_hide"><?=lang('ml_email_address_field')?></label>
			<?=form_input('email', $email, 'class="field shun" id="email" placeholder="'.lang('ml_email_address_field').'"')?>
		</div>
		
		<?=lang('mailing_list', 'list')?>&nbsp;
		<?=form_dropdown('list_id', $mailinglists, $selected_list, 'id="list_id"')?> 
		
		&nbsp;&nbsp;

		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>

		&nbsp;&nbsp;
		
		<img src="<?=$cp_theme_url?>images/indicator.gif" class="searchIndicator" alt="Edit Search Indicator" style="margin-bottom: -5px; visibility: hidden;" width="16" height="16" />

	    <?=form_close()?>
	</div>
</div>
	

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=mailinglist'.AMP.'method=delete_confirm', '', $form_hidden)?>

	<?=$table_html?>


<div class="tableFooter">
	<div class="tableSubmit">
		<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
	</div>
	<?=$pagination_html?>
</div>	

	<?=form_close()?>