<?php if ($can_access_members):?>

		<section class="simple-list-wrapper">
			<?php
				$recent_members = ee('Model')->get('Member')
				->order('last_visit', 'DESC')
				->limit(5)
				->all();

				foreach($recent_members as $member):
					$last_visit = ($member->last_visit) ? ee()->localize->human_time($member->last_visit) : '--';
					$avatar_url = ($member->avatar_filename) ? ee()->config->slash_item('avatar_url') . $member->avatar_filename : (URL_THEMES . 'asset/img/default-avatar.png');
				?>
				<div class="simple-item">
					<a href="<?=ee('CP/URL')->make('members/profile/settings&id=' . $member->member_id);?>" class="d-flex align-items-center normal-link">
						<img src="<?=$avatar_url?>" class="avatar-icon add-mrg-right" alt="">
					</a>
					<div class="simple-item-info">
						<h3><?= $member->screen_name; ?></h3>
						<p class="meta-details">
							<span class="email"><b>Email:</b> <?= $member->email; ?></span>
						</p>
						<p class="meta-info"><b>Last Visit:</b> <?=$last_visit?></p>
					</div>
				</div>
				<?php endforeach; ?>
		</section>

<?php endif; ?>