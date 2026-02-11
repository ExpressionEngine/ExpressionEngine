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
}

// EOF
