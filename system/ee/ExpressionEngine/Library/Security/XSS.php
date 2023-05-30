<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Security;

/**
 * Security XSS
 */
class XSS
{
    protected $_xss_hash = '';

    private $_cache_evil_attributes_regex_string = '';

    /**
     * The replacement-string for not allowed strings.
     *
     * @var string
     */
    private $_replacement = '';

    /* never allowed, string replacement */
    protected $_never_allowed_str = array(
        'document.cookie' => '[removed]',
        'document.write' => '[removed]',
        '.parentNode' => '[removed]',
        '.innerHTML' => '[removed]',
        'window.location' => '[removed]',
        '-moz-binding' => '[removed]',
        '<!--' => '&lt;!--',
        '-->' => '--&gt;',
        '<![CDATA[' => '&lt;![CDATA['
    );

    /* never allowed, regex replacement */
    protected $_never_allowed_regex = array(
        "javascript\s*:" => '[removed]',
        "expression\s*(\(|&\#40;)" => '[removed]', // CSS and IE
        "vbscript\s*:" => '[removed]', // IE, surprise!
        "Redirect\s+302" => '[removed]'
    );

    /* html5 entities we need to manually decode pre PHP 5.4 */
    protected $_html5_entites = array(
        '&Tab;' => '&#x00009;',
        '&NewLine;' => '&#x0000A;',
        '&excl;' => '&#x00021;',
        '&quot;' => '&#x00022;',
        '&QUOT;' => '&#x00022;',
        '&num;' => '&#x00023;',
        '&dollar;' => '&#x00024;',
        '&percnt;' => '&#x00025;',
        '&amp;' => '&#x00026;',
        '&lpar;' => '&#x00028;',
        '&rpar;' => '&#x00029;',
        '&ast;' => '&#x0002A;',
        '&plus;' => '&#x0002B;',
        '&comma;' => '&#x0002C;',
        '&period;' => '&#x0002E;',
        '&sol;' => '&#x0002F;',
        '&colon;' => '&#x0003A;',
        '&semi;' => '&#x0003B;',
        '&lt;' => '&#x0003C;',
        '&gt;' => '&#x0003E;'
    );

    /**
     * https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet#Event_Handlers
     *
     * @var string[]
     */
    private $_evil_attributes_regex = [
        'style',
        'xmlns:xdp',
        'formaction',
        'form',
        'xlink:href',
        'seekSegmentTime',
        'FSCommand',
    ];

