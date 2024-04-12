<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Formatter\Formats;

use ExpressionEngine\Service\Formatter\Formatter;

/**
 * Formatter\Text
 */
class Text extends Formatter
{
    /**
     * @var boolean Whether multibyte string methods are available
     */
    protected $multibyte;

    public function __construct($content, $lang, $session, $config, $options)
    {
        ee()->load->helper('multibyte');

        $this->multibyte = extension_loaded('mbstring');

        // this is text formatter, make sure content is string
        $content = strval($content);

        parent::__construct($content, $lang, $session, $config, $options);
    }

    /**
     * Converts accented / multi-byte characters, e.g. ü, é, ß to ASCII transliterations
     * Uses foreign_chars.php config, either the default or user override, as a map
     *
     * @return self $this
     */
    public function accentsToAscii()
    {
        $accent_map = $this->getConfig('foreign_chars');

        if (empty($accent_map)) {
            return $this;
        }

        $chars = preg_split('//u', $this->content, 0, PREG_SPLIT_NO_EMPTY);

        $this->content = '';
        foreach ($chars as $index => $char) {
            if ($this->multibyte) {
                $ord = mb_ord($char);
            } else {
                if (function_exists('utf8_decode')) {
                    $decoded = utf8_decode($char);
                    if ($decoded != '?') {
                        $char = $decoded;
                    }
                }
                $ord = ord($char);
            }

            if (isset($accent_map[$ord])) {
                $this->content .= $accent_map[$ord];
            } elseif ($ord !== false) { //make sure char is valid character
                $this->content .= $char;
            }
        }

        return $this;
    }

    /**
     * Escapes a string for use in an HTML attribute
     *
     * @param bool $double_encode Whether to double encode existing HTML entities
     * @return self $this
     */
    public function attributeEscape($double_encode = false)
    {
        $this->content = htmlspecialchars($this->content, ENT_QUOTES, 'UTF-8', $double_encode);

        return $this;
    }

    /**
     * Makes content safe to use in an HTML attribute. In addition to escaping like attributeEscape(),
     * it allows for character limiting, and unicode punctuation—handy for meta tags where entities may not be parsed.
     *
     * @param  array  $options Options: (bool) double_encode, (string) end_char, (int) limit, (bool) unicode_punctuation
     * @return self $this
     */
    public function attributeSafe($options = [])
    {
        $options = [
            'double_encode' => (isset($options['double_encode'])) ? get_bool_from_string($options['double_encode']) : false,
            'end_char' => (isset($options['end_char'])) ? $options['end_char'] : '&#8230;',
            'limit' => (isset($options['limit'])) ? (int) $options['limit'] : false,
            'unicode_punctuation' => (isset($options['unicode_punctuation'])) ? get_bool_from_string($options['unicode_punctuation']) : true,
        ];

        // syntax highlighted code will be one long "word" and not summarizable
        if (strpos($this->content, '<div class="codeblock">') !== false) {
            $this->content = preg_replace('|<div class="codeblock">.*?</div>|is', '', $this->content);
        }

        if ($options['unicode_punctuation']) {
            $punctuation = [
                '&#8217;' => '’', // right single curly
                '&#8216;' => '‘', // left single curly
                '&#8221;' => '”', // right double curly
                '&#8220;' => '“', // left double curly
                '&#8212;' => '—', // em-dash
                '&#8230;' => '…', // ellipses
                '&nbsp;' => ' '
            ];

            $this->content = str_replace(array_keys($punctuation), array_values($punctuation), $this->content);

            // flip end_char too if set to the default
            $options['end_char'] = (isset($punctuation[$options['end_char']])) ? $punctuation[$options['end_char']] : $options['end_char'];
        }

        $this->content = strip_tags($this->content);
        $this->attributeEscape($options['double_encode']);

        if (is_numeric($options['limit'])) {
            $this->limitChars(['characters' => $options['limit'], 'end_char' => $options['end_char']]);

            // keep whole words only
            while (strlen($this->content) > $options['limit']) {
                $words = explode(' ', $this->content);
                array_pop($words);
                $this->content = implode(' ', $words) . $options['end_char'];
            }
        }

        return $this;
    }

