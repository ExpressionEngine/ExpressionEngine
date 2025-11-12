<?php if (! empty($filters) && is_array($filters)): ?>
    <?php foreach ($filters as $filter): ?>
        <?php if ($filter['name'] == 'filter_by_keyword') : ?>
            <div class="filter-search-bar__item filter-search-bar__item-search">
                <div class="field-control input-group input-group-sm with-icon-start with-icon-end">
                <?=$filter['html']?>
                <?php if(isset($skipSearchIn) && $skipSearchIn) : ?>
                </div>
            </div>
            <?php endif; ?>
        <?php elseif ($filter['name'] == 'search_in') : ?>
                <?=$filter['html']?>
                <?php if(!isset($skipSearchIn) || ! $skipSearchIn) : ?>
                </div>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="filter-search-bar__item filter-search-bar__item-no-search <?php if (!empty($filter['class'])) {
    echo $filter['class'];
} ?>">
                <?=$filter['html']?>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
    <button class="hidden"><?=lang('submit')?></button>
<?php endif; ?>
