<?php extend_template('default') ?>

<?=form_open('C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=email')?>
	
	<?=$table_html?>
	
	<?php if ( ! empty($rows)): ?>
		<div class="tableFooter">
			<div class="tableSubmit">
				<?=form_submit('email_logs', lang('delete'), 'class="submit"')?>
			</div>		
		
			<?=$pagination_html?>
		</div> <!-- tableFooter -->
	<?php endif ?>
	
<?=form_close()?>