    /**
     * Censor naughty words, respects application preferences
     *
     * @return self $this
     */
    public function censor()
    {
        $censored = $this->session->cache(__CLASS__, 'censored_words');

        // setup censored words regex
        if (! is_array($censored)) {
            $censored = $this->getConfig('censored_words');

            if (empty($censored)) {
                $this->session->set_cache(__CLASS__, 'censored_words', []);

                return $this;
            }

            $censored = preg_split('/[\n|\|]/', $censored, 0, PREG_SPLIT_NO_EMPTY);

            foreach ($censored as $key => $bad) {
                $length = strlen($bad);
                $bad = '/\b(' . preg_quote($bad, '/') . ')\b/ui';

                // wildcards
                $censored[$key] = str_replace('\*', '(\w*)', $bad);
            }

            $this->session->set_cache(__CLASS__, 'censored_words', $censored);
        }

        $replace = $this->getConfig('censor_replacement');

        foreach ($censored as $bad) {
            if ($replace) {
                $this->content = preg_replace($bad, $replace, $this->content);
            } else {
                $this->content = preg_replace_callback(
                    $bad,
                    function ($matches) {
                        return str_repeat('#', strlen($matches[0]));
                    },
                    $this->content
                );
            }
        }

        return $this;
    }

    /**
     * Converts all applicable characters to HTML entities
     *
     * @return self $this
     */
    public function convertToEntities()
    {
        $this->content = htmlentities($this->content, ENT_QUOTES, 'UTF-8');

        return $this;
    }

    /**
     * Decrypt the text
     *
     * @param  array  $options Options: (string) key, (bool) encode
     * @return self $this
     */
    public function decrypt($options = [])
    {
        $key = (isset($options['key'])) ? $options['key'] : null;

        if (isset($options['encode']) && get_bool_from_string($options['encode']) === false) {
            $this->content = ee('Encrypt', $key)->decrypt($this->content);
        } else {
            $this->content = ee('Encrypt', $key)->decode($this->content);
        }

        return $this;
    }

