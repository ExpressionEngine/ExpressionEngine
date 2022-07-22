<?php if ($can_access_members):?>

		<ul class="simple-list">
			<?php
				$recent_members = ee('Model')->get('Member')
				->order('last_visit', 'DESC')
				->limit(7)
				->all();

				foreach($recent_members as $member):
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

<?php endif; ?>