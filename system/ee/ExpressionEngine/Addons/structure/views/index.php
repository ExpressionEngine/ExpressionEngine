<?php
$ul_open = false;
$last_page_depth = 0;
$level_lock_reorder = is_numeric(substr($permissions['reorder'], -1)) ? (int) substr($permissions['reorder'], -1) : $permissions['reorder'];
$level_lock_delete = is_numeric(substr($permissions['delete'], -1)) ? (int) substr($permissions['delete'], -1) : $permissions['delete'];
?>

<div class="padder ee7 structure-gui">
    <div id="structure-ui" data-ajaxreorder="<?php echo ee('CP/URL')->make('addons/settings/structure/ajax_reorder', array('site_id' => ee()->config->item('site_id'))); ?>" data-ajaxcollapse="<?php echo ee('CP/URL')->make('addons/settings/structure/ajax_collapse'); ?>">
        <div id="tree-header" <?php if (count($tabs) >= 8) {
            echo ' class="tree-header-select"';
        } ?>>
<?php
if ($cp_asset_data or count($tabs) > 1) {
    if (count($tabs) >= 8) {
        echo "\t\t\t", '<div id="tree-switcher-select-box" class="here">', "\n";
        echo "\t\t\t\t", '<select id="tree-switcher-select">', "\n";
        echo "\t\t\t\t\t", '<option value="">- Select Language -</option>', "\n";

        foreach ($tabs as $id => $name) {
            echo "\t\t\t\t\t", '<option', (array_search($id, array_keys($tabs)) == 0 ? ' selected="selected"' : ''), ' rel="', $id, '">', $name, '</option>', "\n";
        }

        echo "\t\t\t\t", '</select>', "\n";
        echo "\t\t\t", '</div>', "\n";

        if ($cp_asset_data) {
            echo "\t\t\t", '<ul id="tree-switcher">', "\n";
            echo "\t\t\t\t", '<li><a href="#" rel="assets">', lang('assets'), '</a></li>', "\n";
            echo "\t\t\t", '</ul>', "\n";
        }
    } else {
        echo "\t\t\t", '<ul id="tree-switcher">', "\n";

        foreach ($tabs as $id => $name) {
            echo "\t\t\t\t", '<li', ((!empty($selected_tab) && $selected_tab === $id) || empty($selected_tab) && array_search($id, array_keys($tabs)) == 0 ? ' class="here"' : ''), '><a href="#" rel="', $id, '">', $name, '</a></li>', "\n";
        }

        if ($cp_asset_data) {
            echo "\t\t\t\t", '<li><a href="#" rel="assets">', lang('assets'), '</a></li>', "\n";
        }

        echo "\t\t\t", '</ul>', "\n";
    }
}
?>

            <ul id="tree-controls">
                <?php if (isset($permissions['view_global_add_page']) && $permissions['view_global_add_page'] == true) : ?>
                    <li <?php if (count($page_choices) > 1 && $page_count > 0) :
                        ?>class="tree-add" <?php
                    endif; ?>><a href="<?= $add_page_url ?>" class="pop" title="pop"><?= lang('ui_add_page') ?></a></li>
                <?php endif; ?>
                <li class="tree-expand"><a href="#"><?= lang('ui_expand_all') ?></a></li>
                <li class="tree-collapse"><a href="#"><?= lang('ui_collapse_all') ?></a></li>
            </ul>
        </div> <!-- close #tree-header -->

        <?php
        foreach ($tabs as $id => $name) :
            $i = 1; #reset for each item in the switcher
            echo "\t\t", '<ul id="', $id, '" class="page-ui page-list', ((!empty($selected_tab) && $selected_tab !== $id) || empty($selected_tab) && array_search($id, array_keys($tabs)) > 0 ? ' hide-alt' : ''), '">', "\n";

            $indentDepth = 3;

            foreach ($data[$id] as $eid => $page) :
                $tmpl_id = isset($site_pages['templates'][$eid]) ? $site_pages['templates'][$eid] : ''; // get the template_id if it exists. otherwise return an empty string

                $add_url = ee('CP/URL')->make('publish/create/' . $page['channel_id'], array('parent_id' => $page['parent_id'], 'template_id' => $tmpl_id));

                $edit_url = ee('CP/URL')->make('publish/edit/entry/' . $page['entry_id'], array('channel_id' => $page['channel_id'], 'parent_id' => $page['parent_id']));

                $classes = array('page-item', 'status-' . str_replace(" ", "-", strtolower($page['status'])), 'channel-' . str_replace(" ", "-", strtolower($page['channel_id'])));

                if ($page['entry_id'] == $homepage) {
                    $classes[] = 'home';
                    //$classes[] = 'ui-nestedSortable-no-nesting';
                }

                $li_open = '<li id="page-' . $page['entry_id'] . '" class="' . implode(' ', $classes) . '">';

                // Start a sub nav
                if ($page['depth'] > $last_page_depth) {
                    $markup = str_repeat("\t", $indentDepth) . '<ul class="page-list';

                    if (!empty($member_settings['nav_state']) && in_array($page['parent_id'], $member_settings['nav_state'])) {
                        $markup .= ' state-collapsed';
                    }

                    $indentDepth++;
                    $markup .= '">' . "\n" . str_repeat("\t", $indentDepth) . $li_open . "\n";
                    $indentDepth++;

                    $ul_open = true;
                } elseif ($i == 1) {
                    $markup = str_repeat("\t", $indentDepth) . $li_open . "\n";
                    $indentDepth++;
                } elseif ($page['depth'] < $last_page_depth) {
                    $indentDepth--;
                    $back_to = $last_page_depth - $page['depth'];
                    $markup = str_repeat("\t", $indentDepth) . '</li>' . "\n";

                    for ($d = $back_to; $d > 0; $d--) {
                        $indentDepth--;
                        $markup .= str_repeat("\t", $indentDepth) . '</ul>' . "\n";

                        $indentDepth--;
                        $markup .= str_repeat("\t", $indentDepth) . '</li>' . "\n";
                    }

                    $markup .= str_repeat("\t", $indentDepth) . $li_open . "\n";
                    $indentDepth++;

                    $ul_open = false;
                } else {
                    $indentDepth--;
                    $markup = str_repeat("\t", $indentDepth) . '</li>' . "\n" . str_repeat("\t", $indentDepth) . $li_open . "\n";
                    $indentDepth++;
                }

                echo $markup;

                echo str_repeat("\t", $indentDepth) . '<div class="item-wrapper">', "\n";
                echo str_repeat("\t", $indentDepth) . "\t", '<div class="item-inner">', "\n";

                echo str_repeat("\t", $indentDepth) . "\t\t", '<span class="page-expand-collapse ec-none"><a href="#">+/-</a></span> <!-- new toggle -->', "\n";
                echo str_repeat("\t", $indentDepth) . "\t\t", '<span class="page-handle', ($permissions['reorder'] == 'none' ? ' page-handle-disabled' : ''), (isset($permissions['reorder']) && ($permissions['reorder'] == 'all' || (is_numeric($level_lock_reorder)) && ($page['depth'] + 1) > $level_lock_reorder) ? ' drag-handle' : ''), '"><a href="#">Move</a></span>', "\n";
                echo str_repeat("\t", $indentDepth) . "\t\t", '<span class="page-title">';

                if (isset($prolet) && $prolet) {
                    $edit_url = ee('CP/URL')->make('publish/edit/entry/' . $page['entry_id']);
                    $edit_url = ee()->config->item('base_url') . $edit_url;
                    $site_id = ee()->config->item('site_id');
                    $edit_url .= "&site_id=" . $site_id . "&hide_closer=y" . "&preview=y";
                    if (array_key_exists($page['channel_id'], $assigned_channels)) {
                        echo '<a href="', $edit_url, '"target="_blank">', (!empty($_GET['debug']) ? $page['entry_id'] . ': ' : '') . $page['title'], '</a>';
                    } else {
                        echo '<span class="page-title-disabled">', $page['title'], '</span>', "\n";
                    }
                } else {
                    if (array_key_exists($page['channel_id'], $assigned_channels)) {
                        echo '<a href="', $edit_url, '">', (!empty($_GET['debug']) ? $page['entry_id'] . ': ' : '') . $page['title'], '</a>';
                    } else {
                        echo '<span class="page-title-disabled">', $page['title'], '</span>', "\n";
                    }
                }

                if ($page['hidden'] == 'y') {
                    echo ' <span class="hidden-page">(hidden)</span>';
                }

                echo '</span>', "\n";

                // If Listing Exists
                if ($page['listing_cid'] && array_key_exists($page['listing_cid'], $assigned_channels)) {
                    echo str_repeat("\t", $indentDepth) . "\t\t", '<span class="page-listing"><a href="', ee('CP/URL')->make('publish/create/' . $page['listing_cid']), '">', lang('add'), '</a> or <a href="', ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $page['listing_cid'])), '">', lang('edit'), '</a></span>', "\n";
                }

                echo str_repeat("\t", $indentDepth) . "\t\t", '<div class="page-controls">', "\n";

                $specific_channel_rule = (! empty($vars['channel_rules'])) ? $vars['channel_rules'][$page['channel_id']] : 'y';

                if (isset($permissions['view_view_page']) && $permissions['view_view_page'] == 'y' && isset($specific_channel_rule) && $specific_channel_rule == 'y') {
                    echo str_repeat("\t", $indentDepth) . "\t\t\t", '<span class="control-view"><a href="', ee('CP/URL')->make('addons/settings/structure/link', array('entry_id' => $page['entry_id'])), '">', lang('view_page'), '<i class="view_icon"></i></a></span>', "\n";
                }

                if ($permissions['view_add_page'] && $settings['show_picker'] == 'y') {
                    if (count($page_choices) > 1 && $page_count > 0) {
                        echo str_repeat("\t", $indentDepth) . "\t\t\t", '<span class="control-add"><a href="#" class="pop" data-parent_id="', $eid, '">', lang('ui_add_child_page'), '</a></span>', "\n";
                    } else {
                        echo str_repeat("\t", $indentDepth) . "\t\t\t", '<span class="control-add-page"><a href="', $add_page_url, '&parent_id=', $eid, '">', lang('ui_add_child_page'), '<i class="add-icon"></i></a></span>', "\n";
                    }
                }

                if ($permissions['view_add_page'] && $settings['show_picker'] == 'n') {
                    echo str_repeat("\t", $indentDepth) . "\t\t\t", '<span class="control-add"><a href="#" data-parent_id="', $eid, '">', lang('ui_add_child_page'), '</a></span>', "\n";
                }

                if (isset($permissions['delete']) && ($permissions['delete'] == 'all' || (is_numeric($level_lock_delete)) && ($page['depth'] + 1) > $level_lock_delete)) {
                    echo str_repeat("\t", $indentDepth) . "\t\t\t", '<span class="control-del"><a href="', ee('CP/URL')->make('addons/settings/structure/delete', array('toggle' => $page['entry_id'])), '">', lang('delete'), '<i class="delete-icon"></i></a></span>', "\n";
                }

                echo str_repeat("\t", $indentDepth) . "\t\t\t", '<input type="hidden" class="structurePid" value="', $page['parent_id'], '" />', "\n";
                echo str_repeat("\t", $indentDepth) . "\t\t\t", '<input type="hidden" class="structureEid" value="', $eid, '" />', "\n";
                echo str_repeat("\t", $indentDepth) . "\t\t", '</div> <!-- close .page-controls -->', "\n";
                echo str_repeat("\t", $indentDepth) . "\t", '</div>', "\n";
                echo str_repeat("\t", $indentDepth) . '</div> <!-- close .item-wrapper -->', "\n";

                $last_page_depth = $page['depth'];
                $i++;
            endforeach;

            // Close out the end
            $indentDepth--;
            $html = str_repeat("\t", $indentDepth) . '</li>' . "\n";
            $html .= str_repeat("</ul>\n</li>\n", $last_page_depth);

            // for($d=$back_to; $d>1; $d--) {
            //  $indentDepth--;
            //  $html .= str_repeat("\t", $indentDepth).'</ul>'."\n";

            //  $indentDepth--;
            //  $html .= str_repeat("\t", $indentDepth).'</li>'."\n";
            // }
            $ul_open = false;

            echo $html;
            echo "\t\t", '</ul>', "\n";
        endforeach;

if ($cp_asset_data) {
    echo "\t\t", '<ul id="assets" class="hide-alt">', "\n";

    foreach ($cp_asset_data as $title => $row) :
        if ($row['split_assets'] == 'y') {
            echo "\t\t\t", '<li><span class="listing-title"><a href="', ee('CP/URL')->make('publish/edit/entry/' . $row['entry_id'], array('channel_id' => $row['channel_id'])),'">', $row['title'], '</a></span></li>', "\n";
        } else {
            echo "\t\t\t", '<li><span class="listing-title">', $row['title'], '</span><span class="page-listing"><a href="', ee('CP/URL')->make('publish/create/' . $row['channel_id']), '">', lang('add'), '</a> or <a href="', ee('CP/URL')->make('publish/edit', array('filter_by_channel' => $row['channel_id'])), '">', lang('edit'), '</a></span></li>', "\n";
        }
    endforeach;

    echo "\t\t", '</ul> <!-- close #assets -->', "\n";
}
?>
        <div class="clear"></div>
    </div>
</div>