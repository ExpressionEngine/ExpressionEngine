<div class="tbl-list-wrap">
	<div class="tbl-list selection-area">
		<?php foreach ($files as $file): ?>
				<div align="center">
					<img src="<?=ee('Thumbnail')->get($file)->url?>" /><br />
					<?=$file->title?>
				</div>
		<?php endforeach; ?>
		<?php if (empty($data) && isset($no_results)): ?>
			<div class="tbl-row no-results">
				<div class="none">
					<p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
										<?=$this->embed('_shared/toolbar', ['toolbar_items' => $row['toolbar_items']])?>

				</div>
			</div>
		<?php endif ?>
	</div>
</div>
