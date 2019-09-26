<?php $this->extend('_templates/default-nav'); ?>

<div class="dashboard">

<?php
$menu = ee()->menu->generate_menu();
if ($can_create_channels || count($menu['channels']['create'])): ?>
	<div class="dashboard__item widget">
		<div class="title-bar">
			<h2 class="title-bar__title widget__title"><?=lang('recent_entries'); ?></h2>

			<div class="title-bar__extra-tools">
				<?php if (ee()->cp->allowed_group_any('can_edit_other_entries', 'can_edit_self_entries')) : ?>
					<a href="<?= ee('CP/URL', 'publish/edit') ?>" class="button button--secondary"><?= lang('view_all') ?></a>
				<?php endif; ?>

				<?php if ($number_of_channels == 0): ?>
					<?php if ($can_create_channels): ?>
						<a class="button button--action" href="<?=ee('CP/URL', 'channels/create')?>"><?=lang('create_new_channel')?></a>
					<?php endif; ?>
				<?php elseif ($can_create_entries && $number_of_channels == 1): ?>
					<a class="button button--action" href="<?=ee('CP/URL', 'publish/create/' . $channel_id)?>"><?=lang('create_new')?></a>
				<?php elseif ($can_create_entries): ?>
					<a href="" data-dropdown-pos="bottom-end" class="js-dropdown-toggle button button--action has-sub"><?=lang('create_new')?></a></a>
					<div class="dropdown">
						<?php foreach ($menu['channels']['create'] as $channel_name => $link): ?>
							<a class="dropdown__link" href="<?=$link?>"><?=$channel_name?></a>
						<?php endforeach ?>
					</div>
				<?php endif;?>
			</div>
		</div>

		<ul class="list">
			<?php
			$entries = ee('Model')->get('ChannelEntry')
			->filter('channel_id', 'IN', ee()->functions->fetch_assigned_channels())
			->order('entry_date', 'DESC')
			->limit(10)
			->all();

			foreach($entries as $entry): ?>
			<li><a href="<?=ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);?>"><?= $entry->title; ?></a></li>
			<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>

	<div class="dashboard__item widget">
		<div class="title-bar">
			<h2 class="title-bar__title widget__title"><?=lang('members'); ?></h2>

			<div class="title-bar__extra-tools">
				<?php if ($can_create_members): ?>
					<a class="button button--action" href="<?=ee('CP/URL', 'members/create')?>"><?=lang('register_new')?></a>
				<?php endif; ?>
			</div>
		</div>

		<ul class="list">
			<?php if ($can_access_members):?>
			<li><a href="<?=ee('CP/URL', 'members')?>"><b><?=$number_of_members?></b> <?=lang('members')?></a></li>
			<li><a href="<?=ee('CP/URL')->make('members', array('group' => 2))?>"><b><?=$number_of_banned_members?></b> <?=lang('banned_members')?></a></li>
			<?php else: ?>
			<li><b><?=$number_of_members?></b> <?=lang('members')?></li>
			<?php endif; ?>
		</ul>
	</div>




	<?php $spam_comment_width = ($spam_module_installed) ? '' : 'dashboard__item--full'; ?>
	<?php if (ee()->config->item('enable_comments') == 'y'): ?>
	<div class="dashboard__item widget <?=$spam_comment_width?>">
		<div class="title-bar">
			<h2 class="title-bar__title widget__title"><?=lang('comments'); ?></h2>

			<div class="title-bar__extra-tools">
				<?php if ($can_moderate_comments && $can_edit_comments): ?>
					<a class="button button--action" href="<?=ee('CP/URL', 'publish/comments')?>"><?=lang('review_all_new')?></a>
				<?php endif; ?>
			</div>
		</div>

		<p <?php if ( ! $can_moderate_comments): ?> class="last"<?php endif; ?>>
			<?=lang('there_were')?> <b><?=$number_of_new_comments?></b>
			<?php if ($can_edit_comments): ?>
				<a href="<?=ee('CP/URL')->make('publish/comments', array('filter_by_date' => ee()->localize->now - ee()->session->userdata['last_visit']))?>"><?=lang('new_comments')?></a>
			<?php else: ?>
				<?=lang('new_comments') ?>
			<?php endif; ?>
			<?=lang('since_last_login')?> (<?=$last_visit?>)
		</p>
		<?php if ($can_moderate_comments): ?>
		<p class="last"><b><?=$number_of_pending_comments?></b> <?=lang('are')?> <a href="<?=ee('CP/URL')->make('publish/comments', array('filter_by_status' => 'p'))?>"><?=lang('awaiting_moderation')?></a><?php if ($spam_module_installed): ?>, <?=lang('and')?> <b><?=$number_of_spam_comments?></b> <?=lang('have_been')?> <a href="<?=ee('CP/URL')->make('addons/settings/spam', array('content_type' => 'comment'))?>"><?=lang('flagged_as_spam')?></a><?php endif ?>.</p>
		<?php endif; ?>
	</div>
	<?php endif; ?>


	<?php if ($spam_module_installed): ?>
	<div class="dashboard__item widget">
		<div class="title-bar">
			<h2 class="title-bar__title widget__title"><?=lang('spam'); ?></h2>

			<div class="title-bar__extra-tools">
				<?php if ($can_moderate_spam): ?>
					<a class="button button--action" href="<?=ee('CP/URL', 'addons/settings/spam')?>"><?=lang('review_all')?></a>
				<?php endif; ?>
			</div>
		</div>

		<p>
			<?=lang('there_are')?> <b><?=$number_of_new_spam?></b>
			<?php if ($can_moderate_spam): ?>
				<a href="<?=ee('CP/URL')->make('addons/settings/spam')?>"><?=lang('new_spam')?></a>
			<?php else: ?>
				<?=lang('new_spam') ?>
			<?php endif; ?>
			<?=lang('since_last_login')?> (<?=$last_visit?>)
		</p>

		<ul class="list">
		<?php foreach ($trapped_spam as $trapped): ?>
			<li><a href="<?=ee('CP/URL')->make('addons/settings/spam', array('content_type' => $trapped->content_type))?>">
				<b><?=$trapped->total_trapped?></b> <?=lang($trapped->content_type)?> <?=lang('spam')?>
				</a>
			</li>
		<?php endforeach;?>
		</ul>
	</div>
	<?php endif; ?>


