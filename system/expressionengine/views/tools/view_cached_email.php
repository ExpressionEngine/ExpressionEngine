<?php extend_template('default') ?>

<?=form_open('C=tools_communicate'.AMP.'M=delete_emails_confirm')?>
	<?php
		echo $table_html;
		echo $pagination_html;
	?>

	<div class="tableFooter">
		<div class="tableSubmit">
			<?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'submit'))?>
		</div>
	</div> <!-- tableFooter -->

<?=form_close()?>