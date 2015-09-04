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
	<div class="col-group snap mb">
		<div class="col w-16 last">
			<div class="box">
				<h1><?=lang('comments')?> <a class="btn action" href="<?=ee('CP/URL', 'publish/comments')?>"><?=lang('review_all_new')?></a></h1>
				<div class="info">
					<p><?=lang('there_were')?> <b><?=$number_of_new_comments?></b> <a href="<?=ee('CP/URL', 'publish/comments')?>"><?=lang('new_comments')?></a> <?=lang('since_last_login')?> (<?=$last_visit?>)</p>
					<p class="last"><b><?=$number_of_pending_comments?></b> <?=lang('are')?> <a href="<?=ee('CP/URL', 'publish/comments', array('filter_by_status' => 'p'))?>"><?=lang('awaiting_moderation')?></a><?php if ($spam_module_installed): ?>, <?=lang('and')?> <b><?=$number_of_spam_comments?></b> <?=lang('have_been')?> <a href="<?=ee('CP/URL', 'publish/comments', array('filter_by_status' => 's'))?>"><?=lang('flagged_as_spam')?></a><?php endif ?>.</p>
				</div>
			</div>
		</div>
	</div>
	<div class="col-group snap mb">
		<div class="col w-8">
			<div class="box">
				<h1><?=lang('channels')?> <a class="btn action" href="<?=ee('CP/URL', 'channels/create')?>"><?=lang('create_new')?></a></h1>
				<div class="info">
					<p><?=lang('channels_desc')?></p>
					<h2><?=ee()->config->item('site_name')?> <?=lang('has')?>:</h2>
					<ul class="arrow-list">
						<li><a href="<?=ee('CP/URL', 'channels')?>"><b><?=$number_of_channels?></b> <?=lang('channels')?></a></li>
						<li><a href="<?=ee('CP/URL', 'channels/fields')?>"><b><?=$number_of_channel_fields?></b> <?=lang('channel_fields')?></a></li>
					</ul>
				</div>
			</div>
		</div>
		<div class="col w-8 last">
			<div class="box">
				<h1><?=lang('members')?> <a class="btn action" href="<?=ee('CP/URL', 'members/create')?>"><?=lang('register_new')?></a></h1>
				<div class="info">
					<p><?=sprintf(lang('members_desc'), ee('CP/URL', 'settings/members'))?></p>
					<h2><?=ee()->config->item('site_name')?> <?=lang('has')?>:</h2>
					<ul class="arrow-list">
						<li><a href="<?=ee('CP/URL', 'members')?>"><b><?=$number_of_members?></b> <?=lang('members')?></a></li>
						<li><a href="<?=ee('CP/URL', 'members', array('group' => 2))?>"><b><?=$number_of_banned_members?></b> <?=lang('banned_members')?></a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="col-group snap">
		<div class="col w-16 last">
			<div class="box">
				<h1 class="btn-right"><?=lang('content')?>
					<?php if ($number_of_channels == 0): ?>
						<a class="btn action" href="<?=ee('CP/URL', 'channel/create')?>"><?=lang('create_new_channel')?></a>
					<?php elseif ($number_of_channels == 1): ?>
						<a class="btn action" href="<?=ee('CP/URL', 'publish/create/' . $channel_id)?>"><?=lang('create_new')?></a>
					<?php else: ?>
					<div class="filters">
						<ul>
							<li>
								<a class="has-sub" href=""><?=lang('create_new')?></a>
								<div class="sub-menu">
									<ul>
										<?php $menu = ee()->menu->generate_menu();
										foreach ($menu['channels']['create'] as $channel_name => $link): ?>
											<li><a href="<?=$link?>"><?=$channel_name?></a></li>
										<?php endforeach ?>
									</ul>
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
						<li><a href="<?=ee('CP/URL', 'publish/edit')?>"><b><?=$number_of_entries?></b> <?=sprintf(lang('entries_with_comments'), $number_of_comments)?></a></li>
						<li><a href="<?=ee('CP/URL', 'publish/edit', array('filter_by_status' => 'closed'))?>"><b><?=$number_of_closed_entries?></b> <?=sprintf(lang('closed_entries_with_comments'), $number_of_comments_on_closed_entries)?></a></li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<?php


