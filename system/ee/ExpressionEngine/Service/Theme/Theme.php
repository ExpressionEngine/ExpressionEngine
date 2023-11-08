<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Service\Theme;

/**
 * Theme Service
 */
class Theme
{
    /**
     * @var string The path to the 'system/ee/templates/_themes/' directory
     */
    protected $ee_theme_templates_path;

    /**
     * @var string The URL to the 'themes/ee' directory
     */
    protected $ee_theme_assets_url;

    /**
     * @var string The path to the 'system/user/templates/_themes/' directory
     */
    protected $user_theme_templates_path;

    /**
     * @var string The URL to the 'themes/user' directory
     */
    protected $user_theme_assets_url;

    /**
     * @var string The path to the 'themes/ee' directory
     */
    protected $ee_theme_assets_path;

    /**
     * @var string The path to the 'themes/user' directory
     */
    protected $user_theme_assets_path;

    /**
     * Constructor: sets the ee and user theme path and URL properties
     *
     * @param string $ee_theme_templates_path The path to the 'system/ee/templates/_themes' directory
     * @param string $ee_theme_assets_url The URL to the 'themes/ee' directory
     * @param string $user_theme_templates_path The path to the 'system/user/templates/_themes' directory
     * @param string $user_theme_assets_url The URL to the 'themes/user' directory
     * @param string $ee_theme_assets_path The path to the 'themes/ee' directory
     * @param string $user_theme_assets_path The URL to the 'themes/user' directory
     */
    public function __construct($ee_theme_templates_path, $ee_theme_assets_url, $user_theme_templates_path, $user_theme_assets_url, $ee_theme_assets_path, $user_theme_assets_path)
    {
        $this->ee_theme_templates_path = $ee_theme_templates_path;
        $this->ee_theme_assets_url = $ee_theme_assets_url;
        $this->user_theme_templates_path = $user_theme_templates_path;
        $this->user_theme_assets_url = $user_theme_assets_url;
        $this->ee_theme_assets_path = $ee_theme_assets_path;
        $this->user_theme_assets_path = $user_theme_assets_path;
    }

    /**
     * Gets the full path to the indicated file/directory. If the file/directory
     * exists in the user's theme folder use that, otherwise use the ee theme
     * folder.
     *
     * @param string $path The relative path to the file/directory, i.e. "forum/default"
     * @return string The full path to the file/directory
     */
    public function getPath($path)
    {
        if (file_exists($this->user_theme_templates_path . $path)) {
            return $this->user_theme_templates_path . $path;
        } elseif (file_exists($this->user_theme_assets_path . $path)) {
            return $this->user_theme_assets_path . $path;
        }

        return $this->ee_theme_templates_path . $path;
    }

    /**
     * Gets the full path to the indicated file/directory.
     * Searches only in user folder as edits in ee folder are not allowed.
     *
     * @param string $path The relative path to the file/directory, i.e. "forum/default"
     * @return string The full path to the file/directory
     */
    public function getUserPath($path)
    {
        if (file_exists($this->user_theme_templates_path . $path)) {
            return $this->user_theme_templates_path . $path;
        } elseif (file_exists($this->user_theme_assets_path . $path)) {
            return $this->user_theme_assets_path . $path;
        }

        return false;
    }

    /**
     * Gets the URL to the indicated file/directory. If the file/directory
     * exists in the user's theme folder use that, otherwise use the ee theme
     * folder. A URL MUST go to themes not system.
     *
     * @param string $path The relative path to the file/directory, i.e. "forum/default"
     * @return string The URL to the file/directory
     */
    public function getUrl($path)
    {
        if (file_exists($this->user_theme_assets_path . $path)) {
            return $this->user_theme_assets_url . $path;
        }

        return $this->ee_theme_assets_url . $path;
    }

    /**
     * Gets a list of all the themes available of a certain kind. When a theme
     * exists under both the user folder and the ee folder, the user folder is
     * prefered.
     *
     * @param string $path A path to a directory we want to list
     * @return array An associative array of the contents of the directory
     *  using the file/folder name as the key and making a presentable name as
     *  the value, i.e. 'my_happy_theme' => 'My Happy Theme'
     */
    public function listThemes($kind)
    {
        $user_files = $this->listDirectory($this->user_theme_templates_path . $kind . '/');

        if (empty($user_files)) {
            $user_files = $this->listDirectory($this->user_theme_assets_path . $kind . '/');
        }

        // EE first so the User based themes can override.
        return array_merge(
            $this->listDirectory($this->ee_theme_templates_path . $kind . '/'),
            $user_files
        );
    }

    /**
     * Gets a list of all the themes available of a certain kind.
     * Searches onlin in user folder, as edits in ee folder are not allowed.
     *
     * @param string $path A path to a directory we want to list
     * @return array An associative array of the contents of the directory
     *  using the file/folder name as the key and making a presentable name as
     *  the value, i.e. 'my_happy_theme' => 'My Happy Theme'
     */
    public function listUserThemes($kind)
    {
        $user_files = $this->listDirectory($this->user_theme_templates_path . $kind . '/');

        if (empty($user_files)) {
            $user_files = $this->listDirectory($this->user_theme_assets_path . $kind . '/');
        }

        return $user_files;
    }

    /**
     * Gets the contents of a directory using the "folder" name as the key and
     * transforms that into a presentable name.
     *
     * @param string $path A path to a directory we want to list
     * @return array An associative array of the contents of the directory
     *  using the file/folder name as the key and making a presentable name as
     *  the value, i.e. 'my_happy_theme' => 'My Happy Theme'
     */
    protected function listDirectory($path)
    {
        $files = array();

        if (! file_exists($path)) {
            return $files;
        }

        if (! $fp = @opendir($path)) {
            return $files;
        }

        while (($folder = readdir($fp)) !== false) {
            if (@is_dir($path . $folder) && substr($folder, 0, 1) != '.') {
                $files[$folder] = ucwords(str_replace("_", " ", $folder));
            }
        }

        closedir($fp);
        ksort($files);

        return $files;
    }
}
// EOF
