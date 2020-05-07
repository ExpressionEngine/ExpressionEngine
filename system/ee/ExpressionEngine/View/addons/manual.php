<?php $this->extend('_templates/default-nav'); ?>

<div class="title-bar title-bar--large">
	<h1 class="title-bar__title"><?=$name?> <?=$version?></h1>

	<div class="title-bar__extra-tools">
		<?=lang('author')?>: <a href="<?=$author_url?>" rel="external"><?=$author?></a><br>

	</div>
</div>
<p><i><?=$description?></i><br />&nbsp;</p>
<div class="md-wrap form-standard">
	<?=$readme?>
</div>
