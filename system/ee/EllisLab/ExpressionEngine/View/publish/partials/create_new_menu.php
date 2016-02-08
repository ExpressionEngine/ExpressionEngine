<div class="filters">
	<ul>
		<li>
			<a class="has-sub" href=""><?=$button_text?></a>
			<div class="sub-menu">
				<?php $menu = ee()->menu->generate_menu();
				if (count($menu['channels']['create']) >= 9):?>
				<fieldset class="filter-search">
					<input type="text" value="" data-fuzzy-filter="true" autofocus="autofocus" placeholder="<?=lang('filter_channels')?>">
				</fieldset>
				<?php endif; ?>
				<div class="scroll-wrap">
					<ul>
						<?php foreach ($menu['channels']['create'] as $channel_name => $link): ?>
							<li><a href="<?=$link?>"><?=$channel_name?></a></li>
						<?php endforeach ?>
					</ul>
				</div>
			</div>
		</li>
	</ul>
</div>
