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

class FileFtGetChainableModifiersThatRequireArrayTest extends FileFtTestBase
{
    /**
     * Return the expected ordered modifier list for array-preserving chains.
     *
     * @return array<int, string>
     */
    protected function getExpectedModifiers()
    {
        return [
            'resize',
            'crop',
            'rotate',
            'webp',
            'avif',
            'resize_crop',
            'length',
            'raw_content',
            'attr_safe',
            'limit',
            'form_prep',
            'rot13',
            'encrypt',
            'url_slug',
            'censor',
            'json',
            'replace',
            'url_encode',
            'url_decode',
        ];
    }

    /**
     * Provide representative upstream payloads that must not affect the registry.
     *
     * @return array<string, array{0: mixed}>
     */
    public function chainableModifierInputProvider()
    {
        return [
            'parsed file array' => [[
                'file_id' => 18,
                'title' => 'Manual PDF',
                'url' => 'https://example.com/files/manual.pdf',
            ]],
            'string payload from prior modifier' => ['manual pdf'],
            'null payload' => [null],
        ];
    }

    /**
     * Assert the method returns the full ordered registry when no payload is supplied.
     *
     * @return void
     */
    public function testGetChainableModifiersThatRequireArrayReturnsExpectedOrderedRegistryByDefault()
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame($this->getExpectedModifiers(), $fieldtype->getChainableModifiersThatRequireArray());
    }

    /**
     * Assert representative payloads do not change the modifier registry.
     *
     * @dataProvider chainableModifierInputProvider
     * @param mixed $data
     * @return void
     */
    public function testGetChainableModifiersThatRequireArrayIgnoresRepresentativePayloads($data)
    {
        $fieldtype = $this->makeFieldtype();

        $this->assertSame($this->getExpectedModifiers(), $fieldtype->getChainableModifiersThatRequireArray($data));
    }

    /**
     * Assert callers cannot mutate the returned registry for subsequent calls.
     *
     * @return void
     */
    public function testGetChainableModifiersThatRequireArrayReturnsFreshArrayForEachCall()
    {
        $fieldtype = $this->makeFieldtype();

        $firstResult = $fieldtype->getChainableModifiersThatRequireArray();
        $firstResult[] = 'custom_modifier';

        $this->assertSame($this->getExpectedModifiers(), $fieldtype->getChainableModifiersThatRequireArray());
    }

    /**
     * Assert the method leaves field context and shared collaborators untouched.
     *
     * @return void
     */
    public function testGetChainableModifiersThatRequireArrayDoesNotMutateFieldContextOrTouchCollaborators()
    {
        $fieldtype = $this->makeFieldtype([
            'allowed_directories' => [3, 7],
            'field_content_type' => 'image',
        ], 22, 'hero_asset');
        $expectedSettings = $fieldtype->settings;
        $expectedContentId = $fieldtype->content_id;
        $expectedFieldName = $fieldtype->field_name;
        $libraries = $this->loadRecorder->libraries;
        $models = $this->loadRecorder->models;
        $fileFieldCalls = $this->fileFieldMock->calls;

        $this->assertSame($this->getExpectedModifiers(), $fieldtype->getChainableModifiersThatRequireArray([
            'title' => 'Manual PDF',
        ]));
        $this->assertSame($expectedSettings, $fieldtype->settings);
        $this->assertSame($expectedContentId, $fieldtype->content_id);
        $this->assertSame($expectedFieldName, $fieldtype->field_name);
        $this->assertSame($libraries, $this->loadRecorder->libraries);
        $this->assertSame($models, $this->loadRecorder->models);
        $this->assertSame($fileFieldCalls, $this->fileFieldMock->calls);
    }
}
