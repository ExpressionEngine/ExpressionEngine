<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed.');
}

/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Text Helper
 */

 /**
 * Convert Accented Foreign Characters to ASCII
 *
 * @access	public
 * @param	string	the text string
 * @return	string
 */
if (! function_exists('convert_accented_characters')) {
    function convert_accented_characters($match)
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('4.0.0', "ee('Format')->make('Text', \$str)->accentsToAscii()");

        $foreign_characters = ee()->config->loadFile('foreign_chars');

        /* -------------------------------------
        /*  'foreign_character_conversion_array' hook.
        /*  - Allows you to use your own foreign character conversion array
        /*  - Added 1.6.0
        * 	- Note: in 2.0, you can edit the foreign_chars.php config file as well
        */
        if (isset(ee()->extensions->extensions['foreign_character_conversion_array'])) {
            $foreign_characters = ee()->extensions->call('foreign_character_conversion_array');
        }
        /*
        /* -------------------------------------*/

        if (! isset($foreign_characters)) {
            return $match;
        }

        $ord = ord($match['1']);

        if (isset($foreign_characters[$ord])) {
            return $foreign_characters[$ord];
        } else {
            return $match['1'];
        }
    }
}

/**
 * Word Limiter
 *
 * Limits a string to X number of words.
 *
 * @access	public
 * @param	string
 * @param	integer
 * @param	string	the end character. Usually an ellipsis
 * @return	string
 */
if (! function_exists('word_limiter')) {
    function word_limiter($str, $limit = 100, $end_char = '&#8230;')
    {
        if (trim($str) == '') {
            return $str;
        }

        preg_match('/^\s*+(?:\S++\s*+){1,' . (int) $limit . '}/', $str, $matches);

        if (strlen($str) == strlen($matches[0])) {
            $end_char = '';
        }

        return rtrim($matches[0]) . $end_char;
    }
}

/**
 * Character Limiter
 *
 * Limits the string based on the character count.  Preserves complete words
 * so the character count may not be exactly as specified.
 *
 * @access	public
 * @param	string
 * @param	integer
 * @param	string	the end character. Usually an ellipsis
 * @return	string
 */
if (! function_exists('character_limiter')) {
    function character_limiter($str, $n = 500, $end_char = '&#8230;')
    {
        if (strlen($str) < $n) {
            return $str;
        }

        $str = preg_replace("/\s+/", ' ', str_replace(array("\r\n", "\r", "\n"), ' ', $str));

        if (strlen($str) <= $n) {
            return $str;
        }

        $out = "";
        foreach (explode(' ', trim($str)) as $val) {
            $out .= $val . ' ';

            if (strlen($out) >= $n) {
                $out = trim($out);

                return (strlen($out) == strlen($str)) ? $out : $out . $end_char;
            }
        }
    }
}

/**
 * High ASCII to Entities
 *
 * Converts High ascii text and MS Word special characters to character entities
 *
 * @access	public
 * @param	string
 * @return	string
 */
if (! function_exists('ascii_to_entities')) {
    function ascii_to_entities($str)
    {
        $count = 1;
        $out = '';
        $temp = array();

        for ($i = 0, $s = strlen($str); $i < $s; $i++) {
            $ordinal = ord($str[$i]);

            if ($ordinal < 128) {
                /*
                    If the $temp array has a value but we have moved on, then it seems only
                    fair that we output that entity and restart $temp before continuing. -Paul
                */
                if (count($temp) == 1) {
                    $out .= '&#' . array_shift($temp) . ';';
                    $count = 1;
                }

                $out .= $str[$i];
            } else {
                if (count($temp) == 0) {
                    $count = ($ordinal < 224) ? 2 : 3;
                }

                $temp[] = $ordinal;

                if (count($temp) == $count) {
                    $number = ($count == 3) ? (($temp['0'] % 16) * 4096) + (($temp['1'] % 64) * 64) + ($temp['2'] % 64) : (($temp['0'] % 32) * 64) + ($temp['1'] % 64);

                    $out .= '&#' . $number . ';';
                    $count = 1;
                    $temp = array();
                }
            }
        }

        return $out;
    }
}

/**
 * Entities to ASCII
 *
 * Converts character entities back to ASCII
 *
 * @access	public
 * @param	string
 * @param	bool
 * @return	string
 */
