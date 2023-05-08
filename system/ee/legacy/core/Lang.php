<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

/**
 * Core Language
 */
class EE_Lang
{
    //ISO 639-1
    public $language_codes = [
        'Abkhazian' => 'ab',
        'Afar' => 'aa',
        'Afrikaans' => 'af',
        'Akan' => 'ak',
        'Albanian' => 'sq',
        'Amharic' => 'am',
        'Arabic' => 'ar',
        'Aragonese' => 'an',
        'Armenian' => 'hy',
        'Assamese' => 'as',
        'Avaric' => 'av',
        'Avestan' => 'ae',
        'Aymara' => 'ay',
        'Azerbaijani' => 'az',
        'Bambara' => 'bm',
        'Bashkir' => 'ba',
        'Basque' => 'eu',
        'Belarusian' => 'be',
        'Bengali' => 'bn',
        'Bihari languages' => 'bh',
        'Bislama' => 'bi',
        'Bosnian' => 'bs',
        'Breton' => 'br',
        'Bulgarian' => 'bg',
        'Burmese' => 'my',
        'Catalan' => 'ca',
        'Valencian' => 'ca',
        'Central Khmer' => 'km',
        'Chamorro' => 'ch',
        'Chechen' => 'ce',
        'Chichewa' => 'ny',
        'Chewa' => 'ny',
        'Nyanja' => 'ny',
        'Chinese' => 'zh',
        'Church Slavonic' => 'cu',
        'Old Bulgarian' => 'cu',
        'Old Church Slavonic' => 'cu',
        'Chuvash' => 'cv',
        'Cornish' => 'kw',
        'Corsican' => 'co',
        'Cree' => 'cr',
        'Croatian' => 'hr',
        'Czech' => 'cs',
        'Danish' => 'da',
        'Divehi' => 'dv',
        'Dhivehi' => 'dv',
        'Maldivian' => 'dv',
        'Dutch' => 'nl',
        'Flemish' => 'nl',
        'Dzongkha' => 'dz',
        'English' => 'en',
        'Esperanto' => 'eo',
        'Estonian' => 'et',
        'Ewe' => 'ee',
        'Faroese' => 'fo',
        'Fijian' => 'fj',
        'Finnish' => 'fi',
        'French' => 'fr',
        'Fulah' => 'ff',
        'Gaelic' => 'gd',
        'Scottish Gaelic' => 'gd',
        'Scottish' => 'gd',
        'Galician' => 'gl',
        'Ganda' => 'lg',
        'Georgian' => 'ka',
        'German' => 'de',
        'Gikuyu,' => 'ki',
        'Kikuyu' => 'ki',
        'Greek' => 'el',
        'Greenlandic' => 'kl',
        'Kalaallisut' => 'kl',
        'Guarani' => 'gn',
        'Gujarati' => 'gu',
        'Haitian' => 'ht',
        'Haitian Creole' => 'ht',
        'Hausa' => 'ha',
        'Hebrew' => 'he',
        'Herero' => 'hz',
        'Hindi' => 'hi',
        'Hiri Motu' => 'ho',
        'Hungarian' => 'hu',
        'Icelandic' => 'is',
        'Ido' => 'io',
        'Igbo' => 'ig',
        'Indonesian' => 'id',
        'Interlingua' => 'ia',
        'Interlingue' => 'ie',
        'Inuktitut' => 'iu',
        'Inupiaq' => 'ik',
        'Irish' => 'ga',
        'Italian' => 'it',
        'Japanese' => 'ja',
        'Javanese' => 'jv',
        'Kannada' => 'kn',
        'Kanuri' => 'kr',
        'Kashmiri' => 'ks',
        'Kazakh' => 'kk',
        'Kinyarwanda' => 'rw',
        'Komi' => 'kv',
        'Kongo' => 'kg',
        'Korean' => 'ko',
        'Kwanyama' => 'kj',
        'Kuanyama' => 'kj',
        'Kurdish' => 'ku',
        'Kyrgyz' => 'ky',
        'Lao' => 'lo',
        'Latin' => 'la',
        'Latvian' => 'lv',
        'Letzeburgesch' => 'lb',
        'Luxembourgish' => 'lb',
        'Limburgish' => 'li',
        'Limburgan' => 'li',
        'Limburger' => 'li',
        'Lingala' => 'ln',
        'Lithuanian' => 'lt',
        'Luba-Katanga' => 'lu',
        'Macedonian' => 'mk',
        'Malagasy' => 'mg',
        'Malay' => 'ms',
        'Malayalam' => 'ml',
        'Maltese' => 'mt',
        'Manx' => 'gv',
        'Maori' => 'mi',
        'Marathi' => 'mr',
        'Marshallese' => 'mh',
        'Moldovan' => 'ro',
        'Moldavian' => 'ro',
        'Romanian' => 'ro',
        'Mongolian' => 'mn',
        'Nauru' => 'na',
        'Navajo' => 'nv',
        'Navaho' => 'nv',
        'Northern Ndebele' => 'nd',
        'Ndonga' => 'ng',
        'Nepali' => 'ne',
        'Northern Sami' => 'se',
        'Norwegian' => 'no',
        'Norwegian Bokmål' => 'nb',
        'Bokmål' => 'nb',
        'Norwegian Nynorsk' => 'nn',
        'Nynorsk' => 'nn',
        'Nuosu' => 'ii',
        'Sichuan Yi' => 'ii',
        'Occitan' => 'oc',
        'Ojibwa' => 'oj',
        'Oriya' => 'or',
        'Oromo' => 'om',
        'Ossetian' => 'os',
        'Ossetic' => 'os',
        'Pali' => 'pi',
        'Panjabi' => 'pa',
        'Punjabi' => 'pa',
        'Pashto' => 'ps',
        'Pushto' => 'ps',
        'Persian' => 'fa',
        'Polish' => 'pl',
        'Portuguese' => 'pt',
        'Quechua' => 'qu',
        'Romansh' => 'rm',
        'Rundi' => 'rn',
        'Russian' => 'ru',
        'Samoan' => 'sm',
        'Sango' => 'sg',
        'Sanskrit' => 'sa',
        'Sardinian' => 'sc',
        'Serbian' => 'sr',
        'Shona' => 'sn',
        'Sindhi' => 'sd',
        'Sinhala' => 'si',
        'Sinhalese' => 'si',
        'Slovak' => 'sk',
        'Slovenian' => 'sl',
        'Somali' => 'so',
        'Sotho, Southern' => 'st',
        'South Ndebele' => 'nr',
        'Spanish' => 'es',
        'Castilian' => 'es',
        'Sundanese' => 'su',
        'Swahili' => 'sw',
        'Swati' => 'ss',
        'Swedish' => 'sv',
        'Tagalog' => 'tl',
        'Tahitian' => 'ty',
        'Tajik' => 'tg',
        'Tamil' => 'ta',
        'Tatar' => 'tt',
        'Telugu' => 'te',
        'Thai' => 'th',
        'Tibetan' => 'bo',
        'Tigrinya' => 'ti',
        'Tonga' => 'to',
        'Tsonga' => 'ts',
        'Tswana' => 'tn',
        'Turkish' => 'tr',
        'Turkmen' => 'tk',
        'Twi' => 'tw',
        'Uighur' => 'ug',
        'Uyghur' => 'ug',
        'Ukrainian' => 'uk',
        'Urdu' => 'ur',
        'Uzbek' => 'uz',
        'Venda' => 've',
        'Vietnamese' => 'vi',
        'Volap_k' => 'vo',
        'Walloon' => 'wa',
        'Welsh' => 'cy',
        'Western Frisian' => 'fy',
        'Wolof' => 'wo',
        'Xhosa' => 'xh',
        'Yiddish' => 'yi',
        'Yoruba' => 'yo',
        'Zhuang' => 'za',
        'Chuang' => 'za',
        'Zulu' => 'zu'
    ];
    public $language = array();
    public $addon_language = array();
    public $is_loaded = array();

