<?php if ($can_view_homepage_news): ?>


		<?php if (empty($news)): ?>
			<p><?=lang('news_fetch_failure')?><a href="" class="button button--secondary-alt"><?=lang('retry')?></a></p>
		<?php else: ?>
			<ul class="simple-list">
				<?php for ($i = 0; $i < 6; $i++) { ?>
				<li>
					<a class="normal-link" href="<?=$news[$i]['link']?>" rel="external">
						<?=$news[$i]['title']?>
						<span class="meta-info float-right ml-s"><?=$news[$i]['date']?></span>
					</a>
				</li>
				<?php } ?>
			</ul>
		<?php endif; ?>

<?php endif; ?>
