	<div class="file-card-wrapper selection-area">
		<?php foreach ($files as $file): ?>
				<div class="file-card">
					<div class="file-card__preview">
						<img src="<?=ee('Thumbnail')->get($file)->url?>" />
					</div>

					<div class="file-card__info">
						<div class="file-card__info-name"><?=$file->title?></div>
						<div class="file-card__info-subtitle">1200x850 - 1MB</div>
					</div>
				</div>
		<?php endforeach; ?>
	</div>
	<?php if (empty($data) && isset($no_results)): ?>
			<div class="tbl-row no-results">
				<div class="none">
					<p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
										<?=$this->embed('_shared/toolbar', ['toolbar_items' => $row['toolbar_items']])?>

				</div>
			</div>
		<?php endif ?>
