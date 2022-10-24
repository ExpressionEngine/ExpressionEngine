<div class="dashboard__item dashboard__item--<?=$width?> widget <?=$class?>">
    <div class="widget__title-bar">
    <?php if ($edit_mode) : ?>
        <i class="widget-icon--reorder"></i>
    <?php endif; ?>
    <h2 class="widget__title"><?=$title?></h2>
        <?=$right_head?>
        <?php if ($edit_mode) : ?>
            <i class="widget-icon--<?=($enabled ? 'on' : 'off')?>">
                <input type="hidden" name="widgets_enabled[<?=$widget_id?>]" value="<?=($enabled ? 'y' : 'n')?>" />
            </i>
        <?php endif; ?>
    </div>
    <?=$widget?>
</div>