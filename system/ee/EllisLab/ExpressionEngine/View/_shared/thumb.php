	<div class="file-card-wrapper">
		<?php foreach ($files as $file): ?>
				<a href data-file-id="<?=$file->file_id?>" rel="modal-view-file" class="m-link file-card <?php if (!$file->exists()): echo 'file-card--missing'; endif; ?>">
					<div class="file-card__preview">
					<?php if (ee('Thumbnail')->get($file)->missing): ?>
						<div class="file-card__preview-icon">
							<i class="fas fa-lg fa-exclamation-triangle"></i>
							<div class="file-card__preview-icon-text">File Not Found</div>
						</div>
					<?php else: ?>
						<img src="<?=ee('Thumbnail')->get($file)->url?>" />
					<?php endif; ?>
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
