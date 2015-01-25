<?php
	// the file holding the glossary
	include(APPPATH.'config/glossary.php');
?>

	<div class="glossary_content clear_left" style="display: none;">
		<ul>
			<?php foreach($glossary[1] as $item):?>
				<li><a href="#" title="<?=$item[1]?>"><?=$item[0]?></a></li>
			<?php endforeach;?>
		</ul>

		<ul class="glossary_separator">
			<?php foreach($glossary[2] as $item):?>
				<li><a href="#" title="<?=$item[1]?>"><?=$item[0]?></a></li>
			<?php endforeach;?>
		</ul>

		<ul class="glossary_separator">
			<?php foreach($glossary[3] as $item):?>
				<li><a href="#" title="<?=$item[1]?>"><?=$item[0]?></a></li>
			<?php endforeach;?>
		</ul>

		<ul class="glossary_separator">
			<?php foreach($glossary[4] as $item):?>
				<li><a href="#" title="<?=$item[1]?>"><?=$item[0]?></a></li>
			<?php endforeach;?>
		</ul>

		<div class="clear"></div>
	</div>