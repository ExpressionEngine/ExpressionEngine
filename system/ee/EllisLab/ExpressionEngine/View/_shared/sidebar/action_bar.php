<div class="actions">
	<?php if ($left_button): ?>
		<a class="btn action left"
			href="<?=$left_button['url']?>"
			rel="<?=$left_button['rel']?>"><?=$left_button['text']?></a>
	<?php endif ?>
	<?php if ($right_button): ?>
		<a class="btn action right"
			href="<?=$right_button['url']?>"
			rel="<?=$right_button['rel']?>"><?=$right_button['text']?></a>
	<?php endif ?>
</div>
