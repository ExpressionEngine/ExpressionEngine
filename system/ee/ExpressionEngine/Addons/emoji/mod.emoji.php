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
 * Emoji Module
 */
class Emoji
{
    public $return_data = '';

    /**
      *  {exp:emoji:emoji_list}
      *
      *  Outputs all available emoji
      */
    public function emoji_list()
    {
        $emoji = ee()->config->loadFile('emoji');

        foreach ($emoji as $em) {
            $vars[] = [
                'html_entity' => $em->html_entity,
                'short_name' => $em->short_name,
            ];
        }

        return ee()->TMPL->parse_variables(ee()->TMPL->tagdata, $vars);
    }

    /**
     * {exp:emoji:parse_shorthand}
     *
     * Parse emoji codes in arbitrary text. :joy:!
     */
    public function parse_shorthand()
    {
        return (string) ee('Format')->make('Text', ee()->TMPL->tagdata)->emojiShorthand();
    }
}
// END CLASS

// EOF
