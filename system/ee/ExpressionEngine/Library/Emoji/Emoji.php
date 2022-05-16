<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2021, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

// the data files are generated using (modified) Emoji Detection library by Aaron Parecki, MIT license
// https://github.com/aaronpk/emoji-detector-php/ and https://github.com/intoeetive/emoji-detector-php/tree/feature/reverse-emoji-map
// original data source by Cal Henderson https://github.com/iamcal/emoji-data MIT License

namespace ExpressionEngine\Library\Emoji;

class Emoji
{
    private static $_emojiRegex;
    private static $_emojiMap = [];

    public function __get($param)
    {
        switch ($param) {
            case 'emojiRegex':
                return $this->get__emojiRegex();
                break;
            case 'emojiMap':
                return $this->get__emojiMap();
                break;
            default:
                return null;
        }
    }

    public function get__emojiRegex()
    {
        if (empty(self::$_emojiRegex)) {
            $path = SYSPATH . 'ee/ExpressionEngine/Config/emoji_regex.json';
            $userpath = SYSPATH . 'user/config/emoji_regex.json';
            if (file_exists($userpath)) {
                $path = $userpath;
            }
            if (file_exists($path)) {
                $data = file_get_contents($path);
            }
            if (isset($data)) {
                self::$_emojiRegex = json_decode($data);
            }
        }
        return self::$_emojiRegex;
    }

    public function get__emojiMap()
    {
        if (empty(self::$_emojiMap)) {
            $map = $usermap = [];
            //get the amoji map according to skin tone configured
            if (in_array(ee()->config->item('emoji_skin_tone'), range(2, 6))) {
                $path = SYSPATH . 'ee/ExpressionEngine/Config/emoji_map-skin-tone-' . ee()->config->item('emoji_skin_tone') . '.json';
                if (file_exists($path)) {
                    $data = file_get_contents($path);
                }
            }
            //if no file, or not valid config, fallback to default
            if (!isset($data)) {
                $path = SYSPATH . 'ee/ExpressionEngine/Config/emoji_map.json';
                if (file_exists($path)) {
                    $data = file_get_contents($path);
                }
            }
            if (isset($data)) {
                $map = json_decode($data);
            }
            $userpath = SYSPATH . 'user/config/emoji_map.json';
            if (file_exists($userpath)) {
                $userdata = file_get_contents($userpath);
            }
            if (isset($userdata)) {
                $usermap = json_decode($userdata);
            }
            $emojiMap = array_merge((array) $map, (array) $usermap);
            foreach ($emojiMap as $shortcut => $sequence) {
                self::$_emojiMap[$shortcut] = '&#x' . str_replace('-', ';&#x', $sequence) . ';';
            }
        }
        return self::$_emojiMap;
    }
}
