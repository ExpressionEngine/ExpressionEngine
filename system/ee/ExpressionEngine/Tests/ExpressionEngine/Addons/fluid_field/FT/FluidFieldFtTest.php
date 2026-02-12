<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/../FluidFieldTestBase.php';

use ExpressionEngine\Service\Validation\Result as ValidationResult;
use Mockery as m;

class FluidFieldFtTest extends FluidFieldTestBase
{
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!defined('URL_THEMES')) {
            define('URL_THEMES', '/themes/');
        }

        if (!class_exists(\ExpressionEngine\Addons\FluidField\Model\FluidFieldFilter::class)) {
            require_once PATH_ADDONS . 'fluid_field/Model/FluidFieldFilter.php';
        }
    }

    public function testConstructorLoadsAddonInfoAndValidationResult()
    {
        $this->assertSame('Fluid Field', $this->fieldtype->info['name']);
        $this->assertSame('1.0.0', $this->fieldtype->info['version']);

        $errors = $this->getPrivateProperty($this->fieldtype, 'errors');
        $this->assertInstanceOf(ValidationResult::class, $errors);
    }

    public function testValidateReturnsTrueWhenDataIsEmpty()
    {
        $this->assertTrue($this->fieldtype->validate([]));
    }

    public function testValidateReturnsTrueWhenAjaxRequestHasNoErrors()
    {
        $field = new FluidFieldFacadeStub(1);
        $field->setData('value');
        $channelField = new FluidFieldChannelFieldStub(1, 'title', $field);

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(true));
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [1];

        $this->input->ajax = true;
        $this->input->postData['ee_fv_field'] = 'fluid_content[fields][field_5][field_group_id_0][field_id_1]';

        $data = [
            'fields' => [
                'field_5' => [
                    'field_group_id_0' => [
                        'field_id_1' => 'value'
                    ]
                ]
            ]
        ];

        $this->assertTrue($this->fieldtype->validate($data));
    }

    public function testValidateReturnsFormValidationErrorWhenSubFieldFails()
    {
        $field = new FluidFieldFacadeStub(1);
        $field->setData('value');
        $channelField = new FluidFieldChannelFieldStub(1, 'title', $field);

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $rule = new FluidFieldRuleStub('callback');
        $failed = ['fluid_content[fields][field_5][field_group_id_0][field_id_1]' => [$rule]];

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(false, $failed));
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->input->ajax = false;

        $data = [
            'fields' => [
                'field_5' => [
                    'field_group_id_0' => [
                        'field_id_1' => 'value'
                    ]
                ]
            ]
        ];

        $this->assertSame('form_validation_error', $this->fieldtype->validate($data));
    }

    public function testSaveReturnsEmptyStringWhenNoFluidFieldDataExists()
    {
        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'fluid_field:FluidField') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->save(null);

        $this->assertSame('', $result);
    }

    public function testSaveCompilesSearchDataForExistingAndNewFields()
    {
        $existingField = new FluidFieldFacadeStub(1);
        $existingField->setItem('field_search', true);
        $existingField->saveResult = 'existing-search';

        $existingRecord = new FluidFieldRecordStub(5, 1, $existingField);
        $collection = new FluidFieldTestCollection([$existingRecord]);
        $this->session->set_cache('FluidField', 'FluidField/10/99', $collection);

        $newField = new FluidFieldFacadeStub(2);
        $newField->setItem('field_search', true);
        $newField->saveResult = 'new-search';

        $this->setModelMakeCallback(function ($model) use ($newField) {
            if ($model === 'fluid_field:FluidField') {
                return new FluidFieldRecordStub(0, 2, $newField);
            }

            return new FluidFieldRecordStub();
        });

        $data = [
            'fields' => [
                'field_5' => [
                    'field_group_id_0' => [
                        'field_id_1' => 'existing-value'
                    ]
                ],
                'new_field_1' => [
                    'field_group_id_0' => [
                        'field_id_2' => 'new-value'
                    ]
                ],
                'new_field_0' => [
                    'field_group_id_0' => [
                        'field_id_999' => 'ignored'
                    ]
                ]
            ]
        ];

        $result = $this->fieldtype->save($data);

        $this->assertSame('existing-search new-search', $result);
        $this->assertCount(1, $this->modelService->makeCalls);
    }

    public function testSaveSkipsTemplateRowAndNonFieldValues()
    {
        $existingField = new FluidFieldFacadeStub(1);
        $existingField->setItem('field_search', true);
        $existingRecord = new FluidFieldRecordStub(5, 1, $existingField);
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$existingRecord]));

        $result = $this->fieldtype->save([
            'fields' => [
                'new_field_0' => [
                    'field_group_id_0' => [
                        'field_id_1' => 'template-row',
                    ],
                ],
                'field_5' => [
                    'field_group_id_0' => [
                        'not_a_field_id' => 'skip-me',
                    ],
                ],
            ],
        ]);

        $this->assertSame('', $result);
        $this->assertCount(0, $this->modelService->makeCalls);
        $this->assertNull($existingField->getItem('fluid_field_data_id'));
    }

    public function testPostSaveReturnsEarlyWhenSessionCacheIsMissing()
    {
        $this->assertNull($this->fieldtype->post_save([]));
        $this->assertCount(0, $this->db->updates);
        $this->assertCount(0, $this->db->deletes);
    }

    public function testPostSaveUpdatesAddsAndRemovesFields()
    {
        $existingField = new FluidFieldFacadeStub(1);
        $existingField->data = 'existing-saved';
        $existingField->setItem('field_search', true);
        $existingRecord = new FluidFieldRecordStub(5, 1, $existingField, new FluidFieldChannelFieldStub(1, 'field_one', $existingField));
        $existingRecord->group = 1;
        $existingRecord->field_data_id = 500;

        $orphanField = new FluidFieldFacadeStub(3);
        $orphanRecord = new FluidFieldRecordStub(9, 3, $orphanField, new FluidFieldChannelFieldStub(3, 'field_three', $orphanField, 'text', 'Field Three', 'exp_channel_data_field_3'));
        $orphanRecord->field_data_id = 900;

        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'field_5' => [
                        'field_group_id_0' => [
                            'field_id_1' => 'existing-value'
                        ]
                    ],
                    'new_field_1' => [
                        'field_group_id_0' => [
                            'field_id_2' => 'new-value'
                        ]
                    ]
                ]
            ]
        );

        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([
            $existingRecord,
            $orphanRecord,
        ]));

        $newField = new FluidFieldFacadeStub(2);
        $newField->data = 'new-saved';
        $newRecord = new FluidFieldRecordStub(0, 2, $newField, new FluidFieldChannelFieldStub(2, 'field_two', $newField));

        $this->setModelMakeCallback(function ($model) use ($newRecord) {
            if ($model === 'fluid_field:FluidField') {
                return $newRecord;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection(), new FluidFieldChannelFieldStub(2, 'field_two', new FluidFieldFacadeStub(2)));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $this->fieldtype->post_save([]);

        $this->assertGreaterThan(0, count($this->db->updates));
        $this->assertGreaterThan(0, count($this->db->inserts));
        $this->assertTrue($orphanRecord->deleted);
    }

    public function testReindexUsesFieldReindexWhenAvailable()
    {
        $reindexField = new FluidFieldFacadeStub(1);
        $reindexField->hasReindex = true;
        $reindexField->reindexResult = 'reindexed-value';

        $fallbackField = new FluidFieldFacadeStub(2);
        $fallbackField->hasReindex = false;
        $fallbackField->data = 'fallback-value';

        $recordA = new FluidFieldRecordStub(1, 1, $reindexField);
        $recordB = new FluidFieldRecordStub(2, 2, $fallbackField);

        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$recordA, $recordB]));

        $this->assertSame('reindexed-value fallback-value', $this->fieldtype->reindex([]));
        $this->assertTrue($reindexField->getItem('field_search'));
    }

    public function testReindexReturnsEmptyStringWhenNoStoredFieldsExist()
    {
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $this->assertSame('', $this->fieldtype->reindex([]));
    }

    public function testPrepareDataPersistsFieldValueFormatAndTimezone()
    {
        $field = new FluidFieldFacadeStub(3);
        $field->data = 'normalized-value';
        $field->format = 'xhtml';
        $field->timezone = 'UTC';

        $record = new FluidFieldRecordStub(30, 3, $field);

        $result = $this->invokePrivateMethod($this->fieldtype, 'prepareData', [$record, ['field_id_3' => 'input']]);

        $this->assertSame('normalized-value', $result['field_id_3']);
        $this->assertSame('xhtml', $result['field_ft_3']);
        $this->assertSame('UTC', $result['field_dt_3']);
    }

    public function testUpdateFieldSavesModelAndUpdatesDataTable()
    {
        $field = new FluidFieldFacadeStub(4);
        $field->data = 'updated';

        $channelField = new FluidFieldChannelFieldStub(4, 'field_four', $field, 'text', 'Field Four', 'exp_channel_data_field_4');
        $record = new FluidFieldRecordStub(40, 4, $field, $channelField);
        $record->field_data_id = 444;

        $this->invokePrivateMethod($this->fieldtype, 'updateField', [$record, 7, ['id' => 2, 'order' => 3], ['field_id_4' => 'value']]);

        $this->assertSame(2, $record->field_group_id);
        $this->assertSame(3, $record->group);
        $this->assertSame(7, $record->order);
        $this->assertGreaterThan(0, $record->savedCount);
        $this->assertSame('exp_channel_data_field_4', $this->db->updates[0][0]);
    }

    public function testAddFieldCreatesFluidFieldRecordAndInsertsData()
    {
        $field = new FluidFieldFacadeStub(4);
        $field->data = 'stored-value';
        $record = new FluidFieldRecordStub(0, 4, $field, new FluidFieldChannelFieldStub(4, 'field_four', $field, 'text', 'Field Four', 'exp_channel_data_field_4'));

        $this->setModelMakeCallback(function ($model) use ($record) {
            if ($model === 'fluid_field:FluidField') {
                return $record;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection(), new FluidFieldChannelFieldStub(4, 'field_four', new FluidFieldFacadeStub(4), 'text', 'Field Four', 'exp_channel_data_field_4'));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $this->invokePrivateMethod($this->fieldtype, 'addField', [3, ['id' => 1, 'order' => 8], 4, ['field_id_4' => 'x']]);

        $this->assertGreaterThan(0, $record->savedCount);
        $this->assertSame('exp_channel_data_field_4', $this->db->inserts[0][0]);
        $this->assertSame($this->db->insertIdValue, $record->field_data_id);
    }

    public function testRemoveFieldHandlesGridAndRelationshipAndHooks()
    {
        $gridRecord = new FluidFieldRecordStub(70, 7, new FluidFieldFacadeStub(7), new FluidFieldChannelFieldStub(7, 'grid_field', new FluidFieldFacadeStub(7), 'grid', 'Grid Field', 'exp_channel_data_field_7'));
        $gridRecord->field_data_id = 707;

        $relationshipRecord = new FluidFieldRecordStub(80, 8, new FluidFieldFacadeStub(8), new FluidFieldChannelFieldStub(8, 'relationship_field', new FluidFieldFacadeStub(8), 'relationship', 'Relationship Field', 'exp_channel_data_field_8'));
        $relationshipRecord->field_data_id = 808;

        ee()->setMock('grid_lib', new FluidFieldGridLibStub());
        $this->extensions->activeHooks['fluid_field_remove_field'] = true;

        $this->invokePrivateMethod($this->fieldtype, 'removeField', [$gridRecord]);
        $this->invokePrivateMethod($this->fieldtype, 'removeField', [$relationshipRecord]);

        $this->assertTrue($gridRecord->deleted);
        $this->assertTrue($relationshipRecord->deleted);
        $this->assertNotEmpty($this->load->packagePaths);
        $this->assertGreaterThanOrEqual(3, count($this->db->deletes));
        $this->assertNotEmpty($this->extensions->calls);
    }

    public function testDisplayFieldRendersPublishViewInControlPanel()
    {
        $this->fieldtype->_init([
            'id' => 10,
            'name' => 'fluid_content',
            'content_id' => null,
            'content_type' => 'channel',
        ]);

        $this->fieldtype->settings['field_channel_fields'] = [];
        $this->fieldtype->settings['field_channel_field_groups'] = [];

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->display_field('stored-value');

        $this->assertSame('[view:fluid_field:publish]', $result);
        $this->assertNotEmpty($this->cp->scripts);
    }

    public function testDisplaySettingsReturnsSchemaForNewFields()
    {
        $this->fieldtype->_init([
            'id' => null,
            'name' => 'fluid_content',
            'content_id' => 99,
            'content_type' => 'channel',
        ]);

        $channelField = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));
        $group = new FluidFieldGroupStub(10, 'Body Group', 'body_group');

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField, $group) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$group]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->display_settings([]);

        $this->assertArrayHasKey('field_options_fluid_field', $result);
        $this->assertCount(0, $this->modal->modals);
    }

    public function testDisplaySettingsAddsModalAndJsForExistingField()
    {
        $channelField = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));
        $group = new FluidFieldGroupStub(10, 'Body Group', 'body_group');

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField, $group) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$group]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->display_settings([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => [10],
        ]);

        $this->assertArrayHasKey('field_options_fluid_field', $result);
        $this->assertNotEmpty($this->javascript->global);
        $this->assertNotEmpty($this->cp->scripts);
        $this->assertCount(1, $this->modal->modals);
    }

    public function testDisplaySettingsDefaultsGroupsToEmptyArrayWhenMissing()
    {
        $channelField = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->fieldtype->display_settings([
            'field_channel_fields' => [1],
        ]);

        $this->assertSame([], $this->javascript->global[0]['fields.fluid_field.groups']);
    }

    public function testSaveSettingsReturnsDefaultsIntersectionWithoutReindex()
    {
        $field = new FluidFieldChannelFieldStub(1, 'field_one');
        unset($this->fieldtype->settings['field_channel_field_groups']);

        $this->setModelGetCallback(function ($model, $id = null) use ($field) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$field]));
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->save_settings([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => []
        ]);

        $this->assertSame([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => []
        ], $result);

        $this->assertCount(0, $this->alert->alerts);
    }

    public function testSaveSettingsTriggersReindexWhenFieldsOrGroupsAreRemoved()
    {
        $this->fieldtype->settings['field_channel_fields'] = [1, 2];
        $this->fieldtype->settings['field_channel_field_groups'] = [5, 6];
        $this->fieldtype->settings['field_label'] = 'Fluid Content';

        $deletedByField = new FluidFieldTestCollection();
        $deletedByGroup = new FluidFieldTestCollection();
        $removedLabels = new FluidFieldTestCollection([(object) ['field_label' => 'Old Field']]);

        $call = 0;
        $this->setModelGetCallback(function ($model, $id = null) use (&$call, $deletedByField, $deletedByGroup, $removedLabels) {
            $call++;

            if ($call === 1 && $model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($call === 2 && $model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($call === 3 && $model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($deletedByField);
            }

            if ($call === 4 && $model === 'ChannelField') {
                return $this->makeModelQuery($removedLabels);
            }

            if ($call === 5 && $model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($deletedByGroup);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->save_settings([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => [5]
        ]);

        $this->assertSame([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => [5]
        ], $result);

        $this->assertTrue($deletedByField->deleted);
        $this->assertTrue($deletedByGroup->deleted);
        $this->assertNotEmpty($this->logger->actions);
        $this->assertCount(1, $this->alert->alerts);
        $this->assertNotEmpty($this->config->sitePrefsUpdates);
    }

    public function testSettingsModifyColumnDeletesWhenActionIsDelete()
    {
        $deletedCollection = new FluidFieldTestCollection();

        $this->setModelGetCallback(function ($model, $id = null) use ($deletedCollection) {
            if ($model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($deletedCollection);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $columns = $this->fieldtype->settings_modify_column([
            'field_id' => 10,
            'ee_action' => 'delete'
        ]);

        $this->assertTrue($deletedCollection->deleted);
        $this->assertArrayHasKey('field_id_10', $columns);
        $this->assertSame('mediumtext', $columns['field_id_10']['type']);
    }

    public function testDeleteRemovesFluidFieldRowsForEntryIds()
    {
        $deletedCollection = new FluidFieldTestCollection();

        $this->setModelGetCallback(function ($model, $id = null) use ($deletedCollection) {
            if ($model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($deletedCollection);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $this->fieldtype->delete([100, 101]);

        $this->assertTrue($deletedCollection->deleted);
    }

    public function testAcceptsContentType()
    {
        $this->assertFalse($this->fieldtype->accepts_content_type('grid'));
        $this->assertFalse($this->fieldtype->accepts_content_type('fluid_field'));
        $this->assertTrue($this->fieldtype->accepts_content_type('channel'));
    }

    public function testUpdateAlwaysReturnsTrue()
    {
        $this->assertTrue($this->fieldtype->update('7.0.0'));
    }

    public function testGetFieldDataReadsFromCacheWhenAvailable()
    {
        $cached = new FluidFieldTestCollection([new FluidFieldRecordStub(1, 1)]);
        $this->session->set_cache('FluidField', 'FluidField/10/99', $cached);

        $result = $this->invokePrivateMethod($this->fieldtype, 'getFieldData');

        $this->assertSame($cached, $result);
    }

    public function testGetFieldDataQueriesModelAndCachesOnMiss()
    {
        $fromModel = new FluidFieldTestCollection([new FluidFieldRecordStub(2, 2)]);

        $this->setModelGetCallback(function ($model, $id = null) use ($fromModel) {
            if ($model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($fromModel);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->invokePrivateMethod($this->fieldtype, 'getFieldData');

        $this->assertSame($fromModel, $result);
        $this->assertSame($fromModel, $this->session->cache['FluidField']['FluidField/10/99']);
    }

    public function testSetupFieldInstanceSetsDataFormatTimezoneAndId()
    {
        $field = new FluidFieldFacadeStub(7);

        $result = $this->invokePrivateMethod($this->fieldtype, 'setupFieldInstance', [
            $field,
            [
                'field_id_7' => 'value-7',
                'field_ft_7' => 'markdown',
                'field_dt_7' => 'America/New_York',
            ],
            88
        ]);

        $this->assertSame($field, $result);
        $this->assertSame(99, $field->contentId);
        $this->assertSame('value-7', $field->data);
        $this->assertSame('markdown', $field->format);
        $this->assertSame('America/New_York', $field->timezone);
        $this->assertSame(88, $field->getItem('fluid_field_data_id'));
    }

    public function testReplaceTagParsesInChannelScope()
    {
        $parser = new FluidFieldParserStub();
        ee()->setMock('fluid_field_parser', $parser);

        $this->fieldtype->row = ['field_id_10' => 'value'];

        $result = $this->fieldtype->replace_tag([], ['param' => 'value'], '{tagdata}');

        $this->assertSame('parsed-output', $result);
        $this->assertCount(1, $parser->parseCalls);
        $this->assertContains('fluid_field_parser', $this->load->libraries);
    }

    public function testReplaceTagInitializesChannelFieldsOutsideChannelScope()
    {
        $parser = new FluidFieldParserStub();
        $gridParser = new FluidFieldGridParserStub();
        ee()->setMock('fluid_field_parser', $parser);
        ee()->setMock('grid_parser', $gridParser);

        $this->fieldtype->_init([
            'id' => 10,
            'name' => 'fluid_content',
            'content_id' => 99,
            'content_type' => 'grid',
        ]);

        $result = $this->fieldtype->replace_tag([], [], '');

        $this->assertSame('parsed-output', $result);
        $this->assertContains('api', $this->load->libraries);
        $this->assertSame('fluid_content', $gridParser->fluid_field_field_names[10]);
    }

    public function testReplaceLengthDelegatesToReplaceTotalFields()
    {
        $mock = m::mock(Fluid_field_ft::class)->makePartial();
        $mock->_init([
            'id' => 10,
            'name' => 'fluid_content',
            'content_id' => 99,
            'content_type' => 'channel',
        ]);

        $mock->shouldReceive('replace_total_fields')
            ->once()
            ->with('data', ['type' => 'text'], 'tagdata')
            ->andReturn(42);

        $this->assertSame(42, $mock->replace_length('data', ['type' => 'text'], 'tagdata'));
    }

    public function testReplaceTotalFieldsReturnsZeroWhenLivePreviewOmitsFields()
    {
        $previewCollection = new FluidFieldTestCollection([
            new FluidFieldRecordStub(1, 1),
        ]);

        $this->session->set_cache('FluidField', 'FluidField/10/99', $previewCollection);

        $this->livePreview->hasData = true;
        $this->livePreview->entryData = [
            'entry_id' => 99,
        ];

        $this->assertSame(0, $this->fieldtype->replace_total_fields([], [], ''));
    }

    public function testReplaceTotalFieldsFiltersByTypeAndName()
    {
        $textField = new FluidFieldChannelFieldStub(1, 'alpha', new FluidFieldFacadeStub(1), 'text');
        $dateField = new FluidFieldChannelFieldStub(2, 'beta', new FluidFieldFacadeStub(2), 'date');

        $recordA = new FluidFieldRecordStub(1, 1, new FluidFieldFacadeStub(1), $textField);
        $recordB = new FluidFieldRecordStub(2, 2, new FluidFieldFacadeStub(2), $dateField);

        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$recordA, $recordB]));

        $count = $this->fieldtype->replace_total_fields([], ['type' => 'text', 'name' => 'alpha'], '');

        $this->assertSame(1, $count);
    }

    public function testReplaceTotalFieldsReturnsAllRowsWhenNoFiltersAreProvided()
    {
        $firstField = new FluidFieldChannelFieldStub(1, 'alpha', new FluidFieldFacadeStub(1), 'text');
        $secondField = new FluidFieldChannelFieldStub(2, 'beta', new FluidFieldFacadeStub(2), 'date');

        $recordA = new FluidFieldRecordStub(1, 1, new FluidFieldFacadeStub(1), $firstField);
        $recordB = new FluidFieldRecordStub(2, 2, new FluidFieldFacadeStub(2), $secondField);
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$recordA, $recordB]));

        $count = $this->fieldtype->replace_total_fields([], [], '');

        $this->assertSame(2, $count);
    }

    public function testValidateReturnsAjaxCallbackUsingFallbackFieldLookupAndArrayCasting()
    {
        $field = new FluidFieldFacadeStub(1);
        $field->nativeField = (object) ['has_array_data' => true];
        $channelField = new FluidFieldChannelFieldStub(1, 'title', $field);

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($model === 'ChannelField' && (int) $id === 1) {
                return $this->makeModelQuery(new FluidFieldTestCollection(), $channelField);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $fieldName = 'fluid_content[fields][new_field_1][field_group_id_3][field_id_1]';
        $rule = new FluidFieldRuleStub('callback');

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(
            new FluidFieldValidationResultStub(false, [$fieldName => [$rule]])
        );
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->input->ajax = true;
        $this->input->postData['ee_fv_field'] = $fieldName;

        $data = [
            'fields' => [
                'new_field_1' => [
                    'field_group_id_3' => [
                        'field_id_1' => 'scalar-value'
                    ]
                ]
            ]
        ];

        $this->assertSame('', $this->fieldtype->validate($data));
        $this->assertSame([], $field->getData());
    }

    public function testValidateSkipsAjaxFieldsThatDoNotMatchCurrentValidationTarget()
    {
        $field = new FluidFieldFacadeStub(1);
        $channelField = new FluidFieldChannelFieldStub(1, 'title', $field);

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $rule = new FluidFieldRuleStub('callback');
        $failed = ['fluid_content[fields][field_5][field_group_id_0][field_id_1]' => [$rule]];

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(false, $failed));
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->input->ajax = true;
        $this->input->postData['ee_fv_field'] = 'different_field_name';

        $data = [
            'fields' => [
                'field_5' => [
                    'field_group_id_0' => [
                        'field_id_1' => 'value'
                    ]
                ]
            ]
        ];

        $this->assertTrue($this->fieldtype->validate($data));
        $this->assertCount(0, $validation->validator->validateCalls);
    }

    public function testValidateReturnsTrueForNonAjaxValidSubFieldData()
    {
        $field = new FluidFieldFacadeStub(1);
        $channelField = new FluidFieldChannelFieldStub(1, 'title', $field);

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(true));
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->input->ajax = false;

        $data = [
            'fields' => [
                'field_5' => [
                    'field_group_id_0' => [
                        'field_id_1' => 'value'
                    ]
                ]
            ]
        ];

        $this->assertTrue($this->fieldtype->validate($data));
    }

    public function testSaveTreatsMissingExistingFieldAsNewForRevisions()
    {
        $existingRecord = new FluidFieldRecordStub(5, 1, new FluidFieldFacadeStub(1));
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$existingRecord]));

        $newField = new FluidFieldFacadeStub(2);
        $newField->setItem('field_search', true);
        $newField->saveResult = 'revision-search';
        $newRecord = new FluidFieldRecordStub(0, 2, $newField);

        $this->setModelMakeCallback(function ($model) use ($newRecord) {
            if ($model === 'fluid_field:FluidField') {
                return $newRecord;
            }

            return new FluidFieldRecordStub();
        });

        $this->request->values['version'] = 7;

        $result = $this->fieldtype->save([
            'fields' => [
                'field_77' => [
                    'field_group_id_0' => [
                        'field_id_2' => 'new-value'
                    ]
                ]
            ]
        ]);

        $this->assertSame('revision-search', $result);
        $this->assertCount(1, $this->modelService->makeCalls);
    }

    public function testPostSaveTreatsMissingExistingFieldAsNewForRevisions()
    {
        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'field_77' => [
                        'field_group_id_0' => [
                            'field_id_2' => 'new-value'
                        ]
                    ]
                ]
            ]
        );

        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $newField = new FluidFieldFacadeStub(2);
        $newRecord = new FluidFieldRecordStub(0, 2, $newField, new FluidFieldChannelFieldStub(2, 'field_two', $newField));

        $this->setModelMakeCallback(function ($model) use ($newRecord) {
            if ($model === 'fluid_field:FluidField') {
                return $newRecord;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField' && (int) $id === 2) {
                return $this->makeModelQuery(new FluidFieldTestCollection(), new FluidFieldChannelFieldStub(2, 'field_two', new FluidFieldFacadeStub(2)));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->request->values['version'] = 9;

        $this->fieldtype->post_save([]);

        $this->assertNotEmpty($this->db->inserts);
        $this->assertSame('exp_channel_data_field_2', $this->db->inserts[0][0]);
    }

    public function testPrepareDataOmitsFormatAndTimezoneWhenUnavailable()
    {
        $field = new FluidFieldFacadeStub(4);
        $field->data = 'normalized';
        $field->format = null;
        $field->timezone = null;

        $record = new FluidFieldRecordStub(40, 4, $field);
        $result = $this->invokePrivateMethod($this->fieldtype, 'prepareData', [$record, ['field_id_4' => 'raw']]);

        $this->assertSame('normalized', $result['field_id_4']);
        $this->assertArrayNotHasKey('field_ft_4', $result);
        $this->assertArrayNotHasKey('field_dt_4', $result);
    }

    public function testUpdateFieldUsesHookReturnPayloadWhenExtensionIsActive()
    {
        $field = new FluidFieldFacadeStub(4);
        $channelField = new FluidFieldChannelFieldStub(4, 'field_four', $field, 'text', 'Field Four', 'exp_channel_data_field_4');
        $record = new FluidFieldRecordStub(40, 4, $field, $channelField);
        $record->field_data_id = 444;

        $this->extensions->activeHooks['fluid_field_update_field'] = true;
        $this->extensions->callReturn = [
            'field_id_4' => 'hooked',
            'field_ft_4' => 'xhtml',
        ];

        $this->invokePrivateMethod($this->fieldtype, 'updateField', [$record, 7, ['id' => 2, 'order' => 3], ['field_id_4' => 'value']]);

        $this->assertSame(['field_id_4' => 'hooked', 'field_ft_4' => 'xhtml'], $this->db->setValues[0]);
        $this->assertNotEmpty($this->extensions->calls);
    }

    public function testAddFieldUsesHookPayloadAndSupportsMissingGroupMetadata()
    {
        $field = new FluidFieldFacadeStub(4);
        $field->data = 'stored-value';
        $record = new FluidFieldRecordStub(0, 4, $field, new FluidFieldChannelFieldStub(4, 'field_four', $field, 'text', 'Field Four', 'exp_channel_data_field_4'));

        $this->setModelMakeCallback(function ($model) use ($record) {
            if ($model === 'fluid_field:FluidField') {
                return $record;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField' && (int) $id === 4) {
                return $this->makeModelQuery(new FluidFieldTestCollection(), new FluidFieldChannelFieldStub(4, 'field_four', new FluidFieldFacadeStub(4), 'text', 'Field Four', 'exp_channel_data_field_4'));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->extensions->activeHooks['fluid_field_add_field'] = true;
        $this->extensions->callReturn = [
            'field_id_4' => 'hooked',
            'entry_id' => 0,
        ];

        $this->invokePrivateMethod($this->fieldtype, 'addField', [3, [], 4, ['field_id_4' => 'x']]);

        $this->assertNull($record->field_group_id);
        $this->assertNull($record->group);
        $this->assertSame('exp_channel_data_field_4', $this->db->inserts[0][0]);
        $this->assertNotEmpty($this->extensions->calls);
    }

    public function testRemoveFieldDeletesSimpleFieldWithoutSpecialCleanupHooks()
    {
        $record = new FluidFieldRecordStub(
            90,
            9,
            new FluidFieldFacadeStub(9),
            new FluidFieldChannelFieldStub(9, 'text_field', new FluidFieldFacadeStub(9), 'text', 'Text Field', 'exp_channel_data_field_9')
        );
        $record->field_data_id = 909;

        $this->invokePrivateMethod($this->fieldtype, 'removeField', [$record]);

        $this->assertTrue($record->deleted);
        $this->assertCount(1, $this->db->deletes);
        $this->assertEmpty($this->extensions->calls);
    }

    public function testDisplayFieldBuildsStoredRowsForGroupedAndStandaloneFields()
    {
        $standaloneFacade = new FluidFieldFacadeStub(1);
        $groupFacade = new FluidFieldFacadeStub(2);

        $standaloneField = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));
        $groupField = new FluidFieldChannelFieldStub(2, 'body', $groupFacade);
        $missingGroupField = new class(3, 'summary', new FluidFieldFacadeStub(3)) extends FluidFieldChannelFieldStub {
            public function getField()
            {
                return clone parent::getField();
            }
        };

        $group = new FluidFieldGroupStub(10, 'Body Group', 'body_group', new FluidFieldTestCollection([$groupField, $missingGroupField]));

        $standaloneRecord = new FluidFieldRecordStub(101, 1, $standaloneFacade, $standaloneField, null);
        $standaloneRecord->order = 1;
        $standaloneRecord->group = null;

        $groupRecord = new FluidFieldRecordStub(102, 2, $groupFacade, $groupField, $group);
        $groupRecord->order = 2;
        $groupRecord->group = 2;
        $groupRecord->field_group_id = 10;

        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$standaloneRecord, $groupRecord]));

        $this->fieldtype->settings['field_channel_fields'] = [1, 2, 3];
        $this->fieldtype->settings['field_channel_field_groups'] = [10];

        $allFields = new FluidFieldTestCollection([$standaloneField, $groupField, $missingGroupField]);
        $groups = new FluidFieldTestCollection([$group]);

        $this->setModelGetCallback(function ($model, $id = null) use ($allFields, $groups) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery($allFields);
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery($groups);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->fieldtype->display_field('stored-value');
        $views = array_map(function ($render) {
            return $render['view'];
        }, $this->viewService->renders);
        $storedGroupMissingFieldName = null;
        foreach ($this->viewService->renders as $render) {
            if ($render['view'] !== 'fluid_field:fieldgroup'
                || !isset($render['data']['field_group_fields'])
                || !is_array($render['data']['field_group_fields'])) {
                continue;
            }

            foreach ($render['data']['field_group_fields'] as $groupField) {
                if ($groupField->getId() === 3) {
                    $storedGroupMissingFieldName = $groupField->getName();
                }
            }
            break;
        }

        $this->assertSame('[view:fluid_field:publish]', $result);
        $this->assertContains('fluid_field:field', $views);
        $this->assertContains('fluid_field:fieldgroup', $views);
        $this->assertSame('fluid_content[fields][field_101][field_group_id_0][field_id_1]', $standaloneFacade->getName());
        $this->assertSame('fluid_content[fields][new_field_for_group_2][field_group_id_10][field_id_3]', $storedGroupMissingFieldName);
    }

    public function testDisplayFieldRebuildsRowsFromPostedArrayData()
    {
        $fieldOne = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));
        $fieldTwo = new FluidFieldChannelFieldStub(2, 'body', new FluidFieldFacadeStub(2));
        $fieldThree = new FluidFieldChannelFieldStub(3, 'summary', new FluidFieldFacadeStub(3));

        $group = new FluidFieldGroupStub(10, 'Body Group', 'body_group', new FluidFieldTestCollection([$fieldTwo, $fieldThree]));

        $mappedRecord = new FluidFieldRecordStub(5, 1, new FluidFieldFacadeStub(1), $fieldOne, null);
        $mappedRecord->group = 7;

        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$mappedRecord]));

        $this->fieldtype->settings['field_channel_fields'] = [1, 2, 3];
        $this->fieldtype->settings['field_channel_field_groups'] = [10];
        $this->request->values['version'] = 3;

        $allFields = new FluidFieldTestCollection([$fieldOne, $fieldTwo, $fieldThree]);
        $groups = new FluidFieldTestCollection([$group]);

        $this->setModelGetCallback(function ($model, $id = null) use ($allFields, $groups) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery($allFields);
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery($groups);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->fieldtype->display_field([
            'fields' => [
                'field_5' => [
                    'field_id_1' => 'legacy-shape'
                ],
                'new_field_for_group_7' => [
                    'field_group_id_10' => [
                        'field_id_2' => 'group-value'
                    ]
                ]
            ]
        ]);

        $hasLegacyFieldView = false;
        $hasGroupView = false;
        foreach ($this->viewService->renders as $render) {
            if ($render['view'] === 'fluid_field:field'
                && isset($render['data']['field'])
                && strpos($render['data']['field']->getName(), '[field_5][field_group_id_0][field_id_1]') !== false) {
                $hasLegacyFieldView = true;
            }
            if ($render['view'] === 'fluid_field:fieldgroup'
                && isset($render['data']['field_group'])
                && $render['data']['field_group']->getId() === 10) {
                $hasGroupView = true;
            }
        }

        $this->assertSame('[view:fluid_field:publish]', $result);
        $this->assertTrue($hasLegacyFieldView);
        $this->assertTrue($hasGroupView);
    }

    public function testSaveSettingsCreatesLegacyTablesForSelectedFieldsAndGroups()
    {
        unset($this->fieldtype->settings['field_channel_fields']);
        unset($this->fieldtype->settings['field_channel_field_groups']);

        $directLegacyField = new FluidFieldChannelFieldStub(1, 'direct_legacy', new FluidFieldFacadeStub(1));
        $groupLegacyField = new FluidFieldChannelFieldStub(2, 'group_legacy', new FluidFieldFacadeStub(2));
        $group = new FluidFieldGroupStub(10, 'Body Group', 'body_group', new FluidFieldTestCollection([$groupLegacyField]));

        $this->setModelGetCallback(function ($model, $id = null) use ($directLegacyField, $group) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$directLegacyField]));
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$group]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->fieldtype->save_settings([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => [10],
        ]);

        $this->assertSame([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => [10],
        ], $result);
        $this->assertSame(1, $directLegacyField->createTableCalls);
        $this->assertSame(1, $groupLegacyField->createTableCalls);
    }

    public function testSettingsModifyColumnDoesNotDeleteWhenNoDeleteActionProvided()
    {
        $columns = $this->fieldtype->settings_modify_column([
            'field_id' => 11,
            'ee_action' => 'save'
        ]);

        $fluidDeletes = array_filter($this->modelService->getCalls, function ($call) {
            return $call[0] === 'fluid_field:FluidField';
        });

        $this->assertArrayHasKey('field_id_11', $columns);
        $this->assertCount(0, $fluidDeletes);
    }

    public function testGetFieldDataUsesProvidedFieldAndEntryIdentifiers()
    {
        $fromModel = new FluidFieldTestCollection([new FluidFieldRecordStub(2, 2)]);

        $this->setModelGetCallback(function ($model, $id = null) use ($fromModel) {
            if ($model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($fromModel);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->invokePrivateMethod($this->fieldtype, 'getFieldData', [55, 12]);

        $this->assertSame($fromModel, $result);
        $this->assertSame($fromModel, $this->session->cache['FluidField']['FluidField/55/12']);
    }

    public function testSetupFieldInstanceLeavesUnsetValuesUntouched()
    {
        $field = new FluidFieldFacadeStub(8);
        $field->setData('original');
        $field->setFormat('text');
        $field->setTimezone('UTC');

        $result = $this->invokePrivateMethod($this->fieldtype, 'setupFieldInstance', [$field, [], null]);

        $this->assertSame($field, $result);
        $this->assertSame('original', $field->getData());
        $this->assertSame('text', $field->getFormat());
        $this->assertSame('UTC', $field->getTimezone());
        $this->assertNull($field->getItem('fluid_field_data_id'));
    }

    public function testReplaceTotalFieldsUsesPreviewOverrideForMatchingEntry()
    {
        $previewCollection = new FluidFieldTestCollection([
            new FluidFieldRecordStub(1, 1),
            new FluidFieldRecordStub(2, 2),
        ]);
        $this->session->set_cache('FluidField', 'FluidField/10/99', $previewCollection);

        $this->livePreview->hasData = true;
        $this->livePreview->entryData = [
            'entry_id' => 99,
            'field_id_10' => ['fields' => []],
        ];

        $parser = ee()->fluid_field_parser;

        $count = $this->fieldtype->replace_total_fields([], [], '');

        $this->assertSame(2, $count);
        $this->assertCount(1, $parser->overrideCalls);
    }

    public function testReplaceTotalFieldsIgnoresPreviewDataForDifferentEntry()
    {
        $previewCollection = new FluidFieldTestCollection([
            new FluidFieldRecordStub(1, 1),
        ]);
        $this->session->set_cache('FluidField', 'FluidField/10/99', $previewCollection);

        $this->livePreview->hasData = true;
        $this->livePreview->entryData = [
            'entry_id' => 101,
            'field_id_10' => ['fields' => []],
        ];

        $parser = ee()->fluid_field_parser;

        $count = $this->fieldtype->replace_total_fields([], [], '');

        $this->assertSame(1, $count);
        $this->assertCount(0, $parser->overrideCalls);
    }

    public function testValidateSkipsWhenFieldCannotBeResolvedFromFallbackLookup()
    {
        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($model === 'ChannelField' && (int) $id === 99) {
                return $this->makeModelQuery(new FluidFieldTestCollection(), null);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(true));
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [99];

        $result = $this->fieldtype->validate([
            'fields' => [
                'field_5' => [
                    'field_group_id_0' => [
                        'field_id_99' => 'value',
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result);
        $this->assertCount(0, $validation->validator->validateCalls);
    }

    public function testValidateIgnoresNonFieldKeysWithinDatum()
    {
        $field = new FluidFieldFacadeStub(1);
        $channelField = new FluidFieldChannelFieldStub(1, 'title', $field);

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(true));
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [1];

        $result = $this->fieldtype->validate([
            'fields' => [
                'field_5' => [
                    'field_group_id_0' => [
                        'non_field_key' => 'skip-me',
                    ],
                ],
            ],
        ]);

        $this->assertTrue($result);
        $this->assertCount(0, $validation->validator->validateCalls);
    }

    public function testSaveHandlesPositiveGroupIdWithoutSearchCompilation()
    {
        $existingField = new FluidFieldFacadeStub(1);
        $existingField->saveResult = 'ignored';

        $existingRecord = new FluidFieldRecordStub(5, 1, $existingField);
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$existingRecord]));

        $result = $this->fieldtype->save([
            'fields' => [
                'field_5' => [
                    'field_group_id_4' => [
                        'field_id_1' => 'existing-value',
                    ],
                ],
            ],
        ]);

        $this->assertSame('', $result);
        $this->assertSame(4, $existingRecord->field_group_id);
    }

    public function testSaveSkipsLegacyRowsWithoutGroupWrapperWhenNoFieldIdsArePresent()
    {
        $existingField = new FluidFieldFacadeStub(1);
        $existingRecord = new FluidFieldRecordStub(5, 1, $existingField);
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$existingRecord]));

        $result = $this->fieldtype->save([
            'fields' => [
                'field_5' => [
                    'legacy_key' => 'legacy-value',
                ],
            ],
        ]);

        $this->assertSame('', $result);
        $this->assertCount(0, $this->modelService->makeCalls);
        $this->assertNull($existingField->getItem('fluid_field_data_id'));
    }

    public function testPostSaveHandlesNewFieldForGroupAndSkipsNonFieldKeys()
    {
        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'new_field_for_group_12' => [
                        'field_group_id_10' => [
                            'non_field_key' => 'skip-me',
                            'field_id_2' => 'new-value',
                        ],
                    ],
                ],
            ]
        );
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $newField = new FluidFieldFacadeStub(2);
        $newRecord = new FluidFieldRecordStub(0, 2, $newField, new FluidFieldChannelFieldStub(2, 'field_two', $newField));

        $this->setModelMakeCallback(function ($model) use ($newRecord) {
            if ($model === 'fluid_field:FluidField') {
                return $newRecord;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField' && (int) $id === 2) {
                return $this->makeModelQuery(new FluidFieldTestCollection(), new FluidFieldChannelFieldStub(2, 'field_two', new FluidFieldFacadeStub(2)));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->fieldtype->post_save([]);

        $this->assertSame(10, $newRecord->field_group_id);
        $this->assertSame(1, $newRecord->group);
        $this->assertNotEmpty($this->db->inserts);
        $this->assertSame('exp_channel_data_field_2', $this->db->inserts[0][0]);
    }

    public function testPostSaveSkipsTemplateAndLegacyRowsWithoutFieldIds()
    {
        $existingRecord = new FluidFieldRecordStub(5, 1, new FluidFieldFacadeStub(1));
        $existingRecord->field_data_id = 500;

        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'new_field_0' => [
                        'field_group_id_0' => [
                            'field_id_1' => 'template-value',
                        ],
                    ],
                    'field_5' => [
                        'legacy_key' => [
                            'non_field_key' => 'skip-me',
                        ],
                    ],
                ],
            ]
        );
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$existingRecord]));

        $this->fieldtype->post_save([]);

        $this->assertCount(0, $this->db->updates);
        $this->assertCount(0, $this->db->inserts);
        $this->assertTrue($existingRecord->deleted);
    }

    public function testPostSaveKeepsGroupOrderForMultipleFieldsWithinSameRow()
    {
        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'new_field_1' => [
                        'field_group_id_10' => [
                            'field_id_2' => 'alpha',
                            'field_id_3' => 'beta',
                        ],
                    ],
                ],
            ]
        );
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $createdRecords = [];
        $this->setModelMakeCallback(function ($model) use (&$createdRecords) {
            if ($model === 'fluid_field:FluidField') {
                $record = new FluidFieldRecordStub();
                $createdRecords[] = $record;

                return $record;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField' && $id !== null) {
                $fieldId = (int) $id;
                $field = new FluidFieldFacadeStub($fieldId);

                return $this->makeModelQuery(
                    new FluidFieldTestCollection(),
                    new FluidFieldChannelFieldStub($fieldId, 'field_' . $fieldId, $field)
                );
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->fieldtype->post_save([]);

        $this->assertCount(2, $createdRecords);
        $this->assertSame(1, $createdRecords[0]->group);
        $this->assertSame(1, $createdRecords[1]->group);
        $this->assertSame(10, $createdRecords[0]->field_group_id);
        $this->assertSame(10, $createdRecords[1]->field_group_id);
        $this->assertCount(2, $this->db->inserts);
    }

    public function testPostSaveIncrementsGroupOrderWhenSameGroupIdAppearsInDifferentRows()
    {
        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'new_field_1' => [
                        'field_group_id_10' => [
                            'field_id_2' => 'alpha',
                        ],
                    ],
                    'new_field_2' => [
                        'field_group_id_10' => [
                            'field_id_3' => 'beta',
                        ],
                    ],
                ],
            ]
        );
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $createdRecords = [];
        $this->setModelMakeCallback(function ($model) use (&$createdRecords) {
            if ($model === 'fluid_field:FluidField') {
                $record = new FluidFieldRecordStub();
                $createdRecords[] = $record;

                return $record;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'ChannelField' && $id !== null) {
                $fieldId = (int) $id;
                $field = new FluidFieldFacadeStub($fieldId);

                return $this->makeModelQuery(
                    new FluidFieldTestCollection(),
                    new FluidFieldChannelFieldStub($fieldId, 'field_' . $fieldId, $field)
                );
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->fieldtype->post_save([]);

        $this->assertCount(2, $createdRecords);
        $this->assertSame(1, $createdRecords[0]->group);
        $this->assertSame(2, $createdRecords[1]->group);
        $this->assertSame(10, $createdRecords[0]->field_group_id);
        $this->assertSame(10, $createdRecords[1]->field_group_id);
        $this->assertCount(2, $this->db->inserts);
    }

    public function testDisplayFieldRendersStandaloneRowFromPostedData()
    {
        $field = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));
        $allFields = new FluidFieldTestCollection([$field]);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->fieldtype->settings['field_channel_field_groups'] = [];
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $this->setModelGetCallback(function ($model, $id = null) use ($allFields) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery($allFields);
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->fieldtype->display_field([
            'fields' => [
                'new_field_1' => [
                    'field_group_id_0' => [
                        'field_id_1' => 'posted-value',
                    ],
                ],
            ],
        ]);

        $hasStandaloneRow = false;
        $hasGroupedRow = false;

        foreach ($this->viewService->renders as $render) {
            if (
                $render['view'] === 'fluid_field:field'
                && isset($render['data']['field'])
                && strpos($render['data']['field']->getName(), '[new_field_1][field_group_id_0][field_id_1]') !== false
            ) {
                $hasStandaloneRow = true;
            }

            if ($render['view'] === 'fluid_field:fieldgroup' && isset($render['data']['field_group'])) {
                $hasGroupedRow = true;
            }
        }

        $this->assertSame('[view:fluid_field:publish]', $result);
        $this->assertTrue($hasStandaloneRow);
        $this->assertFalse($hasGroupedRow);
    }

    public function testDisplayFieldUsesEmptyPrefixWhenShortNameAndGroupSettingsAreMissing()
    {
        $this->fieldtype->_init([
            'id' => 10,
            'name' => 'fluid_content',
            'content_id' => null,
            'content_type' => 'channel',
        ]);

        $field = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));
        $allFields = new FluidFieldTestCollection([$field]);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->fieldtype->settings['field_short_name'] = '';
        unset($this->fieldtype->settings['field_channel_field_groups']);

        $this->setModelGetCallback(function ($model, $id = null) use ($allFields) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery($allFields);
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->fieldtype->display_field('stored-value');

        $templatePrefix = null;
        foreach ($this->viewService->renders as $render) {
            if ($render['view'] === 'fluid_field:field' && isset($render['data']['field_name_prefix'])) {
                $templatePrefix = $render['data']['field_name_prefix'];
                break;
            }
        }

        $this->assertSame('[view:fluid_field:publish]', $result);
        $this->assertSame('', $templatePrefix);
    }

    public function testDisplayFieldSkipsNonFieldPostedKeysAndOmitsEmptyGroupFilters()
    {
        $field = new FluidFieldChannelFieldStub(1, 'title', new FluidFieldFacadeStub(1));
        $emptyGroup = new FluidFieldGroupStub(10, 'Empty Group', 'empty_group', new FluidFieldTestCollection());
        $allFields = new FluidFieldTestCollection([$field]);
        $groups = new FluidFieldTestCollection([$emptyGroup]);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->fieldtype->settings['field_channel_field_groups'] = [10];
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $this->setModelGetCallback(function ($model, $id = null) use ($allFields, $groups) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery($allFields);
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery($groups);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->fieldtype->display_field([
            'fields' => [
                'new_field_1' => [
                    'field_group_id_0' => [
                        'not_a_field' => 'skip-me',
                        'field_id_1' => 'posted-value',
                    ],
                ],
            ],
        ]);

        $filterCount = null;
        foreach ($this->viewService->renders as $render) {
            if ($render['view'] === 'fluid_field:filters') {
                $filterCount = count($render['data']['filters']);
                break;
            }
        }

        $this->assertSame('[view:fluid_field:publish]', $result);
        $this->assertSame(1, $filterCount);
    }

    public function testSaveSettingsNormalizesEmptyStringFieldSelection()
    {
        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->fieldtype->settings['field_label'] = 'Fluid Content';
        unset($this->fieldtype->settings['field_channel_field_groups']);

        $deletedByField = new FluidFieldTestCollection();
        $removedLabels = new FluidFieldTestCollection();

        $call = 0;
        $this->setModelGetCallback(function ($model, $id = null) use (&$call, $deletedByField, $removedLabels) {
            $call++;

            if ($call === 1 && $model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($call === 2 && $model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($call === 3 && $model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($deletedByField);
            }

            if ($call === 4 && $model === 'ChannelField') {
                return $this->makeModelQuery($removedLabels);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->save_settings([
            'field_channel_fields' => '',
            'field_channel_field_groups' => [],
        ]);

        $this->assertSame([], $result['field_channel_fields']);
        $this->assertTrue($deletedByField->deleted);
        $this->assertCount(1, $this->alert->alerts);
    }

    public function testSaveSettingsSkipsLoggingWhenRemovedFieldsHaveNoLabels()
    {
        $this->fieldtype->settings['field_channel_fields'] = [1, 2];
        $this->fieldtype->settings['field_label'] = 'Fluid Content';
        unset($this->fieldtype->settings['field_channel_field_groups']);

        $deletedByField = new FluidFieldTestCollection();
        $removedLabels = new FluidFieldTestCollection();

        $call = 0;
        $this->setModelGetCallback(function ($model, $id = null) use (&$call, $deletedByField, $removedLabels) {
            $call++;

            if ($call === 1 && $model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($call === 2 && $model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            if ($call === 3 && $model === 'fluid_field:FluidField') {
                return $this->makeModelQuery($deletedByField);
            }

            if ($call === 4 && $model === 'ChannelField') {
                return $this->makeModelQuery($removedLabels);
            }

            return $this->makeModelQuery(new FluidFieldTestCollection());
        });

        $result = $this->fieldtype->save_settings([
            'field_channel_fields' => [1],
            'field_channel_field_groups' => [],
        ]);

        $this->assertSame([1], $result['field_channel_fields']);
        $this->assertTrue($deletedByField->deleted);
        $this->assertCount(0, $this->logger->actions);
        $this->assertCount(1, $this->alert->alerts);
    }

    public function testRemoveFieldHandlesFileGridCleanup()
    {
        $record = new FluidFieldRecordStub(
            71,
            7,
            new FluidFieldFacadeStub(7),
            new FluidFieldChannelFieldStub(7, 'file_grid_field', new FluidFieldFacadeStub(7), 'file_grid', 'File Grid', 'exp_channel_data_field_7')
        );
        $record->field_data_id = 707;

        ee()->setMock('grid_lib', new FluidFieldGridLibStub());

        $this->invokePrivateMethod($this->fieldtype, 'removeField', [$record]);

        $this->assertTrue($record->deleted);
        $this->assertNotEmpty($this->load->packagePaths);
        $this->assertSame('exp_channel_data_field_7', $this->db->deletes[0][0]);
    }

    public function testDisplaySettingsFiltersOutUnsupportedFieldtypes()
    {
        $this->fieldtype->_init([
            'id' => null,
            'name' => 'fluid_content',
            'content_id' => 99,
            'content_type' => 'channel',
        ]);

        $unsupportedFieldFacade = new FluidFieldFacadeStub(1);
        $unsupportedFieldFacade->accepts = false;
        $supportedFieldFacade = new FluidFieldFacadeStub(2);
        $supportedFieldFacade->accepts = true;

        $unsupportedField = new FluidFieldChannelFieldStub(1, 'unsupported', $unsupportedFieldFacade, 'text', 'Unsupported');
        $supportedField = new FluidFieldChannelFieldStub(2, 'supported', $supportedFieldFacade, 'text', 'Supported');

        $this->setModelGetCallback(function ($model, $id = null) use ($unsupportedField, $supportedField) {
            if ($model === 'ChannelField') {
                return $this->makeModelQuery(new FluidFieldTestCollection([$unsupportedField, $supportedField]));
            }

            if ($model === 'ChannelFieldGroup') {
                return $this->makeModelQuery(new FluidFieldTestCollection());
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $result = $this->fieldtype->display_settings([]);
        $choices = $result['field_options_fluid_field']['settings'][0]['fields']['field_channel_fields']['choices'];
        $choiceValues = array_map(function ($choice) {
            return $choice['value'];
        }, $choices->asArray());

        $this->assertCount(1, $choices);
        $this->assertSame([2], $choiceValues);
    }

    public function testReplaceTotalFieldsFiltersByNameOnly()
    {
        $firstField = new FluidFieldChannelFieldStub(1, 'alpha', new FluidFieldFacadeStub(1), 'text');
        $secondField = new FluidFieldChannelFieldStub(2, 'beta', new FluidFieldFacadeStub(2), 'text');

        $recordA = new FluidFieldRecordStub(1, 1, new FluidFieldFacadeStub(1), $firstField);
        $recordB = new FluidFieldRecordStub(2, 2, new FluidFieldFacadeStub(2), $secondField);
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$recordA, $recordB]));

        $count = $this->fieldtype->replace_total_fields([], ['name' => 'beta'], '');

        $this->assertSame(1, $count);
    }

    public function testReplaceTotalFieldsFiltersByTypeOnly()
    {
        $textField = new FluidFieldChannelFieldStub(1, 'alpha', new FluidFieldFacadeStub(1), 'text');
        $dateField = new FluidFieldChannelFieldStub(2, 'beta', new FluidFieldFacadeStub(2), 'date');

        $recordA = new FluidFieldRecordStub(1, 1, new FluidFieldFacadeStub(1), $textField);
        $recordB = new FluidFieldRecordStub(2, 2, new FluidFieldFacadeStub(2), $dateField);
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection([$recordA, $recordB]));

        $count = $this->fieldtype->replace_total_fields([], ['type' => 'date'], '');

        $this->assertSame(1, $count);
    }

    public function testValidateHandlesGroupLessPostedRowKeys()
    {
        $field = new FluidFieldFacadeStub(1);
        $channelField = new FluidFieldChannelFieldStub(1, 'title', $field);

        $this->setModelGetCallback(function ($model, $id = null) use ($channelField) {
            if ($model === 'ChannelField' && $id === null) {
                return $this->makeModelQuery(new FluidFieldTestCollection([$channelField]));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $validation = new FluidFieldValidationServiceStub();
        $validation->validator = new FluidFieldValidatorStub(new FluidFieldValidationResultStub(true));
        ee()->setMock('Validation', $validation);

        $this->fieldtype->settings['field_channel_fields'] = [1];
        $this->fieldtype->settings['field_channel_field_groups'] = [];

        $data = [
            'fields' => [
                'legacy_row' => [
                    'legacy_group' => [
                        'field_id_1' => 'value'
                    ]
                ]
            ]
        ];

        $this->assertTrue($this->fieldtype->validate($data));
        $this->assertCount(1, $validation->validator->validateCalls);
    }

    public function testPostSaveUsesCloningModeLookupForMissingExistingFieldRows()
    {
        if (!defined('CLONING_MODE')) {
            define('CLONING_MODE', true);
        }

        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'field_77' => [
                        'field_group_id_10' => [
                            'field_id_2' => 'cloned-value',
                        ],
                    ],
                ],
            ]
        );
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $newField = new FluidFieldFacadeStub(2);
        $newRecord = new FluidFieldRecordStub(0, 2, $newField, new FluidFieldChannelFieldStub(2, 'field_two', $newField));

        $clonedSource = (object) ['group' => 4];

        $this->setModelMakeCallback(function ($model) use ($newRecord) {
            if ($model === 'fluid_field:FluidField') {
                return $newRecord;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) use ($clonedSource) {
            if ($model === 'fluid_field:FluidField') {
                return $this->makeModelQuery(new FluidFieldTestCollection(), $clonedSource);
            }

            if ($model === 'ChannelField' && (int) $id === 2) {
                return $this->makeModelQuery(new FluidFieldTestCollection(), new FluidFieldChannelFieldStub(2, 'field_two', new FluidFieldFacadeStub(2)));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->fieldtype->post_save([]);

        $cloningLookups = array_filter($this->modelService->getCalls, function ($call) {
            return $call[0] === 'fluid_field:FluidField';
        });

        $this->assertCount(1, $cloningLookups);
        $this->assertSame(10, $newRecord->field_group_id);
        $this->assertSame(1, $newRecord->group);
        $this->assertNotEmpty($this->db->inserts);
    }

    public function testPostSaveCloningModeHandlesMissingCloneLookupResult()
    {
        if (!defined('CLONING_MODE')) {
            define('CLONING_MODE', true);
        }

        $this->session->set_cache(
            Fluid_field_ft::class,
            $this->fieldtype->name(),
            [
                'fields' => [
                    'field_77' => [
                        'field_group_id_10' => [
                            'field_id_2' => 'cloned-value',
                        ],
                    ],
                ],
            ]
        );
        $this->session->set_cache('FluidField', 'FluidField/10/99', new FluidFieldTestCollection());

        $newField = new FluidFieldFacadeStub(2);
        $newRecord = new FluidFieldRecordStub(0, 2, $newField, new FluidFieldChannelFieldStub(2, 'field_two', $newField));

        $this->setModelMakeCallback(function ($model) use ($newRecord) {
            if ($model === 'fluid_field:FluidField') {
                return $newRecord;
            }

            return new FluidFieldRecordStub();
        });

        $this->setModelGetCallback(function ($model, $id = null) {
            if ($model === 'fluid_field:FluidField') {
                return $this->makeModelQuery(new FluidFieldTestCollection(), null);
            }

            if ($model === 'ChannelField' && (int) $id === 2) {
                return $this->makeModelQuery(new FluidFieldTestCollection(), new FluidFieldChannelFieldStub(2, 'field_two', new FluidFieldFacadeStub(2)));
            }

            return $this->makeModelQuery(new FluidFieldTestCollection(), null);
        });

        $this->fieldtype->post_save([]);

        $this->assertSame(10, $newRecord->field_group_id);
        $this->assertSame(1, $newRecord->group);
        $this->assertNotEmpty($this->db->inserts);
    }
}

// EOF
