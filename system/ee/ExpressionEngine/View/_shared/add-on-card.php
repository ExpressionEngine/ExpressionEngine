<div data-addon="<?=$addon['package']?>" <?php if (isset($addon['settings_url'])) : ?>data-card-link="<?= $addon['settings_url'] ?>"<?php endif; ?> class="add-on-card <?php if (isset($addon['settings_url']) && $addon['installed']) : ?>add-on-card--clickable<?php endif; ?> <?php if (!$addon['installed']) : ?>add-on-card--uninstalled<?php endif; ?>">
	<div class="add-on-card__icon">
		<div class="add-on-card__image">
			<img src="<?= (!empty($addon['icon_url']) ? $addon['icon_url'] : URL_THEMES . 'asset/img/default-addon-icon.svg') ?>" alt="<?= $addon['name'] ?>" width="50">
		</div>
	</div>
	<div class="add-on-card__text">
		<h2 class="add-on-card__title"><?= $addon['name'] ?> <span class="add-on-card__title-version"><?= $addon['version'] ?></span></h2>

		<?php if (!empty($addon['description'])): ?>
		<p class="add-on-card__desc" title="<?= $addon['description'] ?>"><?= $addon['description'] ?></p>
		<?php endif; ?>

		<?php if ($addon['installed'] && $addon['developer'] == 'EEHarbor') : ?>
			<div class="add-on-card__table">
				<table>
					<tr>
						<td style="display:none"><?= $addon['name'] ?></td>
						<td style="display:none"><?= $addon['version'] ?></td>
						<td><div class="toolbar-wrap"><ul class="toolbar"></ul></div></td>
					</tr>
				</table>
			</div>
		<?php endif; ?>
	</div>

	<?php if (!$addon['installed']) : ?>
	<div class="add-on-card__button">
		<a href="" data-post-url="<?= $addon['install_url'] ?>" class="button button--primary button--small"><?= lang('install') ?></a>
	</div>
	<?php else: ?>

	<?php if (isset($addon['update']) && !empty($show_updates)) : ?>
		<div class="add-on-card__button">
		<a href="" data-post-url="<?=$addon['update_url']?>" class="button button--primary button--small">
			<?php echo sprintf(lang('update_to_version'), '<br />' . $addon['update']); ?>
		</a>
		</div>
	<?php endif; ?>


	<?php if (empty($show_updates)) : ?>
	<a class="add-on-card__cog js-dropdown-toggle"><i class="fal fa-cog"></i></a>
	<?php endif; ?>

	<div class="dropdown">
		<?php if (isset($addon['settings_url'])) : ?>
			<a href="<?= $addon['settings_url'] ?>" class="dropdown__link"><?= lang('settings') ?></a>
		<?php endif; ?>
		<?php if (isset($addon['manual_url'])) : ?>
			<a href="<?= $addon['manual_url'] ?>" class="dropdown__link" <?php if ($addon['manual_external']) {
    echo 'rel="external"';
} ?>><?= lang('manual') ?></a>
		<?php endif; ?>

		<?php if (ee('Permission')->hasAll('can_admin_addons') && $addon['installed']) : ?>
			<a class="dropdown__link dropdown__link--danger m-link" href="" rel="modal-confirm-remove" data-action-url="<?= $addon['remove_url'] ?>" data-confirm="<?= $addon['name'] ?>" data-confirm-ajax="<?= $addon['confirm_url'] ?>"><?= lang('uninstall') ?></a>
		<?php endif; ?>
	</div>

	<?php endif; ?>
	<?php if ($addon['installed'] && !empty($addon['license_status']) && !in_array($addon['license_status'], ['na', 'license_valid', 'valid'])) : ?>
		<div class="corner-ribbon-wrap">
			<p class="corner-ribbon top-left <?=$addon['license_status']?> shadow"<?php if ($addon['license_status'] == 'update_available') : ?> style="font-size: 62%;"<?php endif ;?>><?=lang('license_' . $addon['license_status'])?></p>
		</div>
	<?php endif; ?>
</div>
