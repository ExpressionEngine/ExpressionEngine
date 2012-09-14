<?php extend_template('default') ?>

<?php if ($specialty_email_templates_summary->num_rows() < 1):?>

	<p class="notice"><?=lang('unable_to_find_templates')?></p>

<?php else:?>

	<ul class="menu_list">
	<?php foreach ($specialty_email_templates_summary->result() as $template):?>
		<li<?=alternator(' class="odd"', '')?>>
			<a href="<?=BASE.AMP.'C=design'.AMP.'M=edit_email_notification'.AMP.'template='.$template->template_name?>">
				<?=lang($template->template_name)?>
			</a>
		</li>
	<?php endforeach;?>
	</ul>

<?php endif;?>