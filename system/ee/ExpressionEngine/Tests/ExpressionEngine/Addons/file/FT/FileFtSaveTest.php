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

class FileFtSaveTest extends FileFtTestBase
{
    /**
     * Provide representative validated payloads that save() must preserve.
     *
     * @return array<string, array{0: string}>
     */
    public function savePayloadProvider()
    {
        return [
            'selected file tag' => ['{filedir_1}banner.png'],
            'empty optional value' => [''],
            'zero-like directory token' => ['{filedir_0}0'],
        ];
    }

    /**
     * Assert save() preserves the validated field payload verbatim.
     *
     * @dataProvider savePayloadProvider
     * @param string $data
     * @return void
     */
    public function testSaveReturnsValidatedPayloadUnchanged($data)
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame($data, $fieldtype->save($data));
    }

    /**
     * Assert save() does not consult collaborators when persisting field data.
     *
     * @return void
     */
    public function testSaveDoesNotTouchCollaborators()
    {
        $fieldtype = $this->makeFieldtype();
        $libraries = $this->loadRecorder->libraries;
        $models = $this->loadRecorder->models;
        $fileFieldCalls = $this->fileFieldMock->calls;

        $this->assertSame('{filedir_2}hero.jpg', $fieldtype->save('{filedir_2}hero.jpg'));
        $this->assertSame($libraries, $this->loadRecorder->libraries);
        $this->assertSame($models, $this->loadRecorder->models);
        $this->assertSame($fileFieldCalls, $this->fileFieldMock->calls);
    }
}
