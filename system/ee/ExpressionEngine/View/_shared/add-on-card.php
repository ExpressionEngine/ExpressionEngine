<div <?php if (isset($addon['settings_url'])) : ?>data-card-link="<?= $addon['settings_url'] ?>"<?php endif; ?> class="add-on-card <?php if (isset($addon['settings_url'])) : ?>add-on-card--clickable<?php endif; ?> <?php if (!$addon['installed']) : ?>add-on-card--uninstalled<?php endif; ?>">
	<div class="add-on-card__icon">
		<div class="add-on-card__image">
			<img src="<?= (!empty($addon['icon_url']) ? $addon['icon_url'] : URL_THEMES . 'asset/img/default-addon-on-icon.png') ?>" alt="<?= $addon['name'] ?>">
		</div>
	</div>
	<div class="add-on-card__text">
		<h2 class="add-on-card__title"><?= $addon['name'] ?> <span class="add-on-card__title-version"><?= $addon['version'] ?></span></h2>

		<?php if (!empty($addon['description'])): ?>
		<p class="add-on-card__desc"><?= $addon['description'] ?></p>
		<?php endif; ?>
	</div>

	<?php if (!$addon['installed']) : ?>
	<div class="add-on-card__button">
		<a href="" data-post-url="<?= $addon['install_url'] ?>" class="button button--primary"><?= lang('install') ?></a>
	</div>
	<?php else: ?>

	<?php if (isset($addon['update']) && !empty($show_updates)) : ?>
		<div class="add-on-card__button">
		<a href="" data-post-url="<?=$addon['update_url']?>" class="button button--primary">
			<?php echo sprintf(lang('update_to_version'), $addon['update']); ?>
		</a>
		</div>
	<?php endif; ?>


	<?php if (empty($show_updates)) : ?>
	<a class="add-on-card__cog js-dropdown-toggle"><i class="fas fa-cog"></i></a>
	<?php endif; ?>

	<div class="dropdown">
		<?php if (isset($addon['settings_url'])) : ?>
			<a href="<?= $addon['settings_url'] ?>" class="dropdown__link"><?= lang('settings') ?></a>
		<?php endif; ?>
		<?php if (isset($addon['manual_url'])) : ?>
			<a href="<?= $addon['manual_url'] ?>" class="dropdown__link" <?php if ($addon['manual_external']) echo 'rel="external"'; ?>><?= lang('manual') ?></a>
		<?php endif; ?>

		<?php if (ee('Permission')->hasAll('can_admin_addons') && $addon['installed']) : ?>
			<a class="dropdown__link dropdown__link--danger" href="" data-post-url="<?= $addon['remove_url'] ?>"><?= lang('uninstall') ?></a>
		<?php endif; ?>
	</div>

	<?php endif; ?>
</div>
