<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */
if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Pro Search Settings class
 */
class Pro_search_settings
{
    // --------------------------------------------------------------------
    // PROPERTIES
    // --------------------------------------------------------------------

    /**
     * Settings
     *
     * @access     protected
     * @var        array
     */
    private $_settings = array();

    /**
     * Default settings
     *
     * @var        array
     * @access     protected
     */
    private $_default_settings = array(
        'encode_query'        => 'y',
        'min_word_length'     => '4',
        'excerpt_length'      => '50',
        'excerpt_hilite'      => '',
        'title_hilite'        => '',
        'batch_size'          => '100',
        'default_result_page' => 'search/results',
        'search_log_size'     => '500',
        'ignore_words'        => 'a an and the or of s',
        'disabled_filters'    => array(),
        'build_index_act_key' => '',
        'stop_words'          =>
            // http://dev.mysql.com/doc/refman/5.5/en/fulltext-stopwords.html
            "a's able about above according accordingly across actually after afterwards again against ain't
            all allow allows almost alone along already also although always am among amongst an and another
            any anybody anyhow anyone anything anyway anyways anywhere apart appear appreciate appropriate are
            aren't around as aside ask asking associated at available away awfully be became because become
            becomes becoming been before beforehand behind being believe below beside besides best better between
            beyond both brief but by c'mon c's came can can't cannot cant cause causes certain certainly changes
            clearly co com come comes concerning consequently consider considering contain containing contains
            corresponding could couldn't course currently definitely described despite did didn't different do
            does doesn't doing don't done down downwards during each edu eg eight either else elsewhere enough
            entirely especially et etc even ever every everybody everyone everything everywhere ex exactly example
            except far few fifth first five followed following follows for former formerly forth four from further
            furthermore get gets getting given gives go goes going gone got gotten greetings had hadn't happens
            hardly has hasn't have haven't having he he's hello help hence her here here's hereafter hereby herein
            hereupon hers herself hi him himself his hither hopefully how howbeit however i'd i'll i'm i've ie if
            ignored immediate in inasmuch inc indeed indicate indicated indicates inner insofar instead into
            inward is isn't it it'd it'll it's its itself just keep keeps kept know known knows last lately later
            latter latterly least less lest let let's like liked likely little look looking looks ltd mainly many
            may maybe me mean meanwhile merely might more moreover most mostly much must my myself name namely nd
            near nearly necessary need needs neither never nevertheless new next nine no nobody non none noone nor
            normally not nothing novel now nowhere obviously of off often oh ok okay old on once one ones only
            onto or other others otherwise ought our ours ourselves out outside over overall own particular
            particularly per perhaps placed please plus possible presumably probably provides que quite qv rather
            rd re really reasonably regarding regardless regards relatively respectively right said same saw say
            saying says second secondly see seeing seem seemed seeming seems seen self selves sensible sent
            serious seriously seven several shall she should shouldn't since six so some somebody somehow someone
            something sometime sometimes somewhat somewhere soon sorry specified specify specifying still sub such
            sup sure t's take taken tell tends th than thank thanks thanx that that's thats the their theirs them
            themselves then thence there there's thereafter thereby therefore therein theres thereupon these they
            they'd they'll they're they've think third this thorough thoroughly those though three through
            throughout thru thus to together too took toward towards tried tries truly try trying twice two un
            under unfortunately unless unlikely until unto up upon us use used useful uses using usually value
            various very via viz vs want wants was wasn't way we we'd we'll we're we've welcome well went were
            weren't what what's whatever when whence whenever where where's whereafter whereas whereby wherein
            whereupon wherever whether which while whither who who's whoever whole whom whose why will willing
            wish with within without won't wonder would wouldn't yes yet you you'd you'll you're you've your
            yours yourself yourselves zero",

        // Permissions
        'can_manage'           => array(),
        'can_manage_shortcuts' => array(),
        'can_manage_lexicon'   => array(),
        'can_replace'          => array(),
        'can_view_search_log'  => array(),
        'can_view_replace_log' => array(),
    );

    private $_search_modes = array('any', 'all', 'exact', 'auto');
    private $_hilite_tags = array('em', 'span', 'strong', 'mark');
    private $_prefix = 'pro_search_';

    // --------------------------------------------------------------------
    // METHODS
    // --------------------------------------------------------------------

    /**
     * Set the settings
     */
    public function set($settings)
    {
        $this->_settings = array_merge($this->_default_settings, $settings);
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        $key = '_' . $key;

        return isset($this->$key) ? $this->$key : null;
    }

    // --------------------------------------------------------------------

    /**
     * Get setting
     */
    public function get($key = null)
    {
        if (empty($this->_settings)) {
            // Not set yet? Get from DB and add to cache
            $query = ee()->db->select('settings')
                ->from('extensions')
                ->where('class', 'Pro_search_ext')
                ->limit(1)
                ->get();

            if ($query->num_rows > 0) {
                $this->_settings = (array) @unserialize($query->row('settings'));
            }
        }

        // Always fallback to default settings
        $this->_settings = array_merge($this->_default_settings, $this->_settings);

        return is_null($key)
            ? $this->_settings
            : (isset($this->_settings[$key]) ? $this->_settings[$key] : null);
    }

    // --------------------------------------------------------------------

    /**
     * cleaned and array'd Stop words
     *
     * @access     public
     * @return     array
     */
    public function stop_words()
    {
        return $this->_words('stop');
    }

    /**
     * cleaned and array'd Ignore words
     *
     * @access     public
     * @return     array
     */
    public function ignore_words()
    {
        return $this->_words('ignore');
    }

    /**
     * cleaned and array'd words
     *
     * @access     private
     * @return     array
     */
    private function _words($which)
    {
        return (array) array_unique(preg_split(
            '/\s+/',
            str_replace("'", ' ', $this->get($which . '_words')),
            0,
            PREG_SPLIT_NO_EMPTY
        ));
    }

    // --------------------------------------------------------------------

    /**
     * Permission options
     *
     * @access     public
     * @return     array
     */
    public function permissions()
    {
        $out = array();

        foreach (array_keys($this->_default_settings) as $key) {
            // Permissions look like can_whatever
            if (substr($key, 0, 4) != 'can_') {
                continue;
            }
            // Add those to the output
            $out[] = $key;
        }

        return $out;
    }
}
// End of file Pro_search_settings.php
