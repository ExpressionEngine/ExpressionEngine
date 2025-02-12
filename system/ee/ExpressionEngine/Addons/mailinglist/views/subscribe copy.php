<p><?=lang($notice_import_del)?> <?=$good_email?></p>

<p><?=lang('ml_total_duplicate_emails')?> <?=$dup_email?></p>

<?php if(count($bad_email) > 0):?>

	<p><?=lang($notice_bad_email)?></p>

	<ul class="bulleted">
		<?php foreach ($bad_email as $val):?>
			<li><?=$val?></li>
		<?php endforeach;?>
	</ul>

<?php endif;?>