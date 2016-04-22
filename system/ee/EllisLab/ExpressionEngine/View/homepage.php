<?php $this->extend('_templates/wrapper'); ?>

<div class="home-layout">
	<div class="col-group snap mb">
		<div class="col w-16 last">
			<div class="box full">
				<div class="tbl-ctrls">
					<h1>
						<?=$cp_page_title?>
					</h1>
				</div>
			</div>
		</div>
	</div>
	<?php if (ee()->config->item('enable_comments') == 'y'): ?>
	<div class="col-group snap mb">
		<div class="col w-16 last">
			<div class="box">
				<h1><?=lang('comments')?>
					<?php if ($can_moderate_comments && $can_edit_comments): ?>
						<a class="btn action" href="<?=ee('CP/URL', 'publish/comments')?>"><?=lang('review_all_new')?></a>
					<?php endif; ?>
				</h1>
				<div class="info">
					<p<?php if ( ! $can_moderate_comments): ?> class="last"<?php endif; ?>>
						<?=lang('there_were')?> <b><?=$number_of_new_comments?></b>
						<?php if ($can_edit_comments): ?>
							<a href="<?=ee('CP/URL', 'publish/comments')?>"><?=lang('new_comments')?></a>
						<?php else: ?>
							<?=lang('new_comments') ?>
						<?php endif; ?>
						<?=lang('since_last_login')?> (<?=$last_visit?>)
					</p>
					<?php if ($can_moderate_comments): ?>
					<p class="last"><b><?=$number_of_pending_comments?></b> <?=lang('are')?> <a href="<?=ee('CP/URL')->make('publish/comments', array('filter_by_status' => 'p'))?>"><?=lang('awaiting_moderation')?></a><?php if ($spam_module_installed): ?>, <?=lang('and')?> <b><?=$number_of_spam_comments?></b> <?=lang('have_been')?> <a href="<?=ee('CP/URL')->make('publish/comments', array('filter_by_status' => 's'))?>"><?=lang('flagged_as_spam')?></a><?php endif ?>.</p>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="col-group snap mb">
		<div class="col w-8">
			<div class="box">
				<h1><?=lang('channels')?>
					<?php if ($can_create_channels): ?>
						<a class="btn action" href="<?=ee('CP/URL', 'channels/create')?>"><?=lang('create_new')?></a>
					<?php endif; ?>
				</h1>
				<div class="info">
					<p><?=lang('channels_desc')?></p>
					<h2><?=ee()->config->item('site_name')?> <?=lang('has')?>:</h2>
					<ul class="arrow-list">
						<?php if ($can_access_channels): ?>
						<li><a href="<?=ee('CP/URL', 'channels')?>"><b><?=$number_of_channels?></b> <?=lang('channels')?></a></li>
						<?php else: ?>
						<li><b><?=$number_of_channels?></b> <?=lang('channels')?></li>
						<?php endif; ?>
						<?php if ($can_access_fields): ?>
						<li><a href="<?=ee('CP/URL', 'channels/fields/groups')?>"><b><?=$number_of_channel_field_groups?></b> <?=lang('field_groups')?></a></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
		<div class="col w-8 last">
			<div class="box">
				<h1><?=lang('members')?>
					<?php if ($can_create_members): ?>
						<a class="btn action" href="<?=ee('CP/URL', 'members/create')?>"><?=lang('register_new')?></a>
					<?php endif; ?>
				</h1>
				<div class="info">
					<p>
						<?=lang('members_desc')?>
						<?php if ($can_access_member_settings): ?>
							<?=sprintf(lang('new_members_permission_desc'), ee('CP/URL', 'settings/members'))?>
						<?php endif; ?>
					</p>
					<h2><?=ee()->config->item('site_name')?> <?=lang('has')?>:</h2>
					<ul class="arrow-list">
						<?php if ($can_access_members):?>
						<li><a href="<?=ee('CP/URL', 'members')?>"><b><?=$number_of_members?></b> <?=lang('members')?></a></li>
						<li><a href="<?=ee('CP/URL')->make('members', array('group' => 2))?>"><b><?=$number_of_banned_members?></b> <?=lang('banned_members')?></a></li>
						<?php else: ?>
						<li><b><?=$number_of_members?></b> <?=lang('members')?></li>
						<?php endif; ?>
					</ul>
				</div>
			</div>
		</div>
	</div>

<?php
$menu = ee()->menu->generate_menu();
if ($can_create_channels || count($menu['channels']['create'])): ?>
	<div class="col-group snap mb">
		<div class="col w-16">
			<div class="box">
				<h1 class="btn-right"><?=lang('content')?>
					<?php if ($number_of_channels == 0): ?>
						<?php if ($can_create_channels): ?>
							<a class="btn action" href="<?=ee('CP/URL', 'channels/create')?>"><?=lang('create_new_channel')?></a>
						<?php endif; ?>
					<?php elseif ($number_of_channels == 1): ?>
						<a class="btn action" href="<?=ee('CP/URL', 'publish/create/' . $channel_id)?>"><?=lang('create_new')?></a>
					<?php else: ?>
					<div class="filters">
						<ul>
							<li>
								<a class="has-sub" href=""><?=lang('create_new')?></a>
								<div class="sub-menu">
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
					<?php endif;?>
				</h1>
				<div class="info">
					<p><?=lang('content_desc')?></p>
					<h2><?=ee()->config->item('site_name')?> <?=lang('has')?>:</h2>
					<ul class="arrow-list">
						<li><a href="<?=ee('CP/URL', 'publish/edit')?>"><?=sprintf(lang('entries_with_comments'), $number_of_entries, $number_of_comments)?></a></li>
						<li><a href="<?=ee('CP/URL')->make('publish/edit', array('filter_by_status' => 'closed'))?>"><?=sprintf(lang('closed_entries_with_comments'), $number_of_closed_entries, $number_of_comments_on_closed_entries)?></a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
<?php endif; ?>

<?php if ($can_view_homepage_news): ?>
	<div class="col-group snap">
		<div class="col w-16 last">
			<div class="box widget">
				<h1><?=lang('eecms_news')?> <a class="btn action" href="<?=$url_rss?>" rel="external">RSS</a></h1>
				<?php if (empty($news)): ?>
					<div class="widget-empty">
 						<div class="widget-content">
 							<p><?=lang('news_fetch_failure')?></p>
 							<a href="" class="btn submit"><?=lang('retry')?></a>
 						</div>
 					</div>
				<?php else: ?>
					<div class="info">
						<div class="col-group">
							<div class="col w-8">
								<ul class="arrow-list">
									<?php for ($i = 0; $i < 5; $i++) { ?>
										<li>
											<a href="<?=$news[$i]['link']?>" rel="external"><b><?=$news[$i]['title']?></b></a>
											<time>&mdash; <?=$news[$i]['date']?></time>
										</li>
										<?php } ?>
									</ul>
								</div>
								<div class="col w-8">
									<ul class="arrow-list">
										<?php for ($i = 5; $i < 10; $i++) { ?>
											<li>
												<a href="<?=$news[$i]['link']?>" rel="external"><b><?=$news[$i]['title']?></b></a>
												<time>&mdash; <?=$news[$i]['date']?></time>
											</li>
											<?php } ?>
										</ul>
									</div>
								</div>
							</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
<?php endif; ?>
</div>