if (! function_exists('entities_to_ascii')) {
    function entities_to_ascii($str, $all = true)
    {
        if (preg_match_all('/\&#(\d+)\;/', $str, $matches)) {
            for ($i = 0, $s = count($matches['0']); $i < $s; $i++) {
                $digits = $matches['1'][$i];

                $out = '';

                if ($digits < 128) {
                    $out .= chr($digits);
                } elseif ($digits < 2048) {
                    $out .= chr(192 + (($digits - ($digits % 64)) / 64));
                    $out .= chr(128 + ($digits % 64));
                } else {
                    $out .= chr(224 + (($digits - ($digits % 4096)) / 4096));
                    $out .= chr(128 + ((($digits % 4096) - ($digits % 64)) / 64));
                    $out .= chr(128 + ($digits % 64));
                }

                $str = str_replace($matches['0'][$i], $out, $str);
            }
        }

        if ($all) {
            $str = str_replace(
                array("&amp;", "&lt;", "&gt;", "&quot;", "&apos;", "&#45;"),
                array("&","<",">","\"", "'", "-"),
                $str
            );
        }

        return $str;
    }
}

/**
 * Word Censoring Function
 *
 * Supply a string and an array of disallowed words and any
 * matched words will be converted to #### or to the replacement
 * word you've submitted.
 *
 * @access	public
 * @param	string	the text string
 * @param	string	the array of censoered words
 * @param	string	the optional replacement value
 * @return	string
 */
if (! function_exists('word_censor')) {
    function word_censor($str, $censored, $replacement = '')
    {
        ee()->load->library('logger');
        ee()->logger->deprecated('4.0.0', "ee('Format')->make('Text', \$str)->censor()");

        if (! is_array($censored)) {
            return $str;
        }

        $str = ' ' . $str . ' ';

        // \w, \b and a few others do not match on a unicode character
        // set for performance reasons. As a result words like über
        // will not match on a word boundary. Instead, we'll assume that
        // a bad word will be bookeneded by any of these characters.
        $delim = '[-_\'\"`(){}<>\[\]|!?@#%&,.:;^~*+=\/ 0-9\n\r\t]';

        foreach ($censored as $badword) {
            if ($replacement != '') {
                $str = preg_replace("/({$delim})(" . str_replace('\*', '\w*?', preg_quote($badword, '/')) . ")({$delim})/i", "\\1{$replacement}\\3", $str);
            } else {
                $str = preg_replace_callback(
                    "/({$delim})(" . str_replace('\*', '\w*?', preg_quote($badword, '/')) . ")({$delim})/i",
                    function ($matches) {
                        return $matches[1] . str_repeat('#', strlen($matches[2])) . $matches[3];
                    },
                    $str
                );
            }
        }

        return trim($str);
    }
}

/**
 * Code Highlighter
 *
 * Colorizes code strings
 *
 * @access	public
 * @param	string	the text string
 * @return	string
 */
if (! function_exists('highlight_code')) {
    function highlight_code($str)
    {
        // The highlight string function encodes and highlights
        // brackets so we need them to start raw
        $str = str_replace(array('&lt;', '&gt;'), array('<', '>'), $str);

        // Replace any existing PHP tags to temporary markers so they don't accidentally
        // break the string out of PHP, and thus, thwart the highlighting.

        $str = str_replace(
            array('<?', '?>', '<%', '%>', '\\', '</script>'),
            array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'),
            $str
        );

        // The highlight_string function requires that the text be surrounded
        // by PHP tags, which we will remove later
        $str = '<?php ' . $str . ' ?>'; // <?

        // All the magic happens here, baby!
        $str = highlight_string($str, true);

        // Prior to PHP 5, the highligh function used icky <font> tags
        // so we'll replace them with <span> tags.

        if (abs(PHP_VERSION) < 5) {
            $str = str_replace(array('<font ', '</font>'), array('<span ', '</span>'), $str);
            $str = preg_replace('#color="(.*?)"#', 'style="color: \\1"', $str);
        }

        // Remove our artificially added PHP, and the syntax highlighting that came with it
        $str = preg_replace('/<span style="color: #([A-Z0-9]+)">&lt;\?php(&nbsp;| )/i', '<span style="color: #$1">', $str);
        $str = preg_replace('/(<span style="color: #[A-Z0-9]+">.*?)\?&gt;<\/span>\n<\/span>\n<\/code>/is', "$1</span>\n</span>\n</code>", $str);
        $str = preg_replace('/<span style="color: #[A-Z0-9]+"\><\/span>/i', '', $str);

        // Replace our markers back to PHP tags.
        $str = str_replace(
            array('phptagopen', 'phptagclose', 'asptagopen', 'asptagclose', 'backslashtmp', 'scriptclose'),
            array('&lt;?', '?&gt;', '&lt;%', '%&gt;', '\\', '&lt;/script&gt;'),
            $str
        );

        return $str;
    }
}

/**
 * Phrase Highlighter
 *
 * Highlights a phrase within a text string
 *
 * @access	public
 * @param	string	the text string
 * @param	string	the phrase you'd like to highlight
 * @param	string	the openging tag to precede the phrase with
 * @param	string	the closing tag to end the phrase with
 * @return	string
 */
