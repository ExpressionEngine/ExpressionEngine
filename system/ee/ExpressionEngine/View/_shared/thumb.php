<?php 
use ExpressionEngine\Library\CP\Table; 

if (isset($table_attrs['id'])) {
    $table_id = $table_attrs['id'];
} else {
    $table_id = uniqid('tbl_');
}
?>
<?php if (count($data)): ?>
    <div class="file-grid__checkAll">
      <label class="checkbox-label">
          <input name="checkAll" id="checkAll" type="checkbox">
          <div class="checkbox-label__text">Select All Files</div>
      </label>
    </div><!-- /file-grid__select-all -->
    <?php endif; ?>

    <div class="file-grid__wrapper">
        <?php foreach ($data as $row_id => $row): ?>
            
            <?php $missing = false ?>
            <!-- Add class "file-grid__wrapper-large" for larger thumbnails: -->
            <a href="<?=ee('CP/URL')->make('files/file/view/' . $row['attrs']['file_id'])?>" data-file-id="<?=$row['attrs']['file_id']?>" rel="modal-view-file" class="file-grid__file <?php if ($missing): echo 'file-card--missing'; endif; ?>" title="<?=$row['attrs']['title']?>">

            <?php
            $i = 0;
            foreach ($row['columns'] as $key => $column):
                $column_name = $columns[$key]['label'];
                $column_name = ($lang_cols) ? lang($column_name) : $column_name;
                $i++;
                ?>

                <?php if ($i == 1) : ?>
                    <div class="file-thumbnail__wrapper">
                <?php endif; ?>
                <?php if ($i == 2) : ?>
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
                            class="input--no-mrg"
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
                </a>
        <?php endforeach; ?>
    </div>
    <?php if (isset($no_results)): ?>
        <div class="tbl-row no-results">
            <div class="none">
                <?php if($no_results['action_widget']): ?>

                    <?php
                        $component = [
                            'allowedDirectory' => 'all',
                            'contentType' => 'image',
                            'file' => null,
                            'showActionButtons' => false,
                            'createNewDirectory' => true
                        ];
                    ?>

                    <div data-file-field-react="<?=base64_encode(json_encode($component))?>" data-input-value="files_field">
                        <div class="fields-select">
                            <div class="field-inputs">
                                <label class="field-loading">
                                    <?=lang('loading')?><span></span>
                                </label>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <p><?=$no_results['text']?><?php if (isset($no_results['href'])): ?> <a href="<?=$no_results['href']?>"><?=lang('add_new')?></a><?php endif ?></p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif ?>
