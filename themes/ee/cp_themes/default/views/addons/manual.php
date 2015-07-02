<?php extend_template('default-nav'); ?>

			<h1><?=$name?> <?=$version?><br><i><?=lang('author')?>: <a href="<?=$author_url?>" rel="external"><?=$author?></a><br><?=$description?></i></h1>
			<div class="md-wrap">
				<?=$readme?>
			</div>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>
