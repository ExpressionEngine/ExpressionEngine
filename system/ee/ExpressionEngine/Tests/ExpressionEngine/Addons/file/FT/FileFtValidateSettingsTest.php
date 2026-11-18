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

class FileFtValidateSettingsTest extends FileFtTestBase
{
    /**
     * Assert validate_settings() builds the expected validator rules and
     * returns the validator result unchanged.
     *
     * @return void
     */
    public function testValidateSettingsBuildsValidatorAndReturnsValidationResult()
    {
        $expectedResult = (object) ['is_valid' => true];
        $validator = new FileFtValidationValidatorStub($expectedResult);
        $validation = $this->setValidationService($validator);
        $fieldtype = $this->makeFieldtype();
        $settings = [
            'allowed_directories' => [1, 4],
            'field_content_type' => 'image',
        ];

        $result = $fieldtype->validate_settings($settings);

        $this->assertSame($expectedResult, $result);
        $this->assertSame([
            [
                'allowed_directories' => 'required|allowedDirectories',
            ],
        ], $validation->makeCalls);
        $this->assertSame([
            $settings,
        ], $validator->validateCalls);
        $this->assertCount(1, $validator->defineRuleCalls);
        $this->assertSame('allowedDirectories', $validator->defineRuleCalls[0]['name']);
        $this->assertSame([$fieldtype, '_validate_file_settings'], $validator->defineRuleCalls[0]['callback']);
    }

    /**
     * Provide representative callback payloads that should be accepted unchanged.
     *
     * @return array<string, array{0: mixed, 1: mixed, 2: array<string, mixed>, 3: mixed}>
     */
    public function validateFileSettingsProvider()
    {
        return [
            'expected validator payload' => [
                'allowed_directories',
                [1, 4],
                ['field_content_type' => 'image'],
                (object) ['name' => 'allowedDirectories'],
            ],
            'boundary payload' => [
                '',
                null,
                [],
                null,
            ],
        ];
    }

    /**
     * Assert _validate_file_settings() accepts the current callback inputs
     * without invoking additional collaborators or mutating the result.
     *
     * @dataProvider validateFileSettingsProvider
     * @param mixed $key
     * @param mixed $value
     * @param array<string, mixed> $params
     * @param mixed $rule
     * @return void
     */
    public function testValidateFileSettingsAlwaysReturnsTrueAndStaysSideEffectFree($key, $value, array $params, $rule)
    {
        $validator = new FileFtValidationValidatorStub((object) ['is_valid' => false]);
        $validation = $this->setValidationService($validator);
        $fieldtype = $this->makeFieldtype();
        $initialLibraries = $this->loadRecorder->libraries;
        $initialModels = $this->loadRecorder->models;
        $initialPackagePaths = $this->loadRecorder->packagePaths;

        $this->assertTrue($fieldtype->_validate_file_settings($key, $value, $params, $rule));
        $this->assertSame([], $validation->makeCalls);
        $this->assertSame([], $validator->defineRuleCalls);
        $this->assertSame([], $validator->validateCalls);
        $this->assertSame($initialLibraries, $this->loadRecorder->libraries);
        $this->assertSame($initialModels, $this->loadRecorder->models);
        $this->assertSame($initialPackagePaths, $this->loadRecorder->packagePaths);
    }
}
