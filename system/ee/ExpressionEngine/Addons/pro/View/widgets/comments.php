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
				<b><?=$trapped->total_trapped?></b> <?=lang($trapped->content_type)?> <?=lang('spam')?>
				</a>
			</li>
		<?php endforeach;?>
		</ul>
	</div>
	<?php endif; ?>