if (! function_exists('highlight_phrase')) {
    function highlight_phrase($str, $phrase, $tag_open = '<strong>', $tag_close = '</strong>')
    {
        if ($str == '') {
            return '';
        }

        if ($phrase != '') {
            return preg_replace('/(' . preg_quote($phrase, '/') . ')/i', $tag_open . "\\1" . $tag_close, $str);
        }

        return $str;
    }
}

/**
 * Word Wrap
 *
 * Wraps text at the specified character.  Maintains the integrity of words.
 * Anything placed between {unwrap}{/unwrap} will not be word wrapped, nor
 * will URLs.
 *
 * @access	public
 * @param	string	the text string
 * @param	integer	the number of characters to wrap at
 * @return	string
 */
if (! function_exists('word_wrap')) {
    function word_wrap($str, $charlim = '76')
    {
        // Se the character limit
        if (! is_numeric($charlim)) {
            $charlim = 76;
        }

        // Reduce multiple spaces
        $str = preg_replace("| +|", " ", $str);

        // Standardize newlines
        if (strpos($str, "\r") !== false) {
            $str = str_replace(array("\r\n", "\r"), "\n", $str);
        }

        // If the current word is surrounded by {unwrap} tags we'll
        // strip the entire chunk and replace it with a marker.
        $unwrap = array();
        if (preg_match_all("|(\{unwrap\}.+?\{/unwrap\})|s", $str, $matches)) {
            for ($i = 0; $i < count($matches['0']); $i++) {
                $unwrap[] = $matches['1'][$i];
                $str = str_replace($matches['1'][$i], "{{unwrapped" . $i . "}}", $str);
            }
        }

        // Use PHP's native function to do the initial wordwrap.
        // We set the cut flag to FALSE so that any individual words that are
        // too long get left alone.  In the next step we'll deal with them.
        $str = wordwrap($str, $charlim, "\n", false);

        // Split the string into individual lines of text and cycle through them
        $output = "";
        foreach (explode("\n", $str) as $line) {
            // Is the line within the allowed character count?
            // If so we'll join it to the output and continue
            if (strlen($line) <= $charlim) {
                $output .= $line . "\n";

                continue;
            }

            $temp = '';
            while ((strlen($line)) > $charlim) {
                // If the over-length word is a URL we won't wrap it
                if (preg_match("!\[url.+\]|://|wwww.!", $line)) {
                    break;
                }

                // Trim the word down
                $temp .= substr($line, 0, $charlim - 1);
                $line = substr($line, $charlim - 1);
            }

            // If $temp contains data it means we had to split up an over-length
            // word into smaller chunks so we'll add it back to our current line
            if ($temp != '') {
                $output .= $temp . "\n" . $line;
            } else {
                $output .= $line;
            }

            $output .= "\n";
        }

        // Put our markers back
        if (count($unwrap) > 0) {
            foreach ($unwrap as $key => $val) {
                $output = str_replace("{{unwrapped" . $key . "}}", $val, $output);
            }
        }

        // Remove the unwrap tags
        $output = str_replace(array('{unwrap}', '{/unwrap}'), '', $output);

        return $output;
    }
}

/**
 * Ellipsize String
 *
 * This function will strip tags from a string, split it at its max_length and ellipsize
 *
 * @param	string		string to ellipsize
 * @param	integer		max length of string
 * @param	mixed		int (1|0) or float, .5, .2, etc for position to split
 * @param	string		ellipsis ; Default '...'
 * @return	string		ellipsized string
 */
if (! function_exists('ellipsize')) {
    function ellipsize($str, $max_length, $position = 1, $ellipsis = '&hellip;')
    {
        // Strip tags
        $str = trim(strip_tags($str));

        // Is the string long enough to ellipsize?
        if (strlen($str) <= $max_length) {
            return $str;
        }

        $beg = substr($str, 0, floor($max_length * $position));

        $position = ($position > 1) ? 1 : $position;

        if ($position === 1) {
            $end = substr($str, 0, -($max_length - strlen($beg)));
        } else {
            $end = substr($str, -($max_length - strlen($beg)));
        }

        return $beg . $ellipsis . $end;
    }
}

/**
 * Takes an app version string and formats it for the CP, which entails
 * putting bold tags around the first number and dropping the third
 * digit if it is a zero
 *
 * @param	string	$version	App version string, like 3.0.0
 * @return	string	Formatted app version string, like <b>3</b>.0
 */
if (! function_exists('formatted_version')) {
    function formatted_version($version)
    {
        // Break any suffix off first.
        $raw_version = explode('-', $version);

        $version = explode('.', $raw_version[0]);

        $new_version = preg_replace('/^(\d)\./', '<b>$1</b>.', implode('.', $version));

        if (!empty($raw_version[1])) {
            $new_version .= '-' . '<b>' . $raw_version[1] . '</b>';
        }

        return $new_version;
    }
}

// EOF
