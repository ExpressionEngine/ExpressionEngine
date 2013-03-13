<?php extend_template('default') ?>
				
<?php if ( ! empty($rows)): ?>
	<div class="cp_button">
		<a href="<?=BASE.AMP.'C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=developer'?>">
			<?=lang('clear_logs')?>
		</a>
	</div>
	<div class="clear_left"></div>
<?php endif ?>

<?php if (count($rows)): ?>
<p>
	<?=lang('deprecation_detected')?> <a href="#" class="deprecation_meaning"><?=lang('dev_log_help')?></a>
</p>
<?php endif ?>

<?=form_open('C=tools_logs'.AMP.'M=clear_log_files'.AMP.'type=developer')?>

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