    /**
     * Add a language file to the main language array
     *
     * @access	public
     * @param	string
     * @return	void
     */
    public function loadfile($which = '', $package = '', $show_errors = true)
    {
        if ($which == '') {
            return;
        }

        // Sec.ur.ity code.  ::sigh::
        $package = ($package == '')
            ? ee()->security->sanitize_filename(str_replace(array('lang.', '.php'), '', $which))
            : ee()->security->sanitize_filename($package);
        $which = str_replace('lang.', '', $which);

        // If we're in the installer, don't load Session library
        $idiom = $this->getIdiom();

        $this->load($which, $idiom, false, true, PATH_THIRD . $package . '/', $show_errors);
    }

    /**
     * Get the idiom for the current user/situation
     * @return string The idiom to load
     */
    public function getIdiom()
    {
        if (isset(ee()->session)) {
            return ee()->security->sanitize_filename(ee()->session->get_language());
        }

        return ee()->config->item('deft_lang') ?: 'english';
    }

    /**
     * Load a language file
     *
     * Differs from CI's Lang::load() in that it checks each file for a default
     * language version as a backup. Not sure this is appropriate for CI at
     * large.
     *
     * @param	mixed	the name of the language file to be loaded. Can be an array
     * @param	string	the language (english, etc.)
     * @return	mixed
     */
    public function load($langfile = '', $idiom = '', $return = false, $add_suffix = true, $alt_path = '', $show_errors = true)
    {
        //which scope should we load to? default is EE
        $scope = 'ee';

        // Clean up langfile
        $langfile = str_replace('.php', '', $langfile);

        if ($add_suffix == true) {
            $langfile = str_replace('_lang.', '', $langfile) . '_lang';
        }

        $langfile .= '.php';

        // Check to see if it's already loaded
        if (in_array($langfile, $this->is_loaded, true) && ! $return) {
            return;
        }

        $deft_lang = ee()->config->item('deft_lang') ?: 'english';
        if (empty($idiom)) {
            $idiom = $this->getIdiom();
        }

        $paths = array(
            // Check custom languages first
            SYSPATH . 'user/language/' . $idiom . '/' . $langfile,
            // Check if the user session language is English
            SYSPATH . 'ee/language/' . $idiom . '/' . $langfile,
            // Check their defined default language
            SYSPATH . 'user/language/' . $deft_lang . '/' . $langfile,
            // Lastly render the english
            SYSPATH . 'ee/language/english/' . $langfile
        );

        // If we're in the installer, add those lang files
        if (defined('EE_APPPATH')) {
            array_unshift(
                $paths,
                APPPATH . 'language/' . $idiom . '/' . $langfile,
                APPPATH . 'language/' . $deft_lang . '/' . $langfile
            );
        }

        // if it's in an alternate location, such as a package, check there first
        $alt_files = [];
        if ($alt_path != '') {
            // Temporary! Rename your language files!
            $third_party_old = 'lang.' . str_replace('_lang.', '.', $langfile);

            $alt_files = [
                $alt_path . 'language/english/' . $third_party_old,
                $alt_path . 'language/english/' . $langfile,
                $alt_path . 'language/' . $deft_lang . '/' . $third_party_old,
                $alt_path . 'language/' . $idiom . '/' . $third_party_old,
                $alt_path . 'language/' . $deft_lang . '/' . $langfile,
                $alt_path . 'language/' . $idiom . '/' . $langfile
            ];

            foreach ($alt_files as $file) {
                array_unshift($paths, $file);
            }
        }

        // if idiom and deft_lang are the same, don't check those paths twice
        $paths = array_unique($paths);

        $success = false;

        foreach ($paths as $path) {
            if (file_exists($path) && include $path) {
                $success = true;
                if (in_array($path, $alt_files)) {
                    $scope = 'addon';
                }

                break;
            }
        }

        if ($show_errors && $success !== true) {
            show_error('Unable to load the requested language file: language/' . $idiom . '/' . $langfile);
        }

        if (! isset($lang)) {
            log_message('debug', 'Language file contains no data: language/' . $idiom . '/' . $langfile);

            return;
        }

        if ($return == true) {
            return $lang;
        }

        $this->is_loaded[] = $langfile;

        switch ($scope) {
            case 'addon':
                $this->addon_language = array_merge($this->addon_language, $lang);

                break;
            case 'ee':
            default:
                $this->language = array_merge($this->language, $lang);

                break;
        }
        unset($lang);

        if (isset($ee_lang)) {
            $this->language = array_merge($this->language, $ee_lang);
            unset($ee_lang);
        }

        log_message('debug', 'Language file loaded: language/' . $idiom . '/' . $langfile);

        return true;
    }

