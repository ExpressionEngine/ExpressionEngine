<div class="add-on-card <?php if (!$installed) : ?>add-on-card--uninstalled<?php endif; ?>">
	<div class="add-on-card__icon">
		<div class="add-on-card__image">
			<img src="default-addon-on-icon.png">
		</div>
		<span class="price"><?= $version ?></span>
	</div>
	<div class="add-on-card__text">
		<h2 class="add-on-card__title">
			<?php if (isset($settings_url)) : ?>
				<a href="<?= $settings_url ?>"><?= $name ?></a>
			<?php else: ?>
				<?= $name ?>
			<?php endif; ?>
		</h2>

		<?php if (!empty($description)): ?>
		<p class="add-on-card__desc"><?= $description ?></p>
		<?php endif; ?>

		<div class="button-segment">
			<?php if (!$installed) : ?>
				<a href="" data-post-url="<?= $install_url ?>" class="button button--primary"><?= lang('install') ?></a>
			<?php endif; ?>

			<?php if (isset($update)) : ?>
				<a href="" data-post-url="<?=$update_url?>" class="button button--primary">
					<?php echo sprintf(lang('update_to_version'), $update); ?>
				</a>
			<?php endif; ?>

			<?php if (isset($settings_url)) : ?>
				<a href="<?= $settings_url ?>" class="button button--secondary"><?= lang('settings') ?></a>
			<?php endif; ?>

			<?php if (isset($manual_url)) : ?>
				<a href="<?= $manual_url ?>" class="button button--secondary" <?php if ($manual_external) echo 'rel="external"'; ?> title="<?= lang('manual') ?>"><i class="fas fa-fw fa-book"></i></a>
			<?php endif; ?>

			<?php if (ee()->cp->allowed_group('can_admin_addons') && $installed) : ?>
				<a href="" class="button button--secondary js-dropdown-toggle"><i class="fas fa-fw fa-cog"></i></a>
				<div class="dropdown">
					<a class="dropdown__link" href="" data-post-url="<?= $remove_url ?>" class="button button--primary"><?= lang('uninstall') ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
