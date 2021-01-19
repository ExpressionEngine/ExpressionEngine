<div data-addon="<?=$addon['package']?>" <?php if (isset($addon['settings_url'])) : ?>data-card-link="<?= $addon['settings_url'] ?>"<?php endif; ?> class="add-on-card <?php if (isset($addon['settings_url']) && $addon['installed']) : ?>add-on-card--clickable<?php endif; ?> <?php if (!$addon['installed']) : ?>add-on-card--uninstalled<?php endif; ?>">
	<div class="add-on-card__icon">
		<div class="add-on-card__image">
			<img src="<?= (!empty($addon['icon_url']) ? $addon['icon_url'] : URL_THEMES . 'asset/img/default-addon-icon.svg') ?>" alt="<?= $addon['name'] ?>">
		</div>
	</div>
	<div class="add-on-card__text">
		<h2 class="add-on-card__title"><?= $addon['name'] ?> <span class="add-on-card__title-version"><?= $addon['version'] ?></span></h2>

		<?php if (!empty($addon['description'])): ?>
		<p class="add-on-card__desc" title="<?= $addon['description'] ?>"><?= $addon['description'] ?></p>
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
	<a class="add-on-card__cog js-dropdown-toggle"><i class="fas fa-cog"></i></a>
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
			<a class="dropdown__link dropdown__link--danger m-link" href="" rel="modal-confirm-remove" data-action-url="<?= $addon['remove_url'] ?>" data-confirm="<?= $addon['name'] ?>"><?= lang('uninstall') ?></a>
		<?php endif; ?>
	</div>

	<?php endif; ?>
</div>

<!-- Very temporary styles -->
<style>
.corner-ribbon{
  width: 200px;
  background: #e43;
  position: absolute;
  top: 15px;
  left: -25px;
  text-align: center;
  line-height: 25px;
  font-size: 12px;
  letter-spacing: 1px;
  color: #f0f0f0;
  font-weight: bold;
  transform: rotate(-45deg);
  -webkit-transform: rotate(-45deg);
}

/* Custom styles */

.corner-ribbon.sticky{
  position: fixed;
}

.corner-ribbon.shadow{
  box-shadow: 0 0 3px rgba(0,0,0,.3);
}

/* Different positions */

.corner-ribbon.top-left{
  top: 20px;
  left: -65px;
  transform: rotate(-45deg);
  -webkit-transform: rotate(-45deg);
}

.corner-ribbon.top-right{
  top: 25px;
  right: -50px;
  left: auto;
  transform: rotate(45deg);
  -webkit-transform: rotate(45deg);
}

.corner-ribbon.bottom-left{
  top: auto;
  bottom: 25px;
  left: -50px;
  transform: rotate(45deg);
  -webkit-transform: rotate(45deg);
}

.corner-ribbon.bottom-right{
  top: auto;
  right: -50px;
  bottom: 25px;
  left: auto;
  transform: rotate(-45deg);
  -webkit-transform: rotate(-45deg);
}

/* Colors */

.corner-ribbon.white{background: #f0f0f0; color: #555;}
.corner-ribbon.black{background: #333;}
.corner-ribbon.grey{background: #999;}
.corner-ribbon.blue{background: #39d;}
.corner-ribbon.green{background: #2c7;}
.corner-ribbon.turquoise{background: #1b9;}
.corner-ribbon.purple{background: #95b;}
.corner-ribbon.red{background: #e43;}
.corner-ribbon.orange{background: #e82;}
.corner-ribbon.yellow{background: #ec0;}
</style>
