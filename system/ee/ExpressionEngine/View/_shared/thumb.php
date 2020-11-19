	<div class="file-card-wrapper">
		<?php foreach ($files as $file): ?>
				<a href data-file-id="<?=$file->file_id?>" rel="modal-view-file" class="m-link file-card <?php if (!$file->exists()): echo 'file-card--missing'; endif; ?>">
					<div class="file-card__preview">
					<?php if (!$file->exists()): ?>
						<div class="file-card__preview-icon">
							<i class="fas fa-lg fa-exclamation-triangle"></i>
							<div class="file-card__preview-icon-text"><?=lang('file_not_found')?></div>
						</div>
					<?php else: ?>
						<?php if ($file->isImage()): ?>
							<?php if (ee('Thumbnail')->get($file)->missing): ?>
								<div class="file-card__preview-icon">
									<i class="fas fa-lg fa-exclamation-triangle"></i>
									<div class="file-card__preview-icon-text"><?=lang('thumbnail_missing')?></div>
								</div>
							<?php else: ?>
								<div class="file-card__preview-image">
								<img src="<?=ee('Thumbnail')->get($file)->url?>" />
								</div>
							<?php endif; ?>
						<?php else: ?>
							<div class="file-card__preview-icon">
								<?php if ($file->mime_type == 'text/plain'): ?>
									<i class="fas fa-file-alt fa-3x"></i>
								<?php elseif ($file->mime_type == 'application/zip'): ?>
									<i class="fas fa-file-archive fa-3x"></i>
								<?php else: ?>
									<i class="fas fa-file fa-3x"></i>
								<?php endif; ?>
							</div>
						<?php endif; ?>
					<?php endif; ?>
					</div>

					<div class="file-card__info">
						<div class="file-card__info-name"><?=$file->title?></div>
						<div class="file-card__info-subtitle"><?php if ($file->isImage()) { ee()->load->library('image_lib'); $image_info = ee()->image_lib->get_image_properties($file->getAbsolutePath(), TRUE); echo "{$image_info['width']} x {$image_info['height']} - "; }; ?><?=ee('Format')->make('Number', $file->file_size)->bytes();?></div>
					</div>
				</a>
		<?php endforeach; ?>
	</div>
	<?php if (isset($no_results)): ?>
		<div class="tbl-row no-results">
			<div class="none">
				<p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
			</div>
		</div>
	<?php endif ?>
