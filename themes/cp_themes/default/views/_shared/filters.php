<?php if ( ! empty($filters) && is_array($filters)): ?>
<div class="filters">
	<b><?=lang('filters')?>: </b>
	<ul>
	<?php foreach ($filters as $filter): ?>
		<li>
			<a class="has-sub" href="">
				<?=strtolower(lang($filter['label']))?>
				<?php if ($filter['value']): ?>
				<span class="faded">(<?=$filter['value']?>)</span>
				<?php endif; ?>
			</a>
			<div class="sub-menu">
				<?php if (isset($filter['custom_value'])): ?>
				<fieldset class="filter-search">
					<input
						type="text"
						name="<?=$filter['name']?>"
						value="<?=$filter['custom_value']?>"
						placeholder="<?=$filter['placeholder']?>"
						<?php if (isset($filter['attributes'])): ?>
							<?php foreach ($filter['attributes'] as $attr => $value): ?>
								<?=$attr?>="<?=$value?>"
							<?php endforeach; ?>
						<?php endif; ?>
					>
				</fieldset>
				<?php endif; ?>
				<ul>
				<?php foreach ($filter['options'] as $url => $label): ?>
					<li><a href="<?=$url?>"><?=$label?></a></li>
				<?php endforeach; ?>
				</ul>
			</div>
		</li>
	<?php endforeach; ?>
	</ul>
</div>
<?php endif; ?>