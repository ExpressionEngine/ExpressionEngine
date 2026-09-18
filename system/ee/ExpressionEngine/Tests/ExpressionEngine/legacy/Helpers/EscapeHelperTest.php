<?php

use PHPUnit\Framework\TestCase;

class EscapeHelperTest extends TestCase
{
    /**
     * Encode literal values with a fixed UTF-8 and entity policy.
     *
     * @param string|int|float|bool|null $value The value to render.
     * @param string $expected The expected HTML source.
     * @return void
     * @dataProvider escapeValues
     */
    public function testEscapesLiteralValues($value, string $expected): void
    {
        require_once BASEPATH . 'helpers/string_helper.php';

        $this->assertSame($expected, ee_html_escape($value));
    }

    /**
     * Provide supported values, literal entities, and malformed UTF-8.
     *
     * @return array
     */
    public static function escapeValues(): array
    {
        return [
            'empty' => ['', ''],
            'null' => [null, ''],
            'zero' => [0, '0'],
            'integer' => [42, '42'],
            'float' => [1.5, '1.5'],
            'false' => [false, ''],
            'true' => [true, '1'],
            'ordinary text' => ['Editorial searches', 'Editorial searches'],
            'special characters' => ['&"\'<>', '&amp;&quot;&#039;&lt;&gt;'],
            'named entities' => ['&amp; &quot; &apos; &copy;', '&amp;amp; &amp;quot; &amp;apos; &amp;copy;'],
            'numeric entities' => ['&#38; &#x26;', '&amp;#38; &amp;#x26;'],
            'nested entities' => ['A &amp;amp; B', 'A &amp;amp;amp; B'],
            'unicode' => ['Café 日本語 🌱 ©', 'Café 日本語 🌱 ©'],
            'malformed UTF-8' => ["Hello \xC3( & goodbye", "Hello \u{FFFD}( &amp; goodbye"],
        ];
    }

    /**
     * Escape stringable objects without treating them as trusted HTML.
     *
     * @return void
     */
    public function testEscapesStringableObjects(): void
    {
        require_once BASEPATH . 'helpers/string_helper.php';

        $value = new class {
            /**
             * Return text containing literal markup.
             *
             * @return string
             */
            public function __toString(): string
            {
                return '<em>Editorial &amp; news</em>';
            }

            /**
             * Reject trusted-HTML handling for this stringable value.
             *
             * @return string
             * @throws LogicException
             */
            public function toHtml(): string
            {
                throw new LogicException('HTML bypasses are not supported.');
            }
        };

        $this->assertSame('&lt;em&gt;Editorial &amp;amp; news&lt;/em&gt;', ee_html_escape($value));
    }

    /**
     * Keep literal markup inside text and either style of quoted attribute.
     *
     * @return void
     */
    public function testPreservesTextAndQuotedAttributeValues(): void
    {
        require_once BASEPATH . 'helpers/string_helper.php';

        $value = '<em data-ee-marker="benign">Editor\'s &amp; café</em>';
        $escaped = ee_html_escape($value);
        $document = new DOMDocument();
        $this->assertTrue($document->loadHTML(
            '<meta charset="UTF-8"><p>' . $escaped . '</p><input value="' . $escaped . '">'
                . "<input value='" . $escaped . "'>",
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
        ));

        $paragraph = $document->getElementsByTagName('p')->item(0);
        $this->assertSame($value, $paragraph->textContent);
        $this->assertSame(0, $paragraph->getElementsByTagName('*')->length);
        $inputs = $document->getElementsByTagName('input');
        $this->assertSame(2, $inputs->length);
        foreach ($inputs as $input) {
            $this->assertSame(1, $input->attributes->length);
            $this->assertSame($value, $input->getAttribute('value'));
        }
    }

    /**
     * Load the helper during real CP and frontend bootstrap in a fresh process.
     *
     * @param string $request_type The request mode to initialize.
     * @return void
     * @dataProvider requestTypes
     */
    public function testCoreBootstrapLoadsHelper(string $request_type): void
    {
        $runner = SYSPATH . 'ee/ExpressionEngine/Tests/support/escape_helper_bootstrap.php';
        $process = proc_open(
            escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($runner) . ' ' . escapeshellarg($request_type),
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes
        );
        $this->assertIsResource($process);
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $this->assertSame(0, proc_close($process), $errors);
        $this->assertSame('', $errors);
        $this->assertSame([
            'available_before' => false,
            'available_after' => true,
            'escaped' => '&lt;heading&gt;',
        ], json_decode($output, true));
    }

    /**
     * Exercise both control-panel and frontend request initialization.
     *
     * @return array
     */
    public static function requestTypes(): array
    {
        return ['control panel' => ['CP'], 'frontend' => ['PAGE']];
    }
}
