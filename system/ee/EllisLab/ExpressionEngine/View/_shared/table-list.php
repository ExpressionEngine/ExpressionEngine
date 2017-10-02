<div class="tbl-list-wrap">
	<?php if (isset($filters)) echo $filters; ?>
	<?php if ( ! empty($data)): ?>
		<div class="tbl-list-ctrl<?php if (isset($filters)): ?> has-filters<?php endif ?>">
			<label class="ctrl-all"><span><?=lang('select_all')?></span> <input type="checkbox"></label>
		</div>
	<?php endif ?>
	<ul class="tbl-list">
		<?php foreach ($data as $row): ?>
			<li>
				<div class="tbl-row<?php if (isset($row['selected']) && $row['selected']):?> selected<?php endif ?>">
					<div class="txt">
						<div class="main">
							<a href="<?=$row['href']?>"><b><?=ee('Format')->make('Text', $row['label'])->convertToEntities()?></b></a>
							<?php if (isset($row['faded'])): ?>
								<span class="faded"><?=$row['faded']?></span>
							<?php endif ?>
						</div>
						<div class="secondary">
							<span class="faded">ID#</span> <?=$row['id']?> <?php if ( ! empty($row['extra'])):?> <span class="faded">/</span> <?=ee('Format')->make('Text', $row['extra'])->convertToEntities()?><?php endif ?>
						</div>
					</div>
					<?=$this->embed('_shared/toolbar', ['toolbar_items' => $row['toolbar_items']])?>
					<?php if (isset($row['selection'])): ?>
						<div class="check-ctrl">
							<input
								name="<?=form_prep($row['selection']['name'])?>"
								value="<?=form_prep($row['selection']['value'])?>"
								<?php if (isset($row['selection']['data'])):?>
									<?php foreach ($row['selection']['data'] as $key => $value): ?>
										data-<?=$key?>="<?=form_prep($value)?>"
									<?php endforeach; ?>
								<?php endif; ?>
								<?php if (isset($row['selection']['disabled']) && $row['selection']['disabled'] !== FALSE):?>
									disabled="disabled"
								<?php endif; ?>
								type="checkbox"
							>
						</div>
					<?php endif ?>
				</div>
			</li>
		<?php endforeach; ?>
		<?php if (empty($data) && isset($no_results)): ?>
			<li>
				<div class="tbl-row no-results">
					<div class="none">
						<p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
					</div>
				</div>
			</li>
		<?php endif ?>
	</ul>
</div>
