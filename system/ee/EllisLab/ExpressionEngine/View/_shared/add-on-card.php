<div class="add-on-card <?php if (!$installed) : ?>add-on-card--uninstalled<?php endif; ?>">
	<?php if (isset($icon_url)): ?>
    <div class="add-on-card__icon">
		<div class="add-on-card__image">
			<img src="<?= $icon_url ?>" alt="<?= $name ?>">
		</div>
		<!-- <span class="price"><?= $version ?></span> -->
	</div>
    <?php endif; ?>
	<div class="add-on-card__text">
		<h2 class="add-on-card__title">
			<?php if (isset($settings_url)) : ?>
				<a href="<?= $settings_url ?>"><?= $name ?></a>
			<?php else: ?>
				<?= $name ?>
			<?php endif; ?>
			<span class="meta-info">(<?= $version ?>)</span>
		</h2>

		<?php if (isset($description)): ?>
		<p class="add-on-card__desc"><?= $description ?></p>
		<?php endif; ?>

		<div class="button-segment">
			<?php if (!$installed) : ?>
				<a href="" data-post-url="<?= $install_url ?>" class="button button--primary"><?= lang('enable') ?></a>
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
					<a class="dropdown__link" href="" data-post-url="<?= $remove_url ?>" class="button button--primary"><?= lang('disable') ?></a>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
