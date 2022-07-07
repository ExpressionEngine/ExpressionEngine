<?php
ee()->load->helper('text');
$menu = ee()->menu->generate_menu();
if ($can_create_channels || count($menu['channels']['edit'])): ?>
	<div class="dashboard__item widget">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('recent_entries'); ?></h2>

			<div>
				<?php if (ee('Permission')->hasAny('can_edit_other_entries', 'can_edit_self_entries')) : ?>
					<a href="<?= ee('CP/URL', 'publish/edit') ?>" class="button button--default button--small"><?= lang('view_all') ?></a>
				<?php endif; ?>
			</div>
		</div>

		<ul class="simple-list">
			<?php
            if (!empty($number_of_channels)):
                $assigned_channels = ee()->functions->fetch_assigned_channels();
                if (!empty($assigned_channels)):
                    $entries = ee('Model')->get('ChannelEntry')
                        ->fields('entry_id', 'title', 'Author.screen_name', 'entry_date')
                        ->filter('channel_id', 'IN', $assigned_channels)
                        ->filter('site_id', ee()->config->item('site_id'))
                        ->order('entry_date', 'DESC')
                        ->limit(7)
                        ->all();

                    foreach ($entries as $entry): ?>
					<li>
						<a class="normal-link" href="<?=ee('CP/URL')->make('publish/edit/entry/' . $entry->entry_id);?>">
              <?= $entry->title; ?>
              <span class="meta-info float-right ml-s"><?= ee()->localize->format_date(ee()->session->userdata('date_format', ee()->config->item('date_format')), $entry->entry_date)?></span>
						</a>
					</li>
					<?php endforeach;
                endif;
            endif; ?>
		</ul>
	</div>
<?php endif; ?>

<?php if ($can_access_members):?>
	<div class="dashboard__item widget">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('members'); ?></h2>

			<div>
				<div class="button-group">
					<?php
                    $pending_count = ee('Model')->get('Member')->filter('role_id', 4)->count();

                    if ($pending_count > 0):
                    ?>
					<a href="<?=ee('CP/URL')->make('members/pending')?>" class="button button--default button--small"><?=$pending_count?> <?=lang('pending')?></a>
					<?php endif; ?>

					<a class="button button--default button--small" href="<?=ee('CP/URL', 'members')?>"><?=lang('view_all')?></a>
				</div>
			</div>
		</div>

		<ul class="simple-list">
			<?php
                $recent_members = ee('Model')->get('Member')
                    ->order('last_visit', 'DESC')
                    ->limit(7)
                    ->all();

                foreach ($recent_members as $member):
                    $last_visit = ($member->last_visit) ? ee()->localize->human_time($member->last_visit) : '--';
                    $avatar_url = ($member->avatar_filename) ? ee()->config->slash_item('avatar_url') . $member->avatar_filename : (URL_THEMES . 'asset/img/default-avatar.png');
                ?>
				<li>
					<a href="<?=ee('CP/URL')->make('members/profile/settings&id=' . $member->member_id);?>" class="d-flex align-items-center normal-link">
						<img src="<?=$avatar_url?>" class="avatar-icon add-mrg-right" alt="">
						<div class="flex-grow"><?= $member->screen_name; ?> <span class="meta-info float-right"><?=$last_visit?></span></div>
					</a>
				</li>
				<?php endforeach; ?>
		</ul>
	</div>
<?php endif; ?>