    /**
     * List of never allowed strings, afterwards.
     *
     * @var string[]
     */
    private $_never_allowed_on_events_afterwards = [
        'onAbort',
        'onActivate',
        'onAttribute',
        'onAfterPrint',
        'onAfterScriptExecute',
        'onAfterUpdate',
        'onAnimationCancel',
        'onAnimationEnd',
        'onAnimationIteration',
        'onAnimationStart',
        'onAriaRequest',
        'onAutoComplete',
        'onAutoCompleteError',
        'onAuxClick',
        'onBeforeActivate',
        'onBeforeCopy',
        'onBeforeCut',
        'onBeforeDeactivate',
        'onBeforeEditFocus',
        'onBeforePaste',
        'onBeforePrint',
        'onBeforeScriptExecute',
        'onBeforeUnload',
        'onBeforeUpdate',
        'onBegin',
        'onBlur',
        'onBounce',
        'onCancel',
        'onCanPlay',
        'onCanPlayThrough',
        'onCellChange',
        'onChange',
        'onClick',
        'onClose',
        'onCommand',
        'onCompassNeedsCalibration',
        'onContextMenu',
        'onControlSelect',
        'onCopy',
        'onCueChange',
        'onCut',
        'onDataAvailable',
        'onDataSetChanged',
        'onDataSetComplete',
        'onDblClick',
        'onDeactivate',
        'onDeviceLight',
        'onDeviceMotion',
        'onDeviceOrientation',
        'onDeviceProximity',
        'onDrag',
        'onDragDrop',
        'onDragEnd',
        'onDragEnter',
        'onDragLeave',
        'onDragOver',
        'onDragStart',
        'onDrop',
        'onDurationChange',
        'onEmptied',
        'onEnd',
        'onEnded',
        'onError',
        'onErrorUpdate',
        'onExit',
        'onFilterChange',
        'onFinish',
        'onFocus',
        'onFocusIn',
        'onFocusOut',
        'onFormChange',
        'onFormInput',
        'onFullScreenChange',
        'onFullScreenError',
        'onGotPointerCapture',
        'onHashChange',
        'onHelp',
        'onInput',
        'onInvalid',
        'onKeyDown',
        'onKeyPress',
        'onKeyUp',
        'onLanguageChange',
        'onLayoutComplete',
        'onLoad',
        'onLoadedData',
        'onLoadedMetaData',
        'onLoadStart',
        'onLoseCapture',
        'onLostPointerCapture',
        'onMediaComplete',
        'onMediaError',
        'onMessage',
        'onMouseDown',
        'onMouseEnter',
        'onMouseLeave',
        'onMouseMove',
        'onMouseOut',
        'onMouseOver',
        'onMouseUp',
        'onMouseWheel',
        'onMove',
        'onMoveEnd',
        'onMoveStart',
        'onMozFullScreenChange',
        'onMozFullScreenError',
        'onMozPointerLockChange',
        'onMozPointerLockError',
        'onMsContentZoom',
        'onMsFullScreenChange',
        'onMsFullScreenError',
        'onMsGestureChange',
        'onMsGestureDoubleTap',
        'onMsGestureEnd',
        'onMsGestureHold',
        'onMsGestureStart',
        'onMsGestureTap',
        'onMsGotPointerCapture',
        'onMsInertiaStart',
        'onMsLostPointerCapture',
        'onMsManipulationStateChanged',
        'onMsPointerCancel',
        'onMsPointerDown',
        'onMsPointerEnter',
        'onMsPointerLeave',
        'onMsPointerMove',
        'onMsPointerOut',
        'onMsPointerOver',
        'onMsPointerUp',
        'onMsSiteModeJumpListItemRemoved',
        'onMsThumbnailClick',
        'onOffline',
        'onOnline',
        'onOutOfSync',
        'onPage',
        'onPageHide',
        'onPageShow',
        'onPaste',
        'onPause',
        'onPlay',
        'onPlaying',
        'onPointerCancel',
        'onPointerDown',
        'onPointerEnter',
        'onPointerLeave',
        'onPointerLockChange',
        'onPointerLockError',
        'onPointerMove',
        'onPointerOut',
        'onPointerOver',
        'onPointerUp',
        'onPopState',
        'onProgress',
        'onPropertyChange',
        'onqt_error',
        'onRateChange',
        'onReadyStateChange',
        'onReceived',
        'onRepeat',
        'onReset',
        'onResize',
        'onResizeEnd',
        'onResizeStart',
        'onResume',
        'onReverse',
        'onRowDelete',
        'onRowEnter',
        'onRowExit',
        'onRowInserted',
        'onRowsDelete',
        'onRowsEnter',
        'onRowsExit',
        'onRowsInserted',
        'onScroll',
        'onSearch',
        'onSeek',
        'onSeeked',
        'onSeeking',
        'onSelect',
        'onSelectionChange',
        'onSelectStart',
        'onStalled',
        'onStorage',
        'onStorageCommit',
        'onStart',
        'onStop',
        'onShow',
        'onSyncRestored',
        'onSubmit',
        'onSuspend',
        'onSynchRestored',
        'onTimeError',
        'onTimeUpdate',
        'onTimer',
        'onTrackChange',
        'onTransitionEnd',
        'onToggle',
        'onTouchCancel',
        'onTouchEnd',
        'onTouchLeave',
        'onTouchMove',
        'onTouchStart',
        'onTransitionCancel',
        'onTransitionEnd',
        'onUnload',
        'onURLFlip',
        'onUserProximity',
        'onVolumeChange',
        'onWaiting',
        'onWebKitAnimationEnd',
        'onWebKitAnimationIteration',
        'onWebKitAnimationStart',
        'onWebKitFullScreenChange',
        'onWebKitFullScreenError',
        'onWebKitTransitionEnd',
        'onWheel',
    ];

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This function does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything past
     * the filter.
     *
     * Note: This function should only be used to deal with data
     * upon submission.  It's not something that should
     * be used for general runtime processing.
     *
     * This function was based in part on some code and ideas I
     * got from Bitflux: http://wiki.flux-cms.org/display/BLOG/XSS+Prevention
     *
     * To help develop this script I used this great list of
     * vulnerabilities along with a few other hacks I've
     * harvested from examining vulnerabilities in other programs:
     *
     * @param   string|array[string]    $str    The string to be cleaned or an
     *      array of strings to be cleaned.  This needs to contain enough of the
     *      context to allow it to properly be cleaned, but shouldn't be the whole
     *      final output.  For example, if the data to be cleaned is going to wind
     *      up in the href attribute of a link (<a> tag) then the string needs to
     *      include the full anchor tag.  If attributes of the tag contain dangerous
     *      javascript, the whole attribute will be removed.
     * @param   boolean $is_image   If the data is an image file it requires some special
     *      processing to preserve the meta data.
     * @return  string  The string cleaned of dangerous code.  If an attribute contains dangerous
     *      code it will be removed entirely.  Certain HTML tags will be encoded (html and body
     *      among them).
     */
    public function clean($str, $is_image = false)
    {
        /*
         * Is the string an array?
         *
         */
        if (is_array($str)) {
            foreach ($str as $key => $value) {
                $str[$key] = $this->clean($value);
            }

            return $str;
        }

        /*
         * Remove Invisible Characters
         */
        $str = remove_invisible_characters($str);

        // Strip data URIs
        // Not all browsers conform strictly to RFC2397 so we strip anything
        // that looks close to a data URI inside an attribute
        $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_strip_data_URIs'), $str);

        // Validate Entities in URLs
        $str = $this->_validate_entities($str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Only operate inside tags since those the only ones a browser is going to decode
         *
         */
        $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", [$this, 'decodeUrlCallback'], $str);

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         *
         */

        $str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);

        $str = preg_replace_callback("/<\w+.*?(?=>|<|$)/si", array($this, '_decode_entity'), $str);

        /*
         * Remove Invisible Characters Again!
         */
        $str = remove_invisible_characters($str);

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja	vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on
         * large blocks of data, so we use str_replace.
         */

        if (strpos($str, "\t") !== false) {
            $str = str_replace("\t", ' ', $str);
        }

        /*
         * Capture converted string for later comparison
         */
        $converted_string = $str;

        // Remove Strings that are never allowed
        $str = $this->_do_never_allowed($str);

        /*
         * Makes PHP tags safe
         *
         * Note: XML tags are inadvertently replaced too:
         *
         * <?xml
         *
         * But it doesn't seem to pose a problem.
         */
        if ($is_image === true) {
            // Images have a tendency to have the PHP short opening and
            // closing tags every so often so we skip those and only
            // do the long opening tags.
            $str = preg_replace('/<\?(php)/i', "&lt;?\\1", $str);
        } else {
            $str = str_replace(array('<?', '?' . '>'), array('&lt;?', '?&gt;'), $str);
        }

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         */
        $words = array(
            'javascript', 'expression', 'vbscript', 'script', 'base64',
            'applet', 'alert', 'document', 'write', 'cookie', 'window'
        );

        foreach ($words as $word) {
            $temp = '';

            for ($i = 0, $wordlen = strlen($word); $i < $wordlen; $i++) {
                $temp .= substr($word, $i, 1) . "\s*";
            }

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback('#(' . substr($temp, 0, -3) . ')(\W)#is', array($this, '_compact_exploded_words'), $str);
        }

        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos for PHP5,
         * but it is dog slow compared to these simplified non-capturing
         * preg_match(), especially if the pattern exists in the string
         */
        do {
            $original = $str;

            if (preg_match("/<a/i", $str)) {
                $str = preg_replace_callback("#<a\s+([^>]*?)(>|$)#si", array($this, '_js_link_removal'), $str);
            }

            if (preg_match("/<img/i", $str)) {
                $str = preg_replace_callback("#<img\s+([^>]*?)(\s?/?>|$)#si", array($this, '_js_img_removal'), $str);
            }

            if (preg_match("/<svg/i", $str)) {
                $str = preg_replace_callback("#<svg\s+([^>]*?)(\s?/?>|$)#si", array($this, '_js_img_removal'), $str);
            }

            if (preg_match("/script/i", $str) or preg_match("/xss/i", $str)) {
                $str = preg_replace("#<(/*)(script|xss)(.*?)\>#si", '[removed]', $str);
            }
        } while ($original != $str);

        unset($original);

        // Remove evil attributes such as style, onclick and xmlns
        $str = $this->_remove_evil_attributes($str, $is_image);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         */
        $naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
        $str = preg_replace_callback('#<(/*\s*)(' . $naughty . ')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed.  Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example: eval('some code')
         * Becomes:     eval&#40;'some code'&#41;
         */
        $str = preg_replace('#(console.log|alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', "\\1\\2&#9001;\\3&#9002;", $str);

        // Final clean up
        // This adds a bit of extra precaution in case
        // something got through the above filters
        $str = $this->_do_never_allowed($str);

        /*
         * Images are Handled in a Special Way
         * - Essentially, we want to know that after all of the character
         * conversion is done whether any unwanted, likely XSS, code was found.
         * If not, we return TRUE, as the image is clean.
         * However, if the string post-conversion does not matched the
         * string post-removal of XSS, then it fails, as there was unwanted XSS
         * code found and removed/changed during processing.
         */

        if ($is_image === true) {
            return ($str == $converted_string) ? true : false;
        }

        return $str;
    }

    /**
     * Random Hash for protecting URLs
     *
     * @return  string
     */
    public function xss_hash()
    {
        if ($this->_xss_hash == '') {
            mt_srand();

            $this->_xss_hash = md5(time() + mt_rand(0, 1999999999));
        }

        return $this->_xss_hash;
    }

    /**
     * URL Decode PCRE Callback
     *
     * @param  array $matches preg_match array
     * @return string Text with any URL-encoded characters decoded
     */
    private function decodeUrlCallback($match)
    {
        // rawurldecode() so we don't convert + signs
        $str = rawurldecode($match[0]);

        // decoding could have left some non-UTF-8 encoded strings, which could cause nulled
        // strings in PCRE calls later. If that happened, strip those invalid characters
        if (preg_match('/[^\x00-\x7F]/S', $str) != 0) {
            if (function_exists('iconv')) {
                $str = @iconv('UTF-8', 'UTF-8//IGNORE', $str);
            } else {
                // backup for environments without iconv, ENT_SUBSTITUTE since PHP 5.4 subs invalid characters with U+FFFD
                $str = htmlspecialchars_decode(htmlspecialchars($str, ENT_SUBSTITUTE, 'UTF-8'));
                $str = str_replace('�', '', $str);
            }
        }

        return $str;
    }

    /**
     * HTML Entities Decode
     *
     * A replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly. html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @link        http://php.net/html-entity-decode
     *
     * @param        string        $str                Input
     * @param        string        $charset        Character set
     * @return        string
     */
    public function entity_decode($str, $charset = null)
    {
        if (strpos($str, '&') === false) {
            return $str;
        }

        if (empty($charset)) {
            $charset = config_item('charset');
        }

        do {
            $matches = $matches1 = 0;

            $str = preg_replace('~(&#x0*[0-9a-f]{2,5});?~iS', '$1;', $str, -1, $matches);
            $str = preg_replace('~(&#\d{2,4});?~S', '$1;', $str, -1, $matches1);

            // ENT_HTML5 is PHP 5.4+ only
            if (! defined('ENT_HTML5')) {
                $str = str_replace(
                    array_keys($this->_html5_entites),
                    array_values($this->_html5_entites),
                    $str
                );
                $str = html_entity_decode($str, ENT_COMPAT | ENT_QUOTES, $charset);
            } else {
                $str = html_entity_decode($str, ENT_COMPAT | ENT_QUOTES | ENT_HTML5, $charset);
            }
        } while ($matches or $matches1);

        return $str;
    }

    /**
     * Compact Exploded Words
     *
     * Callback function for xss_clean() to remove whitespace from
     * things like j a v a s c r i p t
     *
     * @param   type
     * @return  type
     */
    protected function _compact_exploded_words($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]) . $matches[2];
    }

    /**
     * Remove Evil HTML Attributes (like evenhandlers and style)
     *
     * It removes the evil attribute and either:
     *  - Everything up until a space
     *      For example, everything between the pipes:
     *      <a |style=document.write('hello');alert('world');| class=link>
     *  - Everything inside the quotes
     *      For example, everything between the pipes:
     *      <a |style="document.write('hello'); alert('world');"| class="link">
     *
     * @param string $str The string to check
     * @param boolean $is_image TRUE if this is an image
     * @return string The string with the evil attributes removed
     */
    protected function _remove_evil_attributes($str, $is_image)
    {
        // All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
        $evil_attributes = array('on\w{2,}', 'style', 'xmlns', 'formaction');

        if ($is_image === true) {
            /*
             * Adobe Photoshop puts XML metadata into JFIF images,
             * including namespacing, so we have to allow this for images.
             */
            unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
            unset($this->_evil_attributes_regex[array_search('xmlns:xdp', $this->_evil_attributes_regex)]);
        }

        // replace style-attribute, first (if needed)
        if (
            \stripos($str, 'style') !== false
            &&
            \in_array('style', $this->_evil_attributes_regex, true)
        ) {
            do {
                $count = $temp_count = 0;

                $str = (string) \preg_replace(
                    '/(<[^>]+)(?<!\p{L})(style\s*=\s*"(?:[^"]*?)"|style\s*=\s*\'(?:[^\']*?)\')/iu',
                    '$1' . '[removed]',
                    $str,
                    -1,
                    $temp_count
                );
                $count += $temp_count;
            } while ($count);
        }

        do {
            $count = 0;
            $attribs = array();

            // find occurrences of illegal attribute strings without quotes
            preg_match_all('/(\W' . implode('|', $evil_attributes) . ')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);
            foreach ($matches as $attr) {
                $attribs[] = trim(preg_quote($attr[0], '/'));
            }

            // find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
            preg_match_all('/(\W' . implode('|', $evil_attributes) . ')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);

            foreach ($matches as $attr) {
                $attribs[] = trim(preg_quote($attr[0], '/'));
            }

            // replace illegal attribute strings that are inside an html tag
            if (count($attribs) > 0) {
                $str = preg_replace("/<(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(" . implode('|', $attribs) . ")(.*?)([\s><]*)([><]*)/i", '<$1 $3$5$6$7', $str, -1, $count);
            }
        } while ($count);

        if (!$this->_cache_evil_attributes_regex_string) {
            $this->_cache_evil_attributes_regex_string = \implode('|', $this->_evil_attributes_regex);
            $this->_cache_evil_attributes_regex_string .= '|' . \implode('\w*|', $this->_never_allowed_on_events_afterwards);
        }

        do {
            $count = $temp_count = 0;

            // find occurrences of illegal attribute strings with and without quotes (" and ' are octal quotes)
            $regex = '/(.*)((?:<[^>]+)(?<!\p{L}))(?:' . $this->_cache_evil_attributes_regex_string . ')(?:\s*=\s*)(?:\'(?:.*?)\'|"(?:.*?)")(.*)/ius';
            $strTmp = \preg_replace(
                $regex,
                '$1$2' . $this->_replacement . '$3$4',
                $str,
                -1,
                $temp_count
            );
            if ($strTmp === null) {
                $regex = '/(?:' . $this->_cache_evil_attributes_regex_string . ')(?:\s*=\s*)(?:\'(?:.*?)\'|"(?:.*?)")/ius';
                $strTmp = \preg_replace(
                    $regex,
                    $this->_replacement,
                    $str,
                    -1,
                    $temp_count
                );
            }
            $str = (string) $strTmp;
            $count += $temp_count;

            $regex =  '/(.*?)(<[^>]+)(?<!\p{L})(?:' . $this->_cache_evil_attributes_regex_string . ')\s*=\s*(?:[^\s>]*)/ius';
            $strTmp = \preg_replace(
                $regex,
                '$1$2' . $this->_replacement . '$3',
                $str,
                -1,
                $temp_count
            );
            if ($strTmp === null) {
                $regex =  '/(?<!\p{L})(?:' . $this->_cache_evil_attributes_regex_string . ')\s*=\s*(?:[^\s>]*)(.*?)/ius';
                $strTmp = \preg_replace(
                    $regex,
                    '$1$2' . $this->_replacement . '$3',
                    $str,
                    -1,
                    $temp_count
                );
            }
            $str = (string)$strTmp;
            $count += $temp_count;
        } while ($count);

        return (string) $str;
    }

    /**
     * Sanitize Naughty HTML
     *
     * Callback function for xss_clean() to remove naughty HTML elements
     *
     * @param   array
     * @return  string
     */
    protected function _sanitize_naughty_html($matches)
    {
        // encode opening brace
        $str = '&lt;' . $matches[1] . $matches[2] . $matches[3];

        // encode captured opening or closing brace to prevent recursive vectors
        $str .= str_replace(
            array('>', '<'),
            array('&gt;', '&lt;'),
            $matches[4]
        );

        return $str;
    }

    /**
     * JS Link Removal
     *
     * Callback function for xss_clean() to sanitize links
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings
     *
     * @param   array
     * @return  string
     */
    protected function _js_link_removal($match)
    {
        $attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]));

        return str_replace($match[1], preg_replace("#href=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si", "", $attributes), $match[0]);
    }

    /**
     * JS Image Removal
     *
     * Callback function for xss_clean() to sanitize image tags
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings
     *
     * @param   array
     * @return  string
     */
    protected function _js_img_removal($match)
    {
        $attributes = $this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]));

        return str_replace($match[1], preg_replace("#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si", "", $attributes), $match[0]);
    }

    /**
     * Attribute Conversion
     *
     * Used as a callback for XSS Clean
     *
     * @param   array
     * @return  string
     */
    protected function _convert_attribute($match)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety
     *
     * @param   string
     * @return  string
     */
    protected function _filter_attributes($str)
    {
        $out = '';

        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
            foreach ($matches[0] as $match) {
                $out .= preg_replace("#/\*.*?\*/#s", '', $match);
            }
        }

        return $out;
    }

    /**
     * HTML Entity Decode Callback
     *
     * Used as a callback for XSS Clean
     *
     * @param   array
     * @return  string
     */
    protected function _decode_entity($match)
    {
        return $this->entity_decode($match[0], strtoupper(config_item('charset')));
    }

    /**
     * Validate URL entities
     *
     * Called by xss_clean()
     *
     * @param   string
     * @return  string
     */
    protected function _validate_entities($str)
    {
        /*
         * Protect GET variables in URLs
         */

        // 901119URL5918AMP18930PROTECT8198

        $str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_hash() . "\\1=\\2", $str);

        /*
         * Validate standard character entities
         *
         * Add a semicolon if missing.  We do this to enable
         * the conversion of entities to ASCII later.
         *
         */
        $str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', "\\1;\\2", $str);

        /*
         * Validate UTF16 two byte encoding (x00)
         *
         * Just as above, adds a semicolon if missing.
         *
         */
        $str = preg_replace('#(&\#x?)([0-9A-F]+);?#i', "\\1\\2;", $str);

        /*
         * Un-Protect GET variables in URLs
         */
        $str = str_replace($this->xss_hash(), '&', $str);

        return $str;
    }

    /**
     * Do Never Allowed
     *
     * A utility function for xss_clean()
     *
     * @param   string
     * @return  string
     */
    protected function _do_never_allowed($str)
    {
        foreach ($this->_never_allowed_str as $key => $val) {
            $str = str_replace($key, $val, $str);
        }

        foreach ($this->_never_allowed_regex as $key => $val) {
            $str = preg_replace("#" . $key . "#si", $val, $str);
        }

        return $str;
    }

    /**
     * Strips all data URIs from a string
     *
     * @param string $match  An array of matches from preg_replace_callback.
     * @access protected
     * @return string  The cleaned string.
     */
    protected function _strip_data_URIs($match)
    {
        $pattern = "/('|\")?(?:\s*)?data:[\w\/\-\.]+?;?(?:\w+;)?\w+?,?.*(?:\\1)?(\s)/i";
        $cleaned = $match[0];
        $cleaned = preg_replace($pattern, '$1$1$2', $cleaned);

        return $cleaned;
    }
}

// EOF