    /**
     *   Fetch a specific line of text
     *
     * @access	public
     * @param	string
     * @param	string
     * @return	string
     */
    public function line($which = '', $label = '')
    {
        if ($which != '') {
            if (isset($this->language[$which])) {
                $line = $this->language[$which];
            } elseif (isset($this->addon_language[$which])) {
                $line = $this->addon_language[$which];
            } else {
                $line = $which;
            }

            if ($label != '') {
                $line = '<label for="' . $label . '">' . $line . "</label>";
            }

            return stripslashes($line);
        }
    }

    /**
     * Get a list of available language packs
     *
     * @return array Associative array of language packs, with the keys being
     * the directory names and the value being the name (ucfirst())
     */
    public function language_pack_names()
    {
        $source_dir = SYSPATH . 'user/language/';

        $dirs = array('english' => 'English');

        if ($fp = @opendir($source_dir)) {
            while (false !== ($file = readdir($fp))) {
                if (is_dir($source_dir . $file) && substr($file, 0, 1) != ".") {
                    $dirs[$file] = ucfirst($file);
                }
            }
            closedir($fp);
        }

        return $dirs;
    }

    /**
     * Get language code
     *
     * @return String ISO 639-1 code for given language name in English or current user language
     */
    public function code($language_name = null)
    {
        if (empty($language_name)) {
            $language_name = ee()->session->get_language();
        }
        $language_name = ucwords($language_name);
        if (isset($this->language_codes[$language_name])) {
            return $this->language_codes[$language_name];
        }

        return 'en';
    }
}
// END CLASS

// EOF
