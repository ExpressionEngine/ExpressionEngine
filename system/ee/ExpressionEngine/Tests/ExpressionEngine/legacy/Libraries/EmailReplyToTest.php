<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EmailReplyToTest extends TestCase
{
    /**
     * Load the email library without initializing ExpressionEngine services.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (! defined('APP_NAME')) {
            define('APP_NAME', 'ExpressionEngine');
        }

        if (! defined('MB_ENABLED')) {
            define('MB_ENABLED', extension_loaded('mbstring'));
        }

        require_once BASEPATH . 'libraries/Email.php';
    }

    /**
     * Encode non-ASCII names while preserving existing ASCII name handling.
     *
     * @dataProvider replyToNameProvider
     * @param string $name Display name supplied by the caller.
     * @param string $expectedName Display name after decoding the header.
     * @param string $protocol Mail transport whose headers are being built.
     * @return void
     */
    public function testReplyToDisplayName(string $name, string $expectedName, string $protocol): void
    {
        $email = (new \ReflectionClass(\EE_Email::class))->newInstanceWithoutConstructor();
        $email->charset = 'UTF-8';
        $email->protocol = $protocol;

        $this->assertSame($email, $email->reply_to('billetterie@example.com', $name));

        $writeHeaders = new \ReflectionMethod(\EE_Email::class, '_write_headers');
        \TestReflectionHelper::makeMethodAccessible($writeHeaders);
        $writeHeaders->invoke($email);

        $headerString = new \ReflectionProperty(\EE_Email::class, '_header_str');
        \TestReflectionHelper::makePropertyAccessible($headerString);
        $header = trim($headerString->getValue($email));
        $nameHeader = substr($header, 0, -strlen(' <billetterie@example.com>'));

        $this->assertSame(0, preg_match('/[\x80-\xFF]/', $header));
        $this->assertStringEndsWith(' <billetterie@example.com>', $header);

        $this->assertSame(
            'Reply-To: ' . $expectedName,
            mb_decode_mimeheader($nameHeader)
        );
    }

    /**
     * Preserve names that the existing MIME encoder cannot safely encode.
     *
     * @dataProvider preservedNameProvider
     * @param string $charset Configured email character set.
     * @param string $name Display name in the configured character set.
     * @return void
     */
    public function testPreservesExistingNameFormat(string $charset, string $name): void
    {
        $email = (new \ReflectionClass(\EE_Email::class))->newInstanceWithoutConstructor();
        $email->charset = $charset;
        $email->reply_to('billetterie@example.com', $name);

        $headers = new \ReflectionProperty(\EE_Email::class, '_headers');
        \TestReflectionHelper::makePropertyAccessible($headers);
        $expectedName = $name[0] === '"' ? $name : '"' . $name . '"';

        $this->assertSame(
            $expectedName . ' <billetterie@example.com>',
            $headers->getValue($email)['Reply-To']
        );
    }

    /**
     * Provide legacy charsets and preformatted phrases accepted before encoding.
     *
     * @return array
     */
    public static function preservedNameProvider(): array
    {
        return [
            'Latin-1 name' => ['ISO-8859-1', "Caf\xE9"],
            'quoted Latin-1 name' => ['ISO-8859-1', "\"Caf\xE9\""],
            'quoted Windows-1252 name' => ['Windows-1252', "\"Ren\xE9\""],
            'quoted Shift-JIS name' => ['SHIFT_JIS', "\"\x83\x5C\x83\x89\""],
            'quoted Big5 name' => ['BIG5', "\"\xA4\x5C\xA9\xFA\""],
            'quoted EUC-JP name' => ['EUC-JP', "\"\xB1\xC4\xB6\xC8\""],
            'long Shift-JIS name' => ['SHIFT_JIS', 'A' . str_repeat("\x83\x5C", 15)],
            'long Big5 name' => ['BIG5', str_repeat("\xA4\x5C\xA9\xFA", 8)],
            'long EUC-JP name' => ['EUC-JP', str_repeat("\xB1\xC4\xB6\xC8\xC1\xEB\xB8\xFD", 4)],
            'quoted prefix' => ['UTF-8', '"Bureau" Été'],
            'separate quoted words' => ['UTF-8', '"Bureau" Été "Paris"'],
            'separate quoted phrases' => ['UTF-8', '"Bureau Été" "Office"'],
            'quoted phrase with comment' => ['UTF-8', '"Bureau Été" (Support)'],
            'malformed UTF-8 name' => ['UTF-8', "Caf\xE9"],
        ];
    }

    /**
     * Provide accented names and existing ASCII display-name formats.
     *
     * @return array
     */
    public static function replyToNameProvider(): array
    {
        $accentedName = 'Cirque de la Pointe-Sèche';
        $longName = str_repeat('Été ', 30) . 'Sèche';

        return [
            'accented name with mail' => [$accentedName, $accentedName, 'mail'],
            'accented name with sendmail' => [$accentedName, $accentedName, 'sendmail'],
            'accented name with SMTP' => [$accentedName, $accentedName, 'smtp'],
            'mixed accents' => ['TEST É é È Sèche', 'TEST É é È Sèche', 'mail'],
            'Japanese name' => ['営業窓口', '営業窓口', 'mail'],
            'emoji in name' => ['Support 📨', 'Support 📨', 'mail'],
            'folded encoded name' => [$longName, $longName, 'mail'],
            'comma before accented text' => ['Smith, Été', '"Smith," Été', 'mail'],
            'semicolon before accented text' => ['Bureau; Été', '"Bureau;" Été', 'mail'],
            'colon before accented text' => ['Bureau: Été', '"Bureau:" Été', 'mail'],
            'quoted accented name' => ['"Bureau Été"', 'Bureau Été', 'mail'],
            'quoted name with comma and accents' => ['"Smith, Été"', '"Smith," Été', 'mail'],
            'quoted name with escaped quotes' => ['"Bureau \\"Été\\""', 'Bureau "Été"', 'mail'],
            'quoted name with escaped backslash' => ['"Bureau \\\\ Été"', '"Bureau \\\\" Été', 'mail'],
            'ASCII name' => ['Ticket Office', '"Ticket Office"', 'mail'],
            'quoted ASCII name' => ['"Ticket Office"', '"Ticket Office"', 'mail'],
            'no name' => ['', '"billetterie@example.com"', 'mail'],
        ];
    }
}
