<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2026, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

require_once __DIR__ . '/FileFtTestBase.php';

class FileFtValidateTest extends FileFtTestBase
{
    /**
     * Assert boolean required fields reject empty submissions.
     *
     * @return void
     */
    public function testValidateReturnsRequiredErrorWhenBooleanRequiredFieldIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => true,
        ]);

        $this->assertSame([
            'value' => '',
            'error' => 'required',
        ], $fieldtype->validate(''));
    }

    /**
     * Assert string required fields reject empty submissions.
     *
     * @return void
     */
    public function testValidateReturnsRequiredErrorWhenStringRequiredFieldIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
        ]);

        $this->assertSame([
            'value' => '',
            'error' => 'required',
        ], $fieldtype->validate(null));
    }

    /**
     * Assert optional boolean fields accept empty submissions.
     *
     * @return void
     */
    public function testValidateReturnsEmptyValueWhenBooleanOptionalFieldIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => false,
        ]);

        $this->assertSame([
            'value' => '',
        ], $fieldtype->validate(''));
    }

    /**
     * Assert optional string fields accept empty submissions.
     *
     * @return void
     */
    public function testValidateReturnsEmptyValueWhenStringOptionalFieldIsEmpty()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'n',
        ]);

        $this->assertSame([
            'value' => '',
        ], $fieldtype->validate(null));
    }

    /**
     * Assert unknown field data returns the invalid-selection error.
     *
     * @return void
     */
    public function testValidateReturnsInvalidSelectionWhenFieldDataDoesNotMapToAFile()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
        ]);

        $this->assertSame([
            'value' => '',
            'error' => 'invalid_selection',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame(['{filedir_1}banner.png'], $this->fileFieldMock->calls);
    }

    /**
     * Assert new selections fail when no authenticated member is available.
     *
     * @return void
     */
    public function testValidateReturnsDirectoryErrorWhenNewSelectionHasNoAccessibleMember()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
        ]);
        $file = new FileFtFileModelStub(true);
        $this->setFileModel($file);

        $this->assertSame([
            'value' => '',
            'error' => 'directory_no_access',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame([], $file->memberChecks);
    }

    /**
     * Assert new selections pass when the session member can access the file.
     *
     * @return void
     */
    public function testValidateReturnsSelectedValueWhenNewSelectionIsAccessibleToSessionMember()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
        ]);
        $member = (object) ['member_id' => 42];
        $file = new FileFtFileModelStub(true);

        $this->setSessionMember($member);
        $this->setFileModel($file);

        $this->assertSame([
            'value' => '{filedir_1}banner.png',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame([$member], $file->memberChecks);
    }

    /**
     * Assert unchanged existing entry values skip permission rechecks.
     *
     * @return void
     */
    public function testValidateSkipsPermissionCheckWhenExistingEntryValueIsUnchanged()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
        ], 19, 'feature_image');
        $file = new FileFtFileModelStub(false);

        $this->setFileModel($file);
        $this->setChannelEntry(19, (object) [
            'feature_image' => '{filedir_1}banner.png',
        ]);

        $this->assertSame([
            'value' => '{filedir_1}banner.png',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame([], $file->memberChecks);
        $this->assertSame([
            [
                'model' => 'ChannelEntry',
                'id' => 19,
            ],
        ], $this->modelService->queries);
    }

    /**
     * Assert changed existing entry values re-check permissions.
     *
     * @return void
     */
    public function testValidateChecksPermissionsWhenExistingEntryValueChanges()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
        ], 19, 'feature_image');
        $member = (object) ['member_id' => 77];
        $file = new FileFtFileModelStub(false);

        $this->setSessionMember($member);
        $this->setFileModel($file);
        $this->setChannelEntry(19, (object) [
            'feature_image' => '{filedir_2}previous.png',
        ]);

        $this->assertSame([
            'value' => '',
            'error' => 'directory_no_access',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame([$member], $file->memberChecks);
    }

    /**
     * Assert unchanged grid row values skip permission rechecks.
     *
     * @return void
     */
    public function testValidateSkipsPermissionCheckWhenExistingGridRowValueIsUnchanged()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
            'grid_row_id' => 7,
            'grid_field_id' => 33,
            'grid_content_type' => 'channel',
            'col_id' => 5,
        ], 19);
        $file = new FileFtFileModelStub(false);
        $gridModel = $this->setGridRows([
            19 => [
                7 => [
                    'row_id' => 7,
                    'entry_id' => 19,
                    'col_id_5' => '{filedir_1}banner.png',
                    'col_id_6' => 'unchanged unrelated column',
                ],
            ],
        ]);

        $this->setFileModel($file);

        $this->assertSame([
            'value' => '{filedir_1}banner.png',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame([], $file->memberChecks);
        $this->assertSame(['grid_model'], $this->loadRecorder->models);
        $this->assertSame(0, $gridModel->calls[0]['fluid_field_data_id']);
    }

    /**
     * Assert changed existing grid row cells re-check permissions.
     *
     * @return void
     */
    public function testValidateChecksPermissionsWhenExistingGridRowCellValueChanges()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
            'grid_row_id' => 7,
            'grid_field_id' => 33,
            'grid_content_type' => 'channel',
            'col_id' => 5,
        ], 19);
        $member = (object) ['member_id' => 77];
        $file = new FileFtFileModelStub(false);

        $this->setSessionMember($member);
        $this->setFileModel($file);
        $this->setGridRows([
            19 => [
                7 => [
                    'row_id' => 7,
                    'entry_id' => 19,
                    'col_id_5' => '{filedir_2}previous.png',
                    'col_id_6' => 'unchanged unrelated column',
                ],
            ],
        ]);

        $this->assertSame([
            'value' => '',
            'error' => 'directory_no_access',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame([$member], $file->memberChecks);
    }

    /**
     * Assert grid rows without a stable row ID still enforce permissions.
     *
     * @return void
     */
    public function testValidateChecksPermissionsForGridRowsWithoutStableRowId()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
            'grid_row_name' => 'new_row_1',
            'grid_field_id' => 33,
            'grid_content_type' => 'channel',
            'fluid_field_data_id' => 12,
        ], 19);
        $file = new FileFtFileModelStub(true);
        $gridModel = $this->setGridRows([
            19 => [],
        ]);

        $this->setFileModel($file);

        $this->assertSame([
            'value' => '',
            'error' => 'directory_no_access',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame(12, $gridModel->calls[0]['fluid_field_data_id']);
    }

    /**
     * Assert the channel form fallback can recover a logged-out member.
     *
     * @return void
     */
    public function testValidateLoadsLoggedOutChannelFormMemberWhenSessionHasNoMember()
    {
        $fieldtype = $this->makeFieldtype([
            'field_required' => 'y',
        ]);
        $member = (object) ['member_id' => 88];
        $file = new FileFtFileModelStub(true);
        $channelFormLib = $this->enableChannelFormFallback(88);

        $this->setFileModel($file);
        $this->setMemberModel(88, $member);

        $this->assertSame([
            'value' => '{filedir_1}banner.png',
        ], $fieldtype->validate('{filedir_1}banner.png'));
        $this->assertSame(1, $channelFormLib->fetchCalls);
        $this->assertSame([
            PATH_ADDONS . 'channel',
            'remove:' . PATH_ADDONS . 'channel',
        ], $this->loadRecorder->packagePaths);
        $this->assertSame([
            'file_field',
            'channel_form/channel_form_lib',
        ], $this->loadRecorder->libraries);
        $this->assertSame([$member], $file->memberChecks);
    }
}
