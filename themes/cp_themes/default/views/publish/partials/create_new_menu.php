<div class="filters">
	<ul>
		<li>
			<a class="has-sub" href=""><?=$button_text?></a>
			<div class="sub-menu">
				<ul>
					<?php foreach (ee()->menu->generate_menu()['channels']['create'] as $channel_name => $link): ?>
						<li><a href="<?=$link?>"><?=$channel_name?></a></li>
					<?php endforeach ?>
				</ul>
			</div>
		</li>
	</ul>
</div>