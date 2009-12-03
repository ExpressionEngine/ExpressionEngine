<?php
$template_instructions[1][] = 'business';
$template_instructions[1][] = 'receiver_email';
$template_instructions[1][] = 'receiver_id';
$template_instructions[1][] = 'item_name';
$template_instructions[1][] = 'item_number';
$template_instructions[1][] = 'quantity';
$template_instructions[1][] = 'invoice';
$template_instructions[1][] = 'memo';
$template_instructions[1][] = 'tax';
$template_instructions[1][] = 'mc_gross';
$template_instructions[1][] = 'mc_fee';
$template_instructions[1][] = 'mc_currency';

$template_instructions[2][] = 'first_name';
$template_instructions[2][] = 'last_name';
$template_instructions[2][] = 'member_id';
$template_instructions[2][] = 'screen_name';
$template_instructions[2][] = 'payer_business_name';
$template_instructions[2][] = 'payer_id';
$template_instructions[2][] = 'payer_email';
$template_instructions[2][] = 'payer_status';
$template_instructions[2][] = 'address_name';
$template_instructions[2][] = 'address_street';
$template_instructions[2][] = 'address_country_code';
$template_instructions[2][] = 'address_city';
$template_instructions[2][] = 'address_state';
$template_instructions[2][] = 'address_zip';
$template_instructions[2][] = 'address_country';
$template_instructions[2][] = 'address_status';
$template_instructions[2][] = 'verify_sign';

$template_instructions[3][] = 'payment_gross';
$template_instructions[3][] = 'payment_fee';
$template_instructions[3][] = 'payment_status';
$template_instructions[3][] = 'payment_type';
$template_instructions[3][] = 'payment_date';
$template_instructions[3][] = 'txn_id';
$template_instructions[3][] = 'txn_type';

$template_instructions[4][] = 'option_name1';
$template_instructions[4][] = 'option_selection1';
$template_instructions[4][] = 'option_name2';
$template_instructions[4][] = 'option_selection2';

?>


		<ul>
			<?php foreach($template_instructions[1] as $item):?>
				<li><a href="#" title='<?=$item?>'><?=$item?></a></li>
			<?php endforeach;?>
		</ul>

		<ul class="glossary_separator">
			<?php foreach($template_instructions[2] as $item):?>
				<li><a href="#" title='<?=$item?>'><?=$item?></a></li>
			<?php endforeach;?>
		</ul>

		<ul class="glossary_separator">
			<?php foreach($template_instructions[3] as $item):?>
				<li><a href="#" title='<?=$item?>'><?=$item?></a></li>
			<?php endforeach;?>
		</ul>

		<ul class="glossary_separator">
			<?php foreach($template_instructions[4] as $item):?>
				<li><a href="#" title='<?=$item?>'><?=$item?></a></li>
			<?php endforeach;?>
		</ul>

		<div class="clear"></div>
