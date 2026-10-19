<?php

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries;

use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class EmailReplyToIconvTest extends TestCase
{
    /**
     * Preserve punctuation and folding when mbstring is unavailable.
     *
     * @dataProvider displayNameProvider
     * @param string $name Display name supplied by the caller.
     * @param string $expectedName Display name after MIME decoding.
     * @return void
     */
    public function testReplyToDisplayName(string $name, string $expectedName): void
    {
        if (! extension_loaded('iconv')) {
            $this->markTestSkipped('The iconv extension is required.');
        }

        if (! defined('APP_NAME')) {
            define('APP_NAME', 'ExpressionEngine');
        }

        define('MB_ENABLED', false);
        require_once BASEPATH . 'libraries/Email.php';

        $email = (new \ReflectionClass(\EE_Email::class))->newInstanceWithoutConstructor();
        $email->charset = 'UTF-8';
        $email->reply_to('billetterie@example.com', $name);

        $headers = new \ReflectionProperty(\EE_Email::class, '_headers');
        \TestReflectionHelper::makePropertyAccessible($headers);
        $header = $headers->getValue($email)['Reply-To'];
        $this->assertStringEndsWith(' <billetterie@example.com>', $header);
        $encodedName = substr($header, 0, -strlen(' <billetterie@example.com>'));

        $this->assertSame($expectedName, iconv_mime_decode($encodedName, ICONV_MIME_DECODE_STRICT, 'UTF-8'));
        $this->assertGreaterThan(0, preg_match_all('/=\?UTF-8\?([BQ])\?([^?]*)\?=/i', $encodedName, $words, PREG_SET_ORDER));

        foreach ($words as $word) {
            $this->assertLessThanOrEqual(75, strlen($word[0]));
            if (strtoupper($word[1]) === 'Q') {
                $this->assertSame(0, preg_match('~[^A-Za-z0-9!*+/=_-]~', $word[2]));
            }
        }
    }

    /**
     * Provide punctuation and long names requiring multiple encoded words.
     *
     * @return array
     */
    public static function displayNameProvider(): array
    {
        $longName = 'Bureau, ' . str_repeat('Support ', 15) . 'Été';
        $longAccentedName = 'Bureau, ' . str_repeat('Été ', 30);

        return [
            'comma' => ['Smith, Été', 'Smith, Été'],
            'semicolon' => ['Bureau; Été', 'Bureau; Été'],
            'colon' => ['Bureau: Été', 'Bureau: Été'],
            'parentheses' => ['Bureau (Support) Été', 'Bureau (Support) Été'],
            'quoted name' => ['"Smith, Été"', 'Smith, Été'],
            'newlines in name' => ["Bureau\r\nÉté", 'BureauÉté'],
            'long ASCII prefix' => [$longName, $longName],
            'long accented name' => [$longAccentedName, $longAccentedName],
        ];
    }
}
