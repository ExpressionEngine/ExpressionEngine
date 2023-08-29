<?php use ExpressionEngine\Library\CP\Table;

?>

<?php /* Table */ if (!$grid_input): ?>

<?php if (!empty($no_results['html'])) : ?>
    <div class="no-results<?php if (!empty($data)) :?> hidden<?php endif;?><?=(isset($no_results['class']) ? ' ' . $no_results['class'] : '')?>">
        <?=$no_results['html']?>
    </div>
<?php endif; ?>

<?php if (!empty($data) || empty($no_results['html'])) :?>
    <?php if ($wrap): ?>
        <div class="table-responsive table-responsive--collapsible">
    <?php endif ?>

    <?php if (empty($columns) && empty($data)): ?>
        <table cellspacing="0" class="empty no-results">
            <tr>
                <td>
                    <?=lang($no_results['text'])?>
                    <?php if (! empty($no_results['action_text'])): ?>
                        <a <?=$no_results['external'] ? 'rel="external"' : '' ?> href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>>
                    <?php endif ?>
                </td>
            </tr>
        </table>
    <?php else: ?>
        <table cellspacing="0" <?php if ($class): ?>class="<?=$class?>"<?php endif ?> <?php foreach ($table_attrs as $key => $value):?> <?=$key?>='<?=$value?>'<?php endforeach; ?>>
            <?php
            if (isset($table_attrs['id'])) {
                $table_id = $table_attrs['id'];
            } else {
                $table_id = uniqid('tbl_');
            }
            ?>
            <thead>
                <tr class="app-listing__row app-listing__row--head">
                    <?php
                    // Don't do reordering logic if the table is empty
                    $reorder = $reorder && ! empty($data);
                    $colspan = ($reorder_header || $reorder) ? count($columns) + 1 : count($columns);

                    if ($reorder_header): ?>
                        <th class="reorder-col"><span class="ico reorder fal fa-bars"></span></th>
                    <?php elseif ($reorder): ?>
                        <th class="first reorder-col"></th>
                    <?php endif ?>
                    <?php foreach ($columns as $settings):
                        $attrs = (isset($settings['attrs'])) ? $settings['attrs'] : array();
                        $label = $settings['label']; ?>
                        <?php if ($settings['type'] == Table::COL_CHECKBOX): ?>
                            <th class="app-listing__header text--center">
                                <?php if (! empty($data) or $checkbox_header): // Hide checkbox if no data?>
                                    <?php if (isset($settings['content'])): ?>
                                        <?=$settings['content']?>
                                    <?php else: ?>
                                        <label for="<?=$table_id?>-select-all" class="sr-only"><?=lang('select_all_files')?></label>
                                        <input id="<?=$table_id?>-select-all" class="input--no-mrg" type="checkbox" title="<?=lang('select_all_files')?>">
                                    <?php endif ?>
                                <?php endif ?>
                            </th>
                        <?php else: ?>
                            <?php
                            $header_class = '';
                            $header_sorts = ($sortable && $settings['sort'] && $base_url != null);

                            if ($settings['type'] == Table::COL_ID) {
                                $header_class .= ' id-col';
                            }
                            if ($header_sorts) {
                                $header_class .= ' column-sort-header';
                            }
                            if ($sortable && $settings['sort'] && $sort_col == $label) {
                                $header_class .= ' column-sort-header--active';
                            }
                            if (isset($settings['class'])) {
                                $header_class .= ' ' . $settings['class'];
                            }
                            ?>
                            <th<?php if (! empty($header_class)): ?> class="<?=trim($header_class)?>"<?php endif ?><?php foreach ($attrs as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; ?>>

                                <?php if (empty($settings['label']) && $settings['name'] == 'thumbnail') {?>
                                    <span class="sr-only"><?=lang('thumbnail_column')?></span>
                                <?php } ?>

                                <?php if (empty($settings['label']) && $settings['name'] == 'manage') {?>
                                    <span class="sr-only"><?=lang('toolbar_column')?></span>
                                <?php } ?>

                                <?php if ($header_sorts): ?>
                                    <?php
                                    $url = clone $base_url;
                                    $arrow_dir = ($sort_col == $label) ? $sort_dir : 'desc';
                                    $link_dir = ($arrow_dir == 'asc') ? 'desc' : 'asc';
                                    $url->setQueryStringVariable($sort_col_qs_var, $label);
                                    $url->setQueryStringVariable($sort_dir_qs_var, $link_dir);
                                    ?>
                                    <a href="<?=$url?>" class="column-sort column-sort--<?=$arrow_dir?>">
                                <?php endif ?>

                                <?php if (isset($settings['required']) && $settings['required']): ?><span class="required"><?php endif; ?>
                                <?=($lang_cols) ? lang($label) : $label ?>
                                <?php if (isset($settings['required']) && $settings['required']): ?></span><?php endif; ?>
                                <?php if (isset($settings['desc']) && ! empty($settings['desc'])): ?>
                                    <span class="grid-instruct"><?=lang($settings['desc'])?></span>
                                <?php endif ?>

                                <?php if ($header_sorts): ?>
                                    </a>
                                <?php endif ?>
                            </th>
                        <?php endif ?>
                    <?php endforeach ?>
                </tr>
            </thead>
            <tbody>
                <?php
                // Output this if Grid input so we can dynamically show it via JS
                if (empty($data)): ?>
                    <tr class="no-results<?php if (! empty($action_buttons) || ! empty($action_content)): ?> last<?php endif?>">
                        <td class="solo" colspan="<?=$colspan?>">
                            <?=lang($no_results['text'])?>
                            <?php if (! empty($no_results['action_text'])): ?>
                                <a rel="add_row" <?=$no_results['external'] ? 'rel="external"' : '' ?> href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>
                            <?php endif ?>
                        </td>
                    </tr>
                <?php endif ?>
                <?php $i = 1;
                foreach ($data as $heading => $rows): ?>
                    <?php if (! $subheadings) {
                    $rows = array($rows);
                }
                    if ($subheadings && ! empty($heading)): ?>
                        <tr class="sub-heading"><td colspan="<?=$colspan?>"><?=lang($heading)?></td></tr>
                    <?php endif ?>
                    <?php
                    foreach ($rows as $row_id => $row):
                        if (isset($row['attrs']['class'])) {
                            $row['attrs']['class'] .= ' app-listing__row';
                        } else {
                            $row['attrs']['class'] = 'app-listing__row';
                        }

                        // The last row preceding an action row should have a class of 'last'
                        if ((! empty($action_buttons) || ! empty($action_content)) && $i == min($total_rows, $limit)) {
                            if (isset($row['attrs']['class'])) {
                                $row['attrs']['class'] .= ' last';
                            } else {
                                $row['attrs']['class'] = ' last';
                            }
                        }
                        $i++;
                        ?>
                        <tr<?php foreach ($row['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; ?>>
                            <?php if ($reorder): ?>
                                <td class="reorder-col"><span class="ico reorder fal fa-bars"></span></td>
                            <?php endif ?>
                            <?php foreach ($row['columns'] as $key => $column):
                                $column_name = $columns[$key]['label'];
                                $column_name = ($lang_cols) ? lang($column_name) : $column_name;
                                ?>
    							<?php if ($column['encode'] == true && $column['type'] != Table::COL_STATUS): ?>
    								<?php if (isset($column['href'])): ?>
    								<td><span class="collapsed-label"><?=$column_name?></span><a href="<?=$column['href']?>"><?=htmlentities($column['content'], ENT_QUOTES, 'UTF-8')?></a></td>
    								<?php else: ?>
    								<td><span class="collapsed-label"><?=$column_name?></span><?=htmlentities((string) $column['content'], ENT_QUOTES, 'UTF-8')?></td>
    								<?php endif; ?>
    							<?php elseif ($column['type'] == Table::COL_TOOLBAR): ?>
    								<td class="app-listing__cell">
    									<div class="toolbar-wrap">
    										<?=ee()->load->view('_shared/toolbar', $column, true)?>
    									</div>
    								</td>
    							<?php elseif ($column['type'] == Table::COL_CHECKBOX): ?>
    								<td class="app-listing__cell app-listing__cell--input text--center checkbox-column">
    									<label class="sr-only" for="<?=$table_id . '-' . $i . '-' . $row_id?>"><?=lang('select_row')?></label>
    									<input
    										id="<?=$table_id . '-' . $i . '-' . $row_id?>"
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
    								</td>
    							<?php elseif ($column['type'] == Table::COL_STATUS): ?>
    								<td><span class="collapsed-label"><?=$column_name?></span><?=$column['content']?></td>
    							<?php elseif (isset($column['html'])): ?>
    								<td<?php if (isset($column['error']) && ! empty($column['error'])): ?> class="invalid"<?php endif ?> <?php if (isset($column['attrs'])): foreach ($column['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; endif; ?>>
    									<span class="collapsed-label"><?=$column_name?></span>
    									<?=$column['html']?>
    									<?php if (isset($column['error']) && ! empty($column['error'])): ?>
    										<em class="ee-form-error-message"><?=$column['error']?></em>
    									<?php endif ?>
    								</td>
    							<?php else: ?>
    								<td class="<?=($column['type'] == Table::COL_THUMB ? 'thumb-column' : '')?>"><span class="collapsed-label"><?=$column_name?></span><?=$column['content']?></td>
    							<?php endif ?>
    						<?php endforeach ?>
    					</tr>
    				<?php endforeach ?>
    			<?php endforeach ?>
    			<?php if (! empty($action_buttons) || ! empty($action_content)): ?>
    				<tr class="tbl-action">
    					<td colspan="<?=$colspan?>" class="solo">
    						<?php foreach ($action_buttons as $button): ?>
    							<a class="<?=$button['class']?>" href="<?=$button['url']?>"><?=$button['text']?></a></td>
    						<?php endforeach; ?>
    						<?=$action_content?>
    					</td>
    				</tr>
    			<?php endif; ?>
    		</tbody>
    	</table>
    <?php endif ?>

    <?php if ($wrap): ?>
        </div>
    <?php endif ?>
<?php endif; ?>


<?php /* End table */

else: ?>
    <div
        class="grid-field <?php if (isset($vertical_layout)) {
                if ($vertical_layout == 'y') : echo ' vertical-layout';
                elseif ($vertical_layout == 'horizontal') : echo ' horizontal-layout';
                else : echo 'entry-grid';
                endif;
            }; ?>"
        id="<?=$grid_field_name?>">

    <div class="table-responsive">
    <table class="grid-field__table"<?php foreach ($table_attrs as $key => $value):?> <?=$key?>='<?=$value?>'<?php endforeach; ?>>
    <?php if (empty($columns) && empty($data)): ?>
        <p class="no-results">
            <?=lang($no_results['text'])?>
            <?php if (! empty($no_results['action_text'])): ?>
                <a <?=$no_results['external'] ? 'rel="external"' : '' ?> href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>>
            <?php endif ?>
        </p>
    <?php else: ?>
        <thead>
                <?php
                // Don't do reordering logic if the table is empty
                $reorder = $reorder && ! empty($data);
                $colspan = ($reorder_header || $reorder) ? count($columns) + 1 : count($columns);
                if (isset($vertical_layout)): ?>
                    <th class="hidden"></th>
                <?php endif;

                foreach ($columns as $settings):
                    $attrs = (isset($settings['attrs'])) ? $settings['attrs'] : array();
                    $label = $settings['label']; ?>
                    <?php if ($settings['type'] == Table::COL_CHECKBOX): ?>
                        <th class="check-ctrl">
                            <?php if (! empty($data) or $checkbox_header): // Hide checkbox if no data?>
                                <?php if (isset($settings['content'])): ?>
                                    <?=$settings['content']?>
                                <?php else: ?>
                                    <label for="<?=$grid_field_name?>-select-all" class="hidden"><?=lang('select_all')?></label>
                                    <input id="<?=$grid_field_name?>-select-all" type="checkbox" title="<?=lang('select_all')?>">
                                <?php endif ?>
                            <?php endif ?>
                        </th>
                    <?php else: ?>
                        <?php
                        $header_class = '';
                        $header_sorts = ($sortable && $settings['sort'] && $base_url != null);

                        if ($settings['type'] == Table::COL_ID) {
                            $header_class .= ' id-col';
                        }
                        if ($header_sorts) {
                            $header_class .= ' column-sort-header';
                        }
                        if ($sortable && $settings['sort'] && $sort_col == $label) {
                            $header_class .= ' column-sort-header--active';
                        }
                        if (isset($settings['class'])) {
                            $header_class .= ' ' . $settings['class'];
                        }
                        ?>
                        <th class="<?=$header_class?>" <?php foreach ($attrs as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; ?>>
                            <?php if ($header_sorts): ?>
                                <?php
                                $url = clone $base_url;
                                $arrow_dir = ($sort_col == $label) ? $sort_dir : 'desc';
                                $link_dir = ($arrow_dir == 'asc') ? 'desc' : 'asc';
                                $url->setQueryStringVariable($sort_col_qs_var, $label);
                                $url->setQueryStringVariable($sort_dir_qs_var, $link_dir);
                                ?>
                                <a href="<?=$url?>" class="column-sort column-sort--<?=$arrow_dir?>">
                            <?php endif ?>

                            <?php if (isset($settings['required']) && $settings['required']): ?><span class="required"><?php endif; ?>
                            <?=($lang_cols) ? lang($label) : $label ?>
                            <?php if (isset($settings['badge'])) echo $settings['badge']; ?>
                            <?php if (isset($settings['required']) && $settings['required']): ?></span><?php endif; ?>
                            <?php if (isset($settings['desc']) && ! empty($settings['desc'])): ?>
                                <span class="grid-instruct"><?=lang($settings['desc'])?></span>
                            <?php endif ?>

                            <?php if ($header_sorts): ?>
                                </a>
                            <?php endif ?>
                        </th>
                    <?php endif ?>
                <?php endforeach ?>

                <?php if (!empty($data)): ?>
                    <th class="grid-field__column-remove"></th>
                <?php endif ?>
        </thead>
    <?php endif ?>

        <tbody>
            <tr class="no-results<?php if (! empty($action_buttons) || ! empty($action_content)): ?> last<?php endif?> <?php if (!empty($data)): ?>hidden<?php endif?>"><td colspan="<?=(count($columns) + @intval($header_sorts))?>">
            <?php
            // Output this if Grid input so we can dynamically show it via JS
            ?>
                <p>
                    <?=lang($no_results['text'])?>
                    <?php if (! empty($no_results['action_text'])): ?>
                        <a rel="add_row" <?=$no_results['external'] ? 'rel="external"' : '' ?> href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>
                    <?php endif ?>
                </p>
            </td></tr>
            <?php $i = 1;
            foreach ($data as $heading => $rows): ?>
                <?php if (! $subheadings) {
                $rows = array($rows);
            }

                foreach ($rows as $row):
                    $i++;

                    $row_class = "";

                    if (isset($row['attrs']['class'])) {
                        $row_class = $row['attrs']['class'];
                        unset($row['attrs']['class']);
                    }
                ?>
                    <tr class="<?=$row_class?>" <?php foreach ($row['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; ?>>
                        <?php if (REQ == 'CP' && isset($vertical_layout) && ($vertical_layout !== 'horizontal')):?>
                        <td class="grid-field__item-fieldset" style="display: none;">
                            <div class="grid-field__item-tools grid-field__item-tools--item-open">
                                <a href class="grid-field__item-tool js-toggle-grid-item">
                                    <span class="sr-only"><?=lang('collapse')?></span>
                                    <i class="fal fa-caret-square-up fa-fw"></i>
                                </a>

                                <button type="button" data-dropdown-offset="0px, -30px" data-dropdown-pos="bottom-end" class="grid-field__item-tool js-dropdown-toggle"><i class="fal fa-fw fa-cog"></i></button>

                                <div class="dropdown">
                                    <a href class="dropdown__link js-hide-all-grid-field-items"><?=lang('collapse_all')?></a>
                                    <a href class="dropdown__link js-show-all-grid-field-items"><?=lang('expand_all')?></a>
                                    <div class="dropdown__divider"></div>
                                    <a href class="dropdown__link dropdown__link--danger js-grid-field-remove" rel="remove_row"><i class="fal fa-fw fa-trash-alt"></i> <?=lang('delete')?></a>
                                </div>
                            </div>

                            <div class="field-instruct">
                                <label>
                                    <?php if ($reorder): ?>
                                    <button type="button" class="js-grid-reorder-handle">
                                        <i class="icon--reorder reorder"></i>
                                    </button>
                                    <?php endif ?>
                                </label>
                            </div>
                        </td>
                        <?php endif; ?>

                        <?php foreach ($row['columns'] as $key => $column):
                            $column_name = $columns[$key]['label'];
                            $column_name = ($lang_cols) ? lang($column_name) : $column_name;
                            $column_desc = '';

                            if (isset($columns[$key]['desc']) && !empty($columns[$key]['desc'])) {
                                $column_desc = lang($columns[$key]['desc']);
                            }

                            $column_badge = isset($columns[$key]['badge']) ? $columns[$key]['badge'] : '';

                            $column_label = "<div class=\"grid-field__column-label\"  role=\"rowheader\">
                                <div class=\"grid-field__column-label__instraction\">
                                    <label>$column_name</label>" . $column_badge;
                            if (!empty($column_desc)) {
                                $column_label .= "
                                    <em>$column_desc</em>
                                    ";
                            }
                            $column_label .= "  </div>
                            </div>";

                            ?>

                            <?php if ($column['encode'] == true && $column['type'] != Table::COL_STATUS): ?>
                                <?php if (isset($column['href'])): ?>
                                <td><?=$column_label?><a href="<?=$column['href']?>"><?=htmlentities($column['content'], ENT_QUOTES, 'UTF-8')?></a></td>
                                <?php else: ?>
                                <td><?=$column_label?><?=htmlentities($column['content'], ENT_QUOTES, 'UTF-8')?></td>
                                <?php endif; ?>
                            <?php elseif ($column['type'] == Table::COL_TOOLBAR): ?>
                                <td>
                                    <div class="toolbar-wrap">
                                        <?=ee()->load->view('_shared/toolbar', $column, true)?>
                                    </div>
                                </td>
                            <?php elseif ($column['type'] == Table::COL_CHECKBOX): ?>
                                <td>
                                    <input
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
                                </td>
                            <?php elseif ($column['type'] == Table::COL_STATUS): ?>
                                <td><?=$column_label?><?=$column['content']?></td>
                            <?php elseif (isset($column['html'])): ?>
                                <?php
                                    $column_class = '';
                                    if (isset($column['attrs']['class'])) {
                                        $column_class = $column['attrs']['class'];
                                        unset($column['attrs']['class']);
                                    }
                                    if (isset($column['error']) && ! empty($column['error'])) {
                                        $column_class .= ' invalid';
                                    }
                                ?>
                                <td class="<?=$column_class?>" <?php if (isset($column['attrs'])): foreach ($column['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach; endif; ?>>
                                    <?=$column_label?>
                                    <?=$column['html']?>
                                    <?php if (isset($column['error']) && ! empty($column['error'])): ?>
                                        <em class="ee-form-error-message"><?=$column['error']?></em>
                                    <?php endif ?>
                                </td>
                            <?php else: ?>
                                <td><?=$column_label?><?=$column['content']?></td>
                            <?php endif ?>
                        <?php endforeach ?>

                        <td class="grid-field__column--tools">
                            <div class="grid-field__column-tools">
                                <?php if ($reorder): ?>
                                <button type="button" class="button button--small button--default cursor-move js-grid-reorder-handle">
                                    <span class="grid-field__column-tool"><i class="fal fa-fw fa-arrows-alt"></i></span>
                                </button>
                                <?php endif ?>
                                <button type="button" rel="remove_row" class="button button--small button--default">
                                    <span class="grid-field__column-tool danger-link" title="<?=lang('remove_row')?>"><i class="fal fa-fw fa-trash-alt"><span class="hidden"><?=lang('remove_row')?></span></i></span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach ?>
            <?php endforeach ?>
            </tbody>
    </table>
    </div>

    <div class="grid-field__footer">
        <div class="button-group">
            <?php if (! empty($action_buttons) || ! empty($action_content)): ?>
            <div class="tbl-action">
                <?php foreach ($action_buttons as $button): ?>
                    <a class="<?=$button['class']?>" href="<?=$button['url']?>"><?=$button['text']?></a></td>
                <?php endforeach; ?>
                <?=$action_content?>
            </div>
            <?php endif; ?>
            <?php if ($show_add_button) : ?>
            <button type="button" rel="add_row" class="button button--default button--small js-grid-add-row"><?=lang('add_row')?></button>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif ?>
