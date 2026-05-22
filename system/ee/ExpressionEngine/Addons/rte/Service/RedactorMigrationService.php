<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Rte\Service;

class RedactorMigrationService
{
    public const AUDIT_TABLE = 'rte_migration_audit';

    private const LEGACY_TYPES = ['redactorClassic', 'redactorX'];
    private const UNSUPPORTED_BUTTONS = ['indent', 'outdent'];
    private const UNSUPPORTED_PLUGINS = [
        'clips',
        'variable',
        'textexpander',
        'fontfamily',
        'fontsize',
        'handle',
        'beyondgrammar',
    ];

    private const PLUGIN_MAP = [
        'properties' => ['blockid', 'blockclass'],
        'selector' => ['blockid', 'blockclass'],
        'inlineformat' => ['underline'],
        'removeformat' => [],
    ];

    public function ensureAuditTable(): void
    {
        ee()->load->dbforge();
        if (ee()->db->table_exists(self::AUDIT_TABLE)) {
            return;
        }

        ee()->dbforge->add_field([
            'audit_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'migrated_at' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'legacy_type' => [
                'type' => 'varchar',
                'constraint' => 32,
                'null' => true,
            ],
            'toolset_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'toolset_name' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => true,
            ],
            'content_table' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => true,
            ],
            'content_column' => [
                'type' => 'varchar',
                'constraint' => 128,
                'null' => true,
            ],
            'content_row_id' => [
                'type' => 'int',
                'constraint' => 10,
                'unsigned' => true,
                'null' => true,
            ],
            'content_location' => [
                'type' => 'varchar',
                'constraint' => 255,
                'null' => true,
            ],
            'detected_feature' => [
                'type' => 'varchar',
                'constraint' => 128,
            ],
            'action_taken' => [
                'type' => 'text',
            ],
            'manual_review' => [
                'type' => 'char',
                'constraint' => 1,
                'default' => 'n',
            ],
        ]);
        ee()->dbforge->add_key('audit_id', true);
        ee()->dbforge->add_key('toolset_id');
        ee()->dbforge->add_key('legacy_type');
        ee()->dbforge->create_table(self::AUDIT_TABLE);
    }

    public function migrate(): array
    {
        $this->ensureAuditTable();

        $summary = [
            'content_rows_migrated' => 0,
            'audit_rows' => 0,
        ];

        $summary['toolsets_consolidated'] = $this->consolidateDefaultRedactorToolsets();
        $summary['content_rows_migrated'] = $this->migrateContent();
        $summary['field_toolsets_assigned'] = $this->assignFieldToolsets();

        return $summary;
    }

    public function consolidateDefaultRedactorToolsets(): int
    {
        $defaults = RedactorService::defaultToolbars();
        $canonicalBasic = $this->findOrCreateCanonicalToolset('Redactor Basic', $defaults['Redactor Basic']);
        $canonicalFull = $this->findOrCreateCanonicalToolset('Redactor Full', $defaults['Redactor Full']);

        $toolsets = ee('Model')->get('rte:Toolset')
            ->filter('toolset_type', 'IN', array_merge(['redactor'], self::LEGACY_TYPES))
            ->all();

        $remap = [];
        foreach ($toolsets as $toolset) {
            $toolsetId = (int) $toolset->toolset_id;

            if ($toolsetId === (int) $canonicalBasic->toolset_id || $toolsetId === (int) $canonicalFull->toolset_id) {
                continue;
            }

            $target = $this->detectRedactorToolsetVariant($toolset) === 'basic' ? $canonicalBasic : $canonicalFull;
            $remap[$toolsetId] = (int) $target->toolset_id;
        }

        if (empty($remap)) {
            return 0;
        }

        $this->remapToolsetReferences($remap);

        $removed = 0;
        foreach ($remap as $fromId => $toId) {
            $toolset = ee('Model')->get('rte:Toolset', $fromId)->first();
            if (!$toolset) {
                continue;
            }

            $this->addAuditRow([
                'legacy_type' => $toolset->toolset_type,
                'toolset_id' => $fromId,
                'toolset_name' => $toolset->toolset_name,
                'detected_feature' => 'redactor_toolset',
                'action_taken' => 'Mapped field settings to toolset_id ' . $toId . ' and removed the old Redactor toolset.',
                'manual_review' => 'n',
            ]);

            $toolset->delete();
            $removed++;
        }

        return $removed;
    }

    public function migrateToolsets(): int
    {
        $updated = 0;

        $toolsets = ee('Model')->get('rte:Toolset')
            ->filter('toolset_type', 'IN', self::LEGACY_TYPES)
            ->all();

        foreach ($toolsets as $toolset) {
            $legacyType = $toolset->toolset_type;
            $migration = $this->migrateLegacyToolbar($legacyType, (array) $toolset->settings, $toolset->toolset_name);

            $toolset->toolset_type = 'redactor';
            $toolset->toolset_name = $migration['variant'] === 'basic' ? 'Redactor Basic' : 'Redactor Full';
            $toolset->settings = $migration['settings'];
            $toolset->save();
            $updated++;

            foreach ($migration['audit'] as $audit) {
                $this->addAuditRow([
                    'legacy_type' => $legacyType,
                    'toolset_id' => (int) $toolset->toolset_id,
                    'toolset_name' => $toolset->toolset_name,
                    'detected_feature' => $audit['feature'],
                    'action_taken' => $audit['action'],
                    'manual_review' => $audit['manual_review'] ? 'y' : 'n',
                ]);
            }
        }

        return $updated;
    }

    public function migrateContent(): int
    {
        $updated = 0;

        $channelFields = ee('Model')->get('ChannelField')
            ->filter('field_type', 'rte')
            ->all();

        foreach ($channelFields as $field) {
            $table = $field->getDataStorageTable();
            foreach ($field->getColumnNames() as $column) {
                $updated += $this->migrateReadMoreInColumn(
                    $table,
                    $column,
                    'channel_field',
                    (int) $field->field_id
                );
            }
        }

        if (ee()->db->table_exists('member_data')) {
            $memberFields = ee('Model')->get('MemberField')
                ->filter('m_field_type', 'rte')
                ->all();

            foreach ($memberFields as $field) {
                $column = 'm_field_id_' . $field->m_field_id;
                if (!ee()->db->field_exists($column, 'member_data')) {
                    continue;
                }

                $updated += $this->migrateReadMoreInColumn(
                    'member_data',
                    $column,
                    'member_field',
                    (int) $field->m_field_id
                );
            }
        }

        $updated += $this->migrateGridContent();

        return $updated;
    }

    /**
     * Normalizes read-more markup stored in Grid RTE columns.
     */
    public function migrateGridContent(): int
    {
        if (!ee()->db->table_exists('grid_columns')) {
            return 0;
        }

        $updated = 0;

        $gridColumns = ee('Model')->get('grid:GridColumn')
            ->filter('col_type', 'rte')
            ->all();

        foreach ($gridColumns as $column) {
            $fieldId = (int) $column->field_id;
            $table = 'channel_grid_field_' . $fieldId;
            $colName = 'col_id_' . (int) $column->col_id;

            $updated += $this->migrateReadMoreInColumn(
                $table,
                $colName,
                'grid_column',
                (int) $column->col_id
            );
        }

        return $updated;
    }

    /**
     * Preserves valid assigned Redactor toolsets and only falls back to canonical defaults when a field has no usable assignment.
     *
     * @return array{basic: int, full: int}
     */
    public function assignFieldToolsets(): array
    {
        $defaults = RedactorService::defaultToolbars();
        $canonicalBasic = $this->findOrCreateCanonicalToolset('Redactor Basic', $defaults['Redactor Basic']);
        $canonicalFull = $this->findOrCreateCanonicalToolset('Redactor Full', $defaults['Redactor Full']);

        $basicId = (int) $canonicalBasic->toolset_id;
        $fullId = (int) $canonicalFull->toolset_id;
        $fluidSubFieldIds = $this->getFluidSubFieldIds();
        $validToolsetIds = [];
        $redactorToolsets = ee('Model')->get('rte:Toolset')
            ->filter('toolset_type', 'redactor')
            ->all();

        foreach ($redactorToolsets as $toolset) {
            $validToolsetIds[(int) $toolset->toolset_id] = true;
        }

        $summary = [
            'basic' => 0,
            'full' => 0,
        ];

        $channelFields = ee('Model')->get('ChannelField')
            ->filter('field_type', 'rte')
            ->all();

        foreach ($channelFields as $field) {
            $fieldId = (int) $field->field_id;
            $settings = is_array($field->field_settings) ? $field->field_settings : [];
            $currentId = isset($settings['toolset_id']) ? (int) $settings['toolset_id'] : 0;
            $targetId = $this->resolveAssignedToolsetId(
                $currentId,
                in_array($fieldId, $fluidSubFieldIds, true),
                $validToolsetIds,
                $basicId,
                $fullId
            );

            if (!isset($settings['toolset_id']) || $currentId !== $targetId) {
                $settings['toolset_id'] = $targetId;
                $field->field_settings = $settings;
                $field->save();
                $summary[$targetId === $fullId ? 'full' : 'basic']++;
            }
        }

        if (ee()->db->table_exists('grid_columns')) {
            $gridColumns = ee('Model')->get('grid:GridColumn')
                ->filter('col_type', 'rte')
                ->all();

            foreach ($gridColumns as $column) {
                $settings = is_array($column->col_settings) ? $column->col_settings : [];
                $currentId = isset($settings['toolset_id']) ? (int) $settings['toolset_id'] : 0;
                $targetId = $this->resolveAssignedToolsetId($currentId, true, $validToolsetIds, $basicId, $fullId);

                if (!isset($settings['toolset_id']) || $currentId !== $targetId) {
                    $settings['toolset_id'] = $targetId;
                    $column->col_settings = $settings;
                    $column->save();
                    $summary[$targetId === $fullId ? 'full' : 'basic']++;
                }
            }
        }

        if (ee()->db->table_exists('member_fields')) {
            $memberFields = ee('Model')->get('MemberField')
                ->filter('m_field_type', 'rte')
                ->all();

            foreach ($memberFields as $field) {
                $settings = is_array($field->m_field_settings) ? $field->m_field_settings : [];
                $currentId = isset($settings['toolset_id']) ? (int) $settings['toolset_id'] : 0;
                $targetId = $this->resolveAssignedToolsetId($currentId, false, $validToolsetIds, $basicId, $fullId);

                if (!isset($settings['toolset_id']) || $currentId !== $targetId) {
                    $settings['toolset_id'] = $targetId;
                    $field->m_field_settings = $settings;
                    $field->save();
                    $summary[$targetId === $fullId ? 'full' : 'basic']++;
                }
            }
        }

        return $summary;
    }

    /**
     * @return int[]
     */
    private function getFluidSubFieldIds(): array
    {
        $ids = [];

        $fluidFields = ee('Model')->get('ChannelField')
            ->filter('field_type', 'fluid_field')
            ->all();

        foreach ($fluidFields as $fluidField) {
            $settings = is_array($fluidField->field_settings) ? $fluidField->field_settings : [];
            if (empty($settings['field_channel_fields']) || !is_array($settings['field_channel_fields'])) {
                continue;
            }

            foreach ($settings['field_channel_fields'] as $fieldId) {
                $ids[] = (int) $fieldId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Ensures legacy Redactor read-more blocks include the label span required by current Redactor.
     *
     * @return array{html: string, changed: bool}
     */
    public function normalizeReadMoreMarkup(string $html, string $defaultLabel = 'Read more'): array
    {
        if ($html === '' || stripos($html, 'readmore') === false) {
            return ['html' => $html, 'changed' => false];
        }

        $changed = false;
        $pattern = '/<div\s+([^>]*\bclass=(["\'])[^"\']*\breadmore\b[^"\']*\2[^>]*)>(.*?)<\/div>/is';

        $html = preg_replace_callback($pattern, function (array $matches) use ($defaultLabel, &$changed) {
            $attrs = $matches[1];
            $inner = trim($matches[3]);

            if ($inner !== '' && preg_match('/\breadmore__label\b/i', $inner)) {
                return $matches[0];
            }

            $changed = true;
            $label = $defaultLabel;

            if ($inner !== '') {
                if (preg_match('/<span\b([^>]*)>(.*?)<\/span>/is', $inner, $spanMatch)) {
                    $spanAttrs = trim($spanMatch[1]);
                    $spanText = trim(strip_tags($spanMatch[2]));
                    if ($spanText !== '') {
                        $label = $spanText;
                    }

                    if (preg_match('/\bclass=(["\'])([^"\']*)\1/i', $spanAttrs, $classMatch)) {
                        $classes = $classMatch[2];
                        if (!preg_match('/\breadmore__label\b/i', $classes)) {
                            $classes = trim($classes . ' readmore__label');
                        }
                        $spanAttrs = preg_replace(
                            '/\bclass=(["\'])([^"\']*)\1/i',
                            'class=$1' . $classes . '$1',
                            $spanAttrs,
                            1
                        );
                    } else {
                        $spanAttrs = ($spanAttrs === '' ? '' : $spanAttrs . ' ') . 'class="readmore__label"';
                    }

                    return '<div ' . $attrs . '><span' . ($spanAttrs === '' ? '' : ' ' . $spanAttrs) . '>'
                        . $label
                        . '</span></div>';
                }

                $stripped = trim(strip_tags($inner));
                if ($stripped !== '') {
                    $label = $stripped;
                }
            }

            return '<div ' . $attrs . '><span class="readmore__label">' . $label . '</span></div>';
        }, $html);

        return ['html' => $html, 'changed' => $changed];
    }

    private function migrateReadMoreInColumn(string $table, string $column, string $location, int $fieldId): int
    {
        if (!ee()->db->table_exists($table) || !ee()->db->field_exists($column, $table)) {
            return 0;
        }

        $idColumn = ($table === 'member_data') ? 'member_id' : 'entry_id';
        if (!ee()->db->field_exists($idColumn, $table)) {
            return 0;
        }

        $rows = ee()->db->select($idColumn . ', ' . $column)
            ->from($table)
            ->like($column, '%readmore%', 'both')
            ->not_like($column, '%readmore__label%', 'both')
            ->get()
            ->result_array();

        $updated = 0;

        foreach ($rows as $row) {
            $content = (string) $row[$column];
            $migration = $this->normalizeReadMoreMarkup($content);

            if (!$migration['changed']) {
                continue;
            }

            ee()->db->where($idColumn, $row[$idColumn])
                ->update($table, [$column => $migration['html']]);

            $this->addAuditRow([
                'content_table' => $table,
                'content_column' => $column,
                'content_row_id' => (int) $row[$idColumn],
                'content_location' => $location . ':' . $fieldId,
                'detected_feature' => 'readmore_markup',
                'action_taken' => 'Added readmore__label span to read-more separator.',
                'manual_review' => 'n',
            ]);

            $updated++;
        }

        return $updated;
    }

    public function migrateLegacyToolbar(string $legacyType, array $settings, string $toolsetName = ''): array
    {
        $defaults = RedactorService::defaultToolbars();
        $toolbar = $this->extractToolbarSettings($settings);
        $variant = $this->detectLegacyToolbarVariant($legacyType, $toolbar);
        $target = $variant === 'basic' ? $defaults['Redactor Basic'] : $defaults['Redactor Full'];
        $audit = [];

        if ($legacyType === 'redactorClassic') {
            $legacyButtons = isset($toolbar['buttons']) && is_array($toolbar['buttons']) ? $toolbar['buttons'] : [];
            $legacyPlugins = isset($toolbar['plugins']) && is_array($toolbar['plugins']) ? $toolbar['plugins'] : [];

            foreach ($legacyButtons as $button) {
                if (in_array($button, self::UNSUPPORTED_BUTTONS, true)) {
                    $audit[] = [
                        'feature' => 'button:' . $button,
                        'action' => 'No direct current toolbar equivalent. Preserved existing HTML.',
                        'manual_review' => true,
                    ];
                    continue;
                }
            }

            if (in_array('undo', $legacyButtons, true) && !in_array('undo', $target['extrabar'], true)) {
                $target['extrabar'][] = 'undo';
            }
            if (in_array('redo', $legacyButtons, true) && !in_array('redo', $target['extrabar'], true)) {
                $target['extrabar'][] = 'redo';
            }
            if (in_array('line', $legacyButtons, true) && !in_array('line', $target['addbar'], true)) {
                $target['addbar'][] = 'line';
            }
            if (in_array('sup', $legacyButtons, true) && !in_array('sup', $target['context'], true)) {
                $target['context'][] = 'sup';
            }
            if (in_array('sub', $legacyButtons, true) && !in_array('sub', $target['context'], true)) {
                $target['context'][] = 'sub';
            }
            if (in_array('underline', $legacyButtons, true) && !in_array('underline', $target['plugins'], true)) {
                $target['plugins'][] = 'underline';
            }
            if (in_array('ol', $legacyButtons, true) && !in_array('numberedlist', $target['format'], true)) {
                $target['format'][] = 'numberedlist';
            }
            if (in_array('ul', $legacyButtons, true) && !in_array('bulletlist', $target['format'], true)) {
                $target['format'][] = 'bulletlist';
            }

            $target['plugins'] = $this->mergePlugins($target['plugins'], $legacyPlugins, $audit);
        } else {
            foreach (['toolbar_hide', 'toolbar_addbar', 'toolbar_context', 'toolbar_control', 'sticky'] as $toggle) {
                if (isset($toolbar[$toggle])) {
                    $target[$toggle] = $toolbar[$toggle];
                }
            }

            $target['hide'] = isset($toolbar['hide']) && is_array($toolbar['hide']) ? $toolbar['hide'] : $target['hide'];

            if (!empty($toolbar['topbar']) && is_array($toolbar['topbar'])) {
                $target['extrabar'] = array_values(array_unique(array_merge($target['extrabar'], $this->mapButtons($toolbar['topbar']))));
            }
            if (!empty($toolbar['addbar']) && is_array($toolbar['addbar'])) {
                $target['addbar'] = array_values(array_unique(array_merge($target['addbar'], $this->mapButtons($toolbar['addbar']))));
            }
            if (!empty($toolbar['context']) && is_array($toolbar['context'])) {
                $target['context'] = array_values(array_unique(array_merge($target['context'], $this->mapButtons($toolbar['context']))));
            }
            if (!empty($toolbar['editor']) && is_array($toolbar['editor'])) {
                $target['editor'] = array_values(array_unique(array_merge($target['editor'], $this->mapButtons($toolbar['editor']))));
            }
            if (!empty($toolbar['format']) && is_array($toolbar['format'])) {
                $target['format'] = array_values(array_unique(array_merge($target['format'], $this->mapButtons($toolbar['format']))));
            }
            if (!empty($toolbar['plugins']) && is_array($toolbar['plugins'])) {
                $target['plugins'] = $this->mergePlugins($target['plugins'], $toolbar['plugins'], $audit);
            }
        }

        if (!empty($toolbar['spellcheck'])) {
            $target['spellcheck'] = $toolbar['spellcheck'];
        }
        if (!empty($settings['height'])) {
            $target['height'] = $settings['height'];
        }
        if (!empty($settings['max_height'])) {
            $target['max_height'] = $settings['max_height'];
        }
        if (!empty($settings['limiter'])) {
            $target['limiter'] = $settings['limiter'];
            if (!in_array('limiter', $target['plugins'], true)) {
                $target['plugins'][] = 'limiter';
            }
        }
        if (!empty($settings['upload_dir'])) {
            $target['upload_dir'] = $settings['upload_dir'];
        }
        if (!empty($settings['field_text_direction'])) {
            $target['field_text_direction'] = $settings['field_text_direction'];
        }
        if (!empty($settings['css_template'])) {
            $target['css_template'] = $settings['css_template'];
        }
        if (!empty($settings['js_template'])) {
            $target['js_template'] = $settings['js_template'];
        }

        return [
            'variant' => $variant,
            'settings' => array_merge(RedactorService::defaultConfigSettings(), ['toolbar' => $target]),
            'audit' => $audit,
        ];
    }

    private function extractToolbarSettings(array $settings): array
    {
        if (isset($settings['toolbar']) && is_array($settings['toolbar'])) {
            return $settings['toolbar'];
        }

        if (isset($settings['toolbar']) && is_object($settings['toolbar'])) {
            return (array) $settings['toolbar'];
        }

        return $settings;
    }

    private function detectRedactorToolsetVariant($toolset): string
    {
        $toolsetName = strtolower((string) $toolset->toolset_name);
        if (strpos($toolsetName, 'basic') !== false) {
            return 'basic';
        }
        if (strpos($toolsetName, 'full') !== false) {
            return 'full';
        }

        $settings = is_array($toolset->settings) ? $toolset->settings : (array) $toolset->settings;
        $toolbar = $this->extractToolbarSettings($settings);

        return $this->detectLegacyToolbarVariant((string) $toolset->toolset_type, $toolbar);
    }

    private function addAuditRow(array $row): void
    {
        $payload = array_merge([
            'migrated_at' => ee()->localize->now,
            'legacy_type' => null,
            'toolset_id' => null,
            'toolset_name' => null,
            'content_table' => null,
            'content_column' => null,
            'content_row_id' => null,
            'content_location' => null,
            'detected_feature' => '',
            'action_taken' => '',
            'manual_review' => 'n',
        ], $row);

        ee()->db->insert(self::AUDIT_TABLE, $payload);
    }

    private function findOrCreateCanonicalToolset(string $toolsetName, array $toolbar)
    {
        $matches = ee('Model')->get('rte:Toolset')
            ->filter('toolset_type', 'redactor')
            ->filter('toolset_name', $toolsetName)
            ->order('toolset_id', 'asc')
            ->all();

        if (count($matches) > 0) {
            $primary = $matches->first();
            if ($primary->toolset_type !== 'redactor') {
                $primary->toolset_type = 'redactor';
                $primary->save();
            }

            if (count($matches) > 1) {
                $duplicates = [];
                foreach ($matches as $match) {
                    if ((int) $match->toolset_id !== (int) $primary->toolset_id) {
                        $duplicates[(int) $match->toolset_id] = (int) $primary->toolset_id;
                    }
                }
                if (!empty($duplicates)) {
                    $this->remapToolsetReferences($duplicates);
                    foreach (array_keys($duplicates) as $duplicateId) {
                        $duplicate = ee('Model')->get('rte:Toolset', $duplicateId)->first();
                        if ($duplicate) {
                            $duplicate->delete();
                        }
                    }
                }
            }

            return $primary;
        }

        $toolset = ee('Model')->make('rte:Toolset');
        $toolset->toolset_name = $toolsetName;
        $toolset->toolset_type = 'redactor';
        $toolset->settings = array_merge(RedactorService::defaultConfigSettings(), ['toolbar' => $toolbar]);
        $toolset->save();

        return $toolset;
    }

    private function detectLegacyToolbarVariant(string $legacyType, array $toolbar): string
    {
        $basic = RedactorService::defaultToolbars()['Redactor Basic'];

        if ($legacyType === 'redactorClassic') {
            $buttons = isset($toolbar['buttons']) && is_array($toolbar['buttons']) ? $toolbar['buttons'] : [];
            $plugins = isset($toolbar['plugins']) && is_array($toolbar['plugins']) ? $toolbar['plugins'] : [];

            $basicButtons = array_unique(array_merge(
                $basic['editor'],
                $basic['format'],
                $basic['context'],
                $basic['addbar'],
                $basic['extrabar']
            ));

            foreach ($this->mapButtons($buttons) as $button) {
                if (!in_array($button, $basicButtons, true)) {
                    return 'full';
                }
            }

            $normalizedPlugins = $this->normalizePluginsForDetection($plugins);
            if (in_array('underline', $buttons, true)) {
                $normalizedPlugins[] = 'underline';
            }

            foreach (array_values(array_unique($normalizedPlugins)) as $plugin) {
                if (!in_array($plugin, $basic['plugins'], true)) {
                    return 'full';
                }
            }

            return 'basic';
        }

        foreach (['toolbar_extrabar', 'toolbar_addbar', 'toolbar_context', 'toolbar_control'] as $toggle) {
            if (($toolbar[$toggle] ?? 'n') === 'y') {
                return 'full';
            }
        }

        $segments = [
            'topbar' => 'extrabar',
            'extrabar' => 'extrabar',
            'addbar' => 'addbar',
            'context' => 'context',
            'editor' => 'editor',
            'format' => 'format',
        ];

        foreach ($segments as $legacyKey => $basicKey) {
            $items = isset($toolbar[$legacyKey]) && is_array($toolbar[$legacyKey]) ? $toolbar[$legacyKey] : [];
            foreach ($this->mapButtons($items) as $button) {
                if (!in_array($button, $basic[$basicKey], true)) {
                    return 'full';
                }
            }
        }

        $plugins = isset($toolbar['plugins']) && is_array($toolbar['plugins']) ? $toolbar['plugins'] : [];
        foreach ($this->normalizePluginsForDetection($plugins) as $plugin) {
            if (!in_array($plugin, $basic['plugins'], true)) {
                return 'full';
            }
        }

        return 'basic';
    }

    private function normalizePluginsForDetection(array $plugins): array
    {
        $normalized = [];

        foreach ($plugins as $plugin) {
            foreach (self::PLUGIN_MAP[$plugin] ?? [$plugin] as $mappedPlugin) {
                if ($mappedPlugin !== '') {
                    $normalized[] = $mappedPlugin;
                }
            }
        }

        return array_values(array_unique($normalized));
    }

    private function remapToolsetReferences(array $toolsetIdMap): void
    {
        if (empty($toolsetIdMap)) {
            return;
        }

        $channelFields = ee('Model')->get('ChannelField')
            ->filter('field_type', 'rte')
            ->all();
        foreach ($channelFields as $field) {
            $settings = is_array($field->field_settings) ? $field->field_settings : [];
            if (isset($settings['toolset_id'])) {
                $oldId = (int) $settings['toolset_id'];
                if (isset($toolsetIdMap[$oldId])) {
                    $settings['toolset_id'] = (int) $toolsetIdMap[$oldId];
                    $field->field_settings = $settings;
                    $field->save();
                }
            }
        }

        if (ee()->db->table_exists('member_fields')) {
            $memberFields = ee('Model')->get('MemberField')
                ->filter('m_field_type', 'rte')
                ->all();
            foreach ($memberFields as $field) {
                $settings = is_array($field->m_field_settings) ? $field->m_field_settings : [];
                if (isset($settings['toolset_id'])) {
                    $oldId = (int) $settings['toolset_id'];
                    if (isset($toolsetIdMap[$oldId])) {
                        $settings['toolset_id'] = (int) $toolsetIdMap[$oldId];
                        $field->m_field_settings = $settings;
                        $field->save();
                    }
                }
            }
        }

        if (ee()->db->table_exists('grid_columns')) {
            $gridColumns = ee('Model')->get('grid:GridColumn')
                ->filter('col_type', 'rte')
                ->all();
            foreach ($gridColumns as $column) {
                $settings = is_array($column->col_settings) ? $column->col_settings : [];
                if (isset($settings['toolset_id'])) {
                    $oldId = (int) $settings['toolset_id'];
                    if (isset($toolsetIdMap[$oldId])) {
                        $settings['toolset_id'] = (int) $toolsetIdMap[$oldId];
                        $column->col_settings = $settings;
                        $column->save();
                    }
                }
            }
        }

        foreach ($toolsetIdMap as $fromId => $toId) {
            ee()->db->where('key', 'rte_default_toolset')
                ->where('value', (string) $fromId)
                ->update('config', ['value' => (string) $toId]);
        }
    }

    private function mergePlugins(array $currentPlugins, array $legacyPlugins, array &$audit): array
    {
        foreach ($legacyPlugins as $plugin) {
            $mappedPlugins = self::PLUGIN_MAP[$plugin] ?? [$plugin];
            foreach ($mappedPlugins as $mappedPlugin) {
                if ($mappedPlugin !== '' && !in_array($mappedPlugin, $currentPlugins, true)) {
                    $currentPlugins[] = $mappedPlugin;
                }
            }

            if (in_array($plugin, self::UNSUPPORTED_PLUGINS, true)) {
                $audit[] = [
                    'feature' => 'plugin:' . $plugin,
                    'action' => 'No direct equivalent enabled in migrated toolbar; content preserved.',
                    'manual_review' => true,
                ];
            }

            if ($plugin === 'video' || $plugin === 'widget') {
                $audit[] = [
                    'feature' => 'plugin:' . $plugin,
                    'action' => 'Preserved existing embed/widget HTML. New editing workflow may differ.',
                    'manual_review' => true,
                ];
            }
        }

        return array_values(array_unique($currentPlugins));
    }

    private function mapButtons(array $legacyButtons): array
    {
        $map = [
            'paragraph' => 'text',
            'ul' => 'bulletlist',
            'ol' => 'numberedlist',
            'shortcut' => 'hotkeys',
            'add' => 'heading',
            'code' => 'moreinline',
        ];

        $mapped = [];
        foreach ($legacyButtons as $button) {
            if (in_array($button, self::UNSUPPORTED_BUTTONS, true)) {
                continue;
            }

            $mapped[] = $map[$button] ?? $button;
        }

        return array_values(array_unique($mapped));
    }

    private function resolveAssignedToolsetId(
        int $assignedToolsetId,
        bool $preferFullFallback,
        array $validToolsetIds,
        int $basicToolsetId,
        int $fullToolsetId
    ): int {
        if ($assignedToolsetId > 0 && isset($validToolsetIds[$assignedToolsetId])) {
            return $assignedToolsetId;
        }

        return $preferFullFallback ? $fullToolsetId : $basicToolsetId;
    }
}