    /**
     * Replace Emoji shorthand with HTML entities
     *
     * @return self $this
     */
    public function emojiShorthand()
    {
        static $emoji_map;
        static $shorthand_regex;
        static $usingLegacyEmojiConfig = false;

        // save some cycles if we know we can't possibly find a match
        if (substr_count($this->content, ':') < 2) {
            return $this;
        }

        // setup our regex and our map just once, pretty intensive
        if (empty($shorthand_regex)) {
            //legacy support for old emoji config files
            if (file_exists(SYSPATH . 'user/config/emoji.php')) {
                $usingLegacyEmojiConfig = true;
                $emoji_map = ee()->config->loadFile('emoji');
                $short_names = array_keys($emoji_map);

                // preg_quote the short names for our regex, and store the preg_quoted version with each symbol for later use
                $short_names = array_map(
                    function ($item) use ($emoji_map) {
                        return $emoji_map[$item]->preg_quoted_name = preg_quote($item, '/');
                    },
                    $short_names
                );
            } else {
                //the "new" way of getting emoji data
                $emoji_map = ee('Emoji')->emojiMap;
                $short_names = array_keys($emoji_map);
            }
            $short_names = array_keys($emoji_map);

            $shorthand_regex = '/:(' . str_replace('+', '\+', implode('|', $short_names)) . '):/';
        }

        // grab 'em!
        preg_match_all($shorthand_regex, $this->content, $matches, PREG_SET_ORDER);

        if (empty($matches)) {
            return $this;
        }

        // save some cycles if we don't need to worry about code blocks
        $use_regex = (stripos($this->content, '[code') !== false or stripos($this->content, '<code') !== false);

        // array (size=2)
        //   0 => string ':rabbit:' (length=8)
        //   1 => string 'rabbit' (length=6)
        foreach ($matches as $match) {
            if (isset($emoji_map[$match[1]])) {
                if ($usingLegacyEmojiConfig) {
                    if ($use_regex) {
                        // This regex says "match our emoji shorthand that are not followed by ...[/code] without a [code] inbetween"
                        // essentially ignoring all matches inside of [code][/code]/<code></code> blocks
                        $this->content = preg_replace(
                            "/(:{$emoji_map[$match[1]]->preg_quoted_name}:)(?!(.(?![<\[]code))*[<\[]\/code[>\]])/is",
                            $emoji_map[$match[1]]->html_entity,
                            $this->content
                        );
                    } else {
                        $this->content = str_replace($match[0], $emoji_map[$match[1]]->html_entity, $this->content);
                    }
                } else {
                    //the "new" way
                    if ($use_regex) {
                        $this->content = preg_replace(
                            "/({$match[0]})(?!(.(?![<\[]code))*[<\[]\/code[>\]])/is",
                            $emoji_map[$match[1]],
                            $this->content
                        );
                    } else {
                        $this->content = str_replace($match[0], $emoji_map[$match[1]], $this->content);
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Encode ExpressionEngine Tags. By default encodes all curly braces so variables are also protected.
     *
     * @param  array  $options Options: (bool) encode_vars
     * @return self $this
     */
    public function encodeEETags($options = [])
    {
        $encode_vars = (isset($options['encode_vars'])) ? $options['encode_vars'] : true;

        if ($this->content != '' && strpos($this->content, '{') !== false) {
            if ($encode_vars === true) {
                $this->content = str_replace(array('{', '}'), array('&#123;', '&#125;'), $this->content);
            } else {
                $this->content = preg_replace("/\{(\/){0,1}exp:(.+?)\}/", "&#123;\\1exp:\\2&#125;", $this->content);
                $this->content = str_replace(array('{exp:', '{/exp'), array('&#123;exp:', '&#123;\exp'), $this->content);
                $this->content = preg_replace("/\{embed=(.+?)\}/", "&#123;embed=\\1&#125;", $this->content);
                $this->content = preg_replace("/\{path:(.+?)\}/", "&#123;path:\\1&#125;", $this->content);
                $this->content = preg_replace("/\{redirect=(.+?)\}/", "&#123;redirect=\\1&#125;", $this->content);
                $this->content = str_replace(array('{if', '{/if'), array('&#123;if', '&#123;/if'), $this->content);
                $this->content = preg_replace("/\{(\/)?layout:(.+?)\}/", "&#123;\\1layout:\\2&#125;", $this->content);
            }
        }

        return $this;
    }

    /**
     * Encrypt the text
     *
     * @param  array  $options Options: (string) key, (bool) encode
     * @return self $this
     */
    public function encrypt($options = [])
    {
        $key = (isset($options['key'])) ? $options['key'] : null;

        if (isset($options['encode']) && get_bool_from_string($options['encode']) === false) {
            $this->content = ee('Encrypt', $key)->encrypt($this->content);
        } else {
            $this->content = ee('Encrypt', $key)->encode($this->content);
        }

        return $this;
    }

    /**
     * Preps the content for use in a form field
     *
     * @return self $this
     */
    public function formPrep()
    {
        ee()->load->helper('form');
        $this->content = form_prep($this->content);

        return $this;
    }

    /**
     * JSON encoding
     *
     * @param  array  $options Options: (bool) double_encode, (bool) enclose_with_quotes, (string) options, pipe-delimited list of PHP JSON bitmask constants
     * @return self $this
     */
    public function json($options = [])
    {
        $double_encode = (isset($options['double_encode'])) ? get_bool_from_string($options['double_encode']) : true;
        $enclose_with_quotes = (isset($options['enclose_with_quotes'])) ? get_bool_from_string($options['enclose_with_quotes']) : true;

        $json_options = 0;
        if (isset($options['options'])) {
            foreach (preg_split('/[\s\|]/', $options['options'], 0, PREG_SPLIT_NO_EMPTY) as $param) {
                $json_options += constant($param);
            }
        }

        $this->attributeEscape($double_encode);
        $this->content = json_encode($this->content, $json_options);

        if (! $enclose_with_quotes) {
            $this->content = trim($this->content, '"');
        }

        return $this;
    }

    /**
     * Get the length of the string
     *
     * @return self $this
     */
    public function length()
    {
        $this->content = ee_mb_strlen($this->content, 'utf8');

        return $this;
    }

    /**
     * Limit to X characters, with an optional end character. Strips HTML.
     *
     * @param  array  $options Options: (int) characters, (string) end_char, (boolean) preserve_words
     * @return self $this
     */
    public function limitChars($options = [])
    {
        $limit = (isset($options['characters'])) ? (int) $options['characters'] : 500;
        $end_char = (isset($options['end_char'])) ? $options['end_char'] : '&#8230;';
        $preserve_words = (isset($options['preserve_words'])) ? get_bool_from_string($options['preserve_words']) : false;
        $this->content = strip_tags($this->content);

        $length = ee_mb_strlen($this->content, 'utf8');

        if ($length < $limit) {
            return $this;
        }

        $this->content = preg_replace(
            "/\s+/",
            ' ',
            str_replace(
                array("\r\n", "\r", "\n"),
                ' ',
                $this->content
            )
        );

        $length = ee_mb_strlen($this->content, 'utf8');

        if ($length <= $limit) {
            return $this;
        }

        if ($preserve_words) {
            // wordwrap() currently doesn't account for multi-byte, so those
            // characters may affect where the wrap occurs
            $this->content = wordwrap($this->content, $limit, "\n", true);

            $cut = ee_mb_substr($this->content, 0, ee_mb_strpos($this->content, "\n"), 'utf8');
        } else {
            $cut = ee_mb_substr($this->content, 0, $limit, 'utf8');
        }

        $this->content = (strlen($cut) == strlen($this->content)) ? $cut : $cut . $end_char;

        return $this;
    }

    /**
     * String replacement
     *
     * @param  array  $options Options: (string) find, (string) (replace), (bool) regex, (bool) case_sensitive
     * @return object $this
     */
    public function replace($options = [])
    {
        $find = (isset($options['find'])) ? $options['find'] : '';
        $replace = (isset($options['replace'])) ? $options['replace'] : '';

        // anything to do?
        if (! $find) {
            return $this;
        }

        if (! isset($options['regex']) or get_bool_from_string($options['regex']) !== true) {
            if (isset($options['case_sensitive']) && get_bool_from_string($options['case_sensitive']) === false) {
                $this->content = str_ireplace($find, $replace, $this->content);
            } else {
                $this->content = str_replace($find, $replace, $this->content);
            }

            return $this;
        }

        $find = $this->removeEvalModifier($find);
        $valid = @preg_match($find, '');

        // valid regex only, unless DEBUG is enabled
        if ($valid !== false or DEBUG) {
            $this->content = preg_replace($find, $replace, $this->content);
        }

        return $this;
    }

    /**
     * Remove deprecated and potentially unsafe eval (`e`) modifier from regex
     * patterns, assumes the pattern is already properly delimited
     *
     * @param string $pattern Regex pattern
     * @return string Regex pattern sans eval modifier
     */
    private function removeEvalModifier($pattern)
    {
        $pattern_parts = explode($pattern[0], trim($pattern));
        $pattern_last = sizeof($pattern_parts) - 1;
        $pattern_parts[$pattern_last] = str_replace('e', '', $pattern_parts[$pattern_last]);

        return implode($pattern[0], $pattern_parts);
    }

    /**
     * Returns a string with whitespace stripped from its beginning and end.
     *
     * @param  array $options Options: (string) characters
     * @return string Regex pattern sans eval modifier
     */
    public function trim($options = [])
    {
        if (empty($this->content)) {
            return $this;
        }

        $this->content = isset($options['characters'])
            ? trim($this->content, $options['characters'])
            : trim($this->content);

        return $this->content;
    }

    /**
     * Normalize URLs so they can be used in HTML
     *
     * @return object $this
     */
    public function url()
    {
        if (empty($this->content)) {
            return $this;
        }

        // strings that contain only a protocol? wipe 'em. Shortest valid URL in the world is 11 chars: http://g.cn
        if (strncasecmp($this->content, 'http', 4) === 0 && strlen($this->content) <= 8) {
            $this->content = '';

            return $this;
        }

        $url = parse_url($this->content);

        if (! $url or ! isset($url['scheme'])) {
            $this->content = 'http://' . $this->content;
        }

        return $this->content;
    }

    /**
     * URL Decode
     *
     * @param  array  $options Options: (bool) plus_encoded_spaces
     * @return object $this
     */
    public function urlDecode($options = [])
    {
        if (isset($options['plus_encoded_spaces']) && get_bool_from_string($options['plus_encoded_spaces'])) {
            $this->content = urldecode($this->content);
        } else {
            $this->content = rawurldecode($this->content);
        }

        return $this;
    }

    /**
     * URL Encode
     *
     * @param  array  $options Options: (bool) plus_encoded_spaces
     * @return object $this
     */
    public function urlEncode($options = [])
    {
        if (isset($options['plus_encoded_spaces']) && get_bool_from_string($options['plus_encoded_spaces'])) {
            $this->content = urlencode($this->content);
        } else {
            $this->content = rawurlencode($this->content);
        }

        return $this;
    }

    /**
     * Make a URL slug from the text
     *
     * @param  array  $options Options: (string) separator, (bool) lowercase, (bool) remove_stopwords
     * @return self $this
     */
    public function urlSlug($options = [])
    {
        if (! isset($options['separator'])) {
            $options['separator'] = ($this->getConfig('word_separator') == 'underscore') ? '_' : '-';
        }

        $lowercase = (isset($options['lowercase']) && get_bool_from_string($options['lowercase']) === false) ? false : true;

        $this->accentsToAscii();

        // order here is important
        $replace = [
            // remove numeric entities
            '#&\#\d+?;#i' => '',
            // remove named entities
            '#&\S+?;#i' => '',
            // replace whitespace and forward slashes with the separator
            '#\s+|/+|\|+#i' => $options['separator'],
            // only allow low ascii letters, numbers, dash, dot, underscore, and emoji
            '#[^a-z0-9\-\._' . ee('Emoji')->emojiRegex . ']#iu' => '',
            // no dot-then-separator (in case multiple sentences were passed)
            '#\.' . $options['separator'] . '#i' => $options['separator'],
            // reduce multiple instances of the separator to a single
            '#' . $options['separator'] . '+#i' => $options['separator'],
        ];

        $this->content = strip_tags($this->content);
        $this->content = preg_replace(array_keys($replace), array_values($replace), $this->content);

        // don't allow separators or dots at the beginning or end of the string, and remove slashes if they exist
        $this->content = trim(stripslashes($this->content), '-_.');

        if ($lowercase === true) {
            $this->content = strtolower($this->content);
        }

        if (isset($options['remove_stopwords']) && get_bool_from_string($options['remove_stopwords'])) {
            $stopwords = $this->getConfig('stopwords');

            foreach ($stopwords as $stopword) {
                $this->content = preg_replace("/\b" . preg_quote($stopword, '/') . "\b/iu", "", $this->content);
            }

            // reduce any multiples this left behind, and any end bits
            $this->content = preg_replace('#' . $options['separator'] . '+#i', $options['separator'], $this->content);
            $this->content = trim(stripslashes($this->content), '-_.');
        }

        return $this;
    }
}
// END CLASS

// EOF
