<?php
use ExpressionEngine\Library\CP\Table;

if (isset($table_attrs['id'])) {
    $table_id = $table_attrs['id'];
} else {
    $table_id = uniqid('tbl_');
}
$hasCheckboxColumn = false;
if (count($data)) {
    foreach ($data[0]['columns'] as $column) {
        if ($column['type'] == Table::COL_CHECKBOX) {
            $hasCheckboxColumn = true;
            continue;
        }
    }
}
?>
<?php if (isset($no_results)): ?>
    <div class="tbl-row no-results<?=!empty($data) ? ' hidden' : ''?><?=(isset($no_results['class']) ? ' ' . $no_results['class'] : '')?>">
        <div class="none">
            <?php if (isset($no_results['html'])) : ?>

                <?=$no_results['html']?>

            <?php else: ?>
                <p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif ?>
<div class="panel-body">
    <?php if ($hasCheckboxColumn): ?>
    <div class="file-grid__checkAll">
      <label for="<?=$table_id?>-select-all" class="checkbox-label">
          <input id="<?=$table_id?>-select-all" type="checkbox" title="<?=lang('select_all_files')?>" />
          <div class="checkbox-label__text"><?=lang('select_all_files')?></div>
      </label>
    </div><!-- /file-grid__select-all -->
    <?php endif; ?>

    <?php if (!empty($data)) : ?>
    <!-- Add class "file-grid__wrapper-large" for larger thumbnails: -->
    <div class="file-grid__wrapper">
        <?php
        foreach ($data as $row_id => $row) :
            $row['attrs']['class'] .= ' file-grid__file filepicker-item';
        ?>
            <div<?php foreach ($row['attrs'] as $key => $value) :?> <?=$key?>="<?=$value?>"<?php endforeach; ?> data-file-id="<?=$row['attrs']['file_id']?>">

            <?php
            $i = 0;
            foreach ($row['columns'] as $key => $column):
                $column_name = $columns[$key]['label'];
                $column_name = ($lang_cols) ? lang($column_name) : $column_name;
                $i++;
                ?>

                <?php if ($i == 1) : ?>
                    <div class="file-thumbnail__wrapper">
                        <?php if (isset($row['attrs']['href'])) : ?>
                        <a href="<?=$row['attrs']['href']?>">
                        <?php endif; ?>
                <?php endif; ?>
                <?php if ($i == 2) : ?>
                        <?php if (isset($row['attrs']['href'])) : ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="file-metadata__wrapper">
                <?php endif; ?>

                <?php if ($column['type'] == Table::COL_THUMB): ?>
                    <div class="file-thumbnail">
                        <?=$column['content']?>
                    </div>
                <?php elseif ($column['encode'] == true && $column['type'] != Table::COL_STATUS): ?>
                    <?php if (isset($column['href'])): ?>
                    <span><a href="<?=$column['href']?>"><?=htmlentities($column['content'], ENT_QUOTES, 'UTF-8')?></a></span>
                    <?php else: ?>
                    <span><?=htmlentities((string) $column['content'], ENT_QUOTES, 'UTF-8')?></span>
                    <?php endif; ?>
                <?php elseif ($column['type'] == Table::COL_TOOLBAR): ?>
                    <!-- toolbar is only for table view -->
                <?php elseif ($column['type'] == Table::COL_CHECKBOX): ?>
                    <label for="<?=$table_id . '-' . $row_id?>">
                        <input
                            id="<?=$table_id . '-' . $row_id?>"
                            class="input--no-mrg<?php if (isset($column['hidden']) && $column['hidden']):?> hidden<?php endif; ?>"
                            name="<?=form_prep($column['name'])?>"
                            value="<?=form_prep($column['value'])?>"
                            <?php if (isset($column['data'])):?>
                                <?php foreach ($column['data'] as $key => $value): ?>
                                    data-<?=$key?>="<?=form_prep($value)?>"
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <?php if (isset($column['disabled']) && $column['disabled'] !== false):?>
                                disabled="disabled"
                            <?php endif; ?>
                            type="checkbox"
                        >
                    </label>
                <?php elseif (isset($column['html'])): ?>
                    <span<?php if (isset($column['error']) && ! empty($column['error'])): ?> class="invalid"<?php endif ?> <?php if (isset($column['attrs'])): foreach ($column['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; endif; ?>>
                        <?=$column['html']?>
                        <?php if (isset($column['error']) && ! empty($column['error'])): ?>
                            <em class="ee-form-error-message"><?=$column['error']?></em>
                        <?php endif ?>
                        </span>
                <?php else: ?>
                    <span><?=$column['content']?></span>
                <?php endif ?>
            <?php endforeach ?>
                </div>
                </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
