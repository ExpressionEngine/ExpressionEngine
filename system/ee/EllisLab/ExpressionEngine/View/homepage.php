<?php $this->extend('_templates/default-nav'); ?>

<script>
document.querySelector('.ee-main').classList.add('ee-main--dashboard')
</script>

<div class="dashboard">

<?php
$menu = ee()->menu->generate_menu();
if ($can_create_channels || count($menu['channels']['create'])): ?>
	<div class="dashboard__item widget">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('recent_entries'); ?></h2>

			<div>
				<?php if (ee()->cp->allowed_group_any('can_edit_other_entries', 'can_edit_self_entries')) : ?>
					<a href="<?= ee('CP/URL', 'publish/edit') ?>" class="button button--secondary-alt"><?= lang('view_all') ?></a>
				<?php endif; ?>
			</div>
		</div>

		<ul class="simple-list">
			<?php
			if(!empty($number_of_channels)):
				$entries = ee('Model')->get('ChannelEntry')
				->fields('entry_id', 'title', 'Author.screen_name', 'entry_date')
				->filter('channel_id', 'IN', ee()->functions->fetch_assigned_channels())
				->order('entry_date', 'DESC')
				->limit(7)
				->all();


				foreach($entries as $entry): ?>
				<li>
					<a class="normal-link" href="<?=ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);?>">
						<span class="meta-info float-right ml-s"><?= ee()->localize->format_date("%j%S %M, %Y", $entry->entry_date)?></span>
						<?= $entry->title; ?>
					</a>
				</li>
				<?php endforeach; ?>
			<?php endif; ?>
		</ul>
	</div>
<?php endif; ?>

	<div class="dashboard__item widget">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('members'); ?></h2>

			<div>
				<?php if ($can_create_members): ?>
					<a class="button button--secondary-alt" href="<?=ee('CP/URL', 'members/create')?>"><?=lang('register_new')?></a>
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
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('comments'); ?></h2>

			<div>
				<?php if ($can_moderate_comments && $can_edit_comments): ?>
					<a class="button button--secondary-alt" href="<?=ee('CP/URL', 'publish/comments')?>"><?=lang('review_all_new')?></a>
				<?php endif; ?>
			</div>
		</div>

		<ul class="list-group">

			<?php if ($can_edit_comments): ?>
			<div class="list-item list-item--action">
				<a href="<?=ee('CP/URL')->make('publish/comments', array('filter_by_date' => ee()->localize->now - ee()->session->userdata['last_visit']))?>" class="list-item__content">
					<b><?=$number_of_new_comments?></b> <?=lang('new_comments') ?> <?=lang('since_last_login')?> (<?=$last_visit?>)
					<i class="fas fa-chevron-right float-right" style="margin-top: 2px;"></i>
				</a>
			</div>
			<?php else: ?>
			<div class="list-item">
				<div class="list-item__content">
					<b><?=$number_of_new_comments?></b> <?=lang('new_comments') ?> <?=lang('since_last_login')?> (<?=$last_visit?>)
				</div>
			</div>
			<?php endif; ?>

			<?php if ($can_moderate_comments): ?>
				<div class="list-item list-item--action">
					<a href="<?=ee('CP/URL')->make('publish/comments', array('filter_by_status' => 'p'))?>" class="list-item__content">
						<b><?=$number_of_pending_comments?></b> <?=lang('are')?> <?=lang('awaiting_moderation')?>
						<i class="fas fa-chevron-right float-right" style="margin-top: 2px;"></i>
					</a>
				</div>

				<?php if ($spam_module_installed): ?>
				<div class="list-item list-item--action">
					<a href="<?=ee('CP/URL')->make('addons/settings/spam', array('content_type' => 'comment'))?>" class="list-item__content">
						<b><?=$number_of_spam_comments?></b> <?=lang('have_been')?> <?=lang('flagged_as_spam')?>
						<i class="fas fa-chevron-right float-right" style="margin-top: 2px;"></i>
					</a>
				</div>
				<?php endif ?>
			<?php endif; ?>
		</ul>
	</div>
	<?php endif; ?>


	<?php if ($spam_module_installed): ?>
	<div class="dashboard__item widget">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('spam'); ?></h2>

			<div>
				<?php if ($can_moderate_spam): ?>
					<a class="button button--secondary-alt" href="<?=ee('CP/URL', 'addons/settings/spam')?>"><?=lang('review_all')?></a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ($can_moderate_spam): ?>
		<div class="list-item list-item--action">
			<a href="<?=ee('CP/URL')->make('addons/settings/spam')?>" class="list-item__content">
				<b><?=$number_of_new_spam?></b> <?=lang('new_spam') ?> <?=lang('since_last_login')?>
				<i class="fas fa-chevron-right float-right" style="margin-top: 2px;"></i>
			</a>
		</div>
		<?php else: ?>
		<div class="list-item">
			<div class="list-item__content">
				<b><?=$number_of_new_spam?></b> <?=lang('new_spam') ?> <?=lang('since_last_login')?>
			</div>
		</div>
		<?php endif; ?>

		<ul class="simple-list">
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
	<div class="dashboard__item  widget">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('eecms_news'); ?></h2>

			<div>
				<a class="button button--secondary-alt" href="<?=$url_rss?>" rel="external">RSS</a>
			</div>
		</div>

		<?php if (empty($news)): ?>
			<p><?=lang('news_fetch_failure')?><a href="" class="button button--secondary-alt"><?=lang('retry')?></a></p>
		<?php else: ?>
			<ul class="simple-list">
				<?php for ($i = 0; $i < 6; $i++) { ?>
				<li>
					<a class="normal-link" href="<?=$news[$i]['link']?>" rel="external">
						<span class="meta-info float-right ml-s"><?=$news[$i]['date']?></span>
						<?=$news[$i]['title']?>
					</a>
				</li>
				<?php } ?>
			</ul>
		<?php endif; ?>
	</div>
<?php endif; ?>

	<div class="dashboard__item  widget widget--support">
		<div class="widget__title-bar">
			<h2 class="widget__title">ExpressionEngine Support</h2>
		</div>

		<p>Get <b>direct</b>, <b>fast</b>, <b>unlimited</b> support from the same team that builds your favorite CMS.</p>

		<p><a href="https://expressionengine.com/support" target="_blank" class="button">Learn More</a></p>
	</div>

</div>
