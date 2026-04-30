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
}
