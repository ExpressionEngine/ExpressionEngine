<div class="js-list-group-wrap">
    <div class="list-group-controls">
        <?php if (isset($filters)) {
    echo $filters;
} ?>
        <?php if ((!isset($disable_action) || empty($disable_action)) && !empty($data)): ?>
            <label class="ctrl-all"><span><?=lang('select_all')?></span> <input type="checkbox"></label>
        <?php endif ?>
    </div>

    <ul class="list-group">
        <?php foreach ($data as $row): ?>
            <li class="list-item list-item--action<?php if (isset($row['selected']) && $row['selected']):?> list-item--selected<?php endif ?>" style="position: relative;">
                <?php if (isset($row['reorderable']) && $row['reorderable']) : ?>
                <div class="list-item__handle"><i class="fal fa-bars"></i></div>
                <input type="hidden" name="order[]" value="<?=$row['id']?>" />
                <?php endif; ?>
                <div class="list-item__secondary"<?php if (isset($row['reorderable']) && $row['reorderable']) : ?> style="left: 51px;"<?php endif; ?>>
                    #<?=$row['id']?> <?php if (! empty($row['extra'])):?> <span class="faded">/</span> <span class="click-select-text"><?=ee('Format')->make('Text', $row['extra'])->convertToEntities()?><?php endif ?></span>
                </div>
                <a href="<?=$row['href']?>" class="list-item__content">
                    <div class="list-item__title">
                        <?=(isset($row['htmlLabel']) && $row['htmlLabel']) ? $row['label'] : ee('Format')->make('Text', $row['label'])->convertToEntities()?>
                        <?php if (isset($row['faded'])): ?>
                            <span class="faded"<?php echo isset($row['faded-href']) ? ' data-href="' . $row['faded-href'] . '"' : ''; ?>><?=$row['faded']?></span>
                        <?php endif ?>
                    </div>
                    <div class="list-item__secondary">&#160;</div>
                </a>

                <?php if (isset($row['toolbar_items'])) : ?>
                <div class="list-item__content-right">
                    <?=$this->embed('_shared/toolbar', ['toolbar_items' => $row['toolbar_items']])?>
                </div>
                <?php endif ?>

                <?php if ((!isset($disable_action) || empty($disable_action)) && isset($row['selection'])): ?>
                    <div class="list-item__checkbox">
                        <input
                            name="<?=form_prep($row['selection']['name'])?>"
                            value="<?=form_prep($row['selection']['value'])?>"
                            <?php if (isset($row['selection']['data'])):?>
                                <?php foreach ($row['selection']['data'] as $key => $value): ?>
                                    data-<?=$key?>="<?=form_prep($value)?>"
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (isset($row['selection']['disabled']) && $row['selection']['disabled'] !== false):?>
                                disabled="disabled"
                            <?php endif; ?>
                            type="checkbox"
                            aria-label="<?=$row['label'] . ' ' . lang('checkbox')?>"
                        >
                    </div>
                <?php endif ?>
            </li>
        <?php endforeach; ?>
        <?php if (empty($data) && isset($no_results)): ?>
            <li>
                <div class="tbl-row no-results">
                    <div class="none">
                        <p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
                    </div>
                </div>
            </li>
        <?php endif ?>
    </ul>
</div>