<?php if ($can_view_homepage_news): ?>
	<div class="dashboard__item dashboard__item--full widget">
		<div class="title-bar">
			<h2 class="title-bar__title widget__title"><?=lang('eecms_news'); ?></h2>

			<div class="title-bar__extra-tools">
				<a class="button button--action" href="<?=$url_rss?>" rel="external">RSS</a>
			</div>
		</div>

		<?php if (empty($news)): ?>
			<div class="widget-empty">
				<div class="widget-content">
					<p><?=lang('news_fetch_failure')?></p>
					<a href="" class="btn submit"><?=lang('retry')?></a>
				</div>
			</div>
		<?php else: ?>
				<div class="col-group">
					<div class="col w-8">
						<ul class="list">
							<?php for ($i = 0; $i < 5; $i++) { ?>
								<li>
									<a href="<?=$news[$i]['link']?>" rel="external"><b><?=$news[$i]['title']?></b></a>
									<time>&mdash; <?=$news[$i]['date']?></time>
								</li>
								<?php } ?>
							</ul>
						</div>
						<div class="col w-8">
							<ul class="list">
							<?php for ($i = 5; $i < 10; $i++) { ?>
								<li>
									<a href="<?=$news[$i]['link']?>" rel="external"><b><?=$news[$i]['title']?></b></a>
									<time>&mdash; <?=$news[$i]['date']?></time>
								</li>
								<?php } ?>
							</ul>
						</div>
					</div>
		<?php endif; ?>
	</div>
<?php endif; ?>
</div>