<?php $spam_comment_width = ($spam_module_installed) ? '' : 'dashboard__item--full'; ?>
<?php if (ee()->config->item('enable_comments') == 'y'): ?>
	<div class="dashboard__item widget <?=$spam_comment_width?>">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('comments'); ?></h2>

			<div class="button-group button-group-xsmall">
				<?php if ($can_edit_comments): ?>
					<a class="button button--default button--small" href="<?=ee('CP/URL')->make('publish/comments', ['filter_by_date' => ee()->localize->now - ee()->session->userdata['last_visit']])?>"><?=$number_of_new_comments?> <?=lang('new_comments')?></a>
					<a class="button button--default button--small" href="<?=ee('CP/URL', 'publish/comments')?>"><?=lang('view_all')?></a>
				<?php endif; ?>
			</div>
		</div>

		<ul class="simple-list">
			<?php
            $comments = ee('Model')->get('Comment')
                ->filter('site_id', ee()->config->item('site_id'))
                ->order('comment_date', 'DESC')
                ->limit(3)
                ->all();

            foreach ($comments as $comment):
            ?>
			<li>
				<div class="d-flex">
					<div>
						<p class="meta-info">
							<?php if ($comment->author_id) : ?>
							<a href="<?=ee('CP/URL')->make('cp/members/profile&id=' . $comment->author_id)?>"><?=$comment->name?></a>
							<?php else: ?>
							<?=$comment->name?>
							<?php endif; ?>
							<?=lang('commented_on')?> <a href="<?=ee('CP/URL')->make('publish/edit/entry/' . $comment->getEntry()->entry_id)?>"><?=$comment->getEntry()->title?></a>
						</p>
						<p><a href="<?=ee('CP/URL')->make('cp/publish/comments/entry/' . $comment->entry_id)?>" class="normal-link"><?=ellipsize($comment->comment, 150)?></a></p>
					</div>
				</div>
			</li>
			<?php endforeach; ?>
		</ul>

		<?php if ($can_moderate_comments): ?>
		<div class="widget__bottom-buttons button-group button-group-small">
			<a href="<?=ee('CP/URL')->make('publish/comments', array('filter_by_status' => 'p'))?>" class="button button--default button--small">
				<?php if ($number_of_pending_comments > 0): ?>
				<i class="icon--caution icon-left"></i>
				<?php endif ?>
				<b><?=$number_of_pending_comments?></b> <?=lang('awaiting_moderation')?>
			</a>

			<?php if ($spam_module_installed): ?>
				<a href="<?=ee('CP/URL')->make('addons/settings/spam', array('content_type' => 'comment'))?>" class="button button--default button--small">
					<b><?=$number_of_spam_comments?></b> <?=lang('flagged_as_spam')?>
				</a>
			<?php endif ?>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>


	<?php if ($spam_module_installed): ?>
	<div class="dashboard__item widget">
		<div class="widget__title-bar">
			<h2 class="widget__title"><?=lang('spam'); ?></h2>

			<div>
				<?php if ($can_moderate_spam): ?>
					<a class="button button--default button--small" href="<?=ee('CP/URL', 'addons/settings/spam')?>"><?=lang('review_all')?></a>
				<?php endif; ?>
			</div>
		</div>

		<?php if ($can_moderate_spam): ?>
		<div class="list-item list-item--action">
			<a href="<?=ee('CP/URL')->make('addons/settings/spam')?>" class="list-item__content">
				<b><?=$number_of_new_spam?></b> <?=lang('new_spam') ?> <?=lang('since_last_login')?>
				<i class="fal fa-chevron-right float-right" style="margin-top: 2px;"></i>
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
			<b class="ml-s"><?=$trapped->total_trapped?></b>&nbsp; <?php if ($trapped->total_trapped > 1): ?><?=lang("$trapped->content_type"."s")?><?php else: ?><?=lang($trapped->content_type)?><?php endif ?> <?=lang('marked_as')?> <?=lang('spam')?>
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
				<a class="button button--default button--small" href="<?=$url_rss?>" rel="external">RSS</a>
			</div>
		</div>

		<?php if (empty($news)): ?>
			<p><?=lang('news_fetch_failure')?><a href="" class="button button--default button--small"><?=lang('retry')?></a></p>
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
	</div>
<?php endif; ?>

	<div class="dashboard__item  widget widget--support">
		<div class="widget__title-bar">
			<h2 class="widget__title">ExpressionEngine Support</h2>
		</div>

		<p>Get <b>direct</b>, <b>fast</b>, <b>unlimited</b> support from the same team that builds your favorite CMS.</p>

		<p><a href="https://expressionengine.com/support" target="_blank" class="button button--default">Learn More</a></p>
	</div>
