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
 * File Helpers
 */

/**
 * Read File
 *
 * Opens the file specfied in the path and returns it as a string.
 *
 * @access  public
 * @param   string  path to file
 * @return  string
 */
if (! function_exists('read_file')) {
    function read_file($file)
    {
        if (! file_exists($file)) {
            return false;
        }

        return file_get_contents($file);
    }
}

/**
 * Write File
 *
 * Writes data to the file specified in the path.
 * Creates a new file if non-existent.
 *
 * @access  public
 * @param   string  path to file
 * @param   string  file data
 * @return  bool
 */
if (! function_exists('write_file')) {
    function write_file($path, $data, $mode = FOPEN_WRITE_CREATE_DESTRUCTIVE)
    {
        if (! $fp = @fopen($path, $mode)) {
            return false;
        }

        flock($fp, LOCK_EX);
        fwrite($fp, $data);
        flock($fp, LOCK_UN);
        fclose($fp);

        return true;
    }
}

/**
 * Delete Files
 *
 * Deletes all files contained in the supplied directory path.
 * Files must be writable or owned by the system in order to be deleted.
 * If the second parameter is set to TRUE, any directories contained
 * within the supplied base directory will be nuked as well.
 *
 * @access  public
 * @param   string  $path       Path to file
 * @param   bool    $del_dir    Whether to delete any directories found in the path
 * @param   int     $level      Levels deep to traverse the file tree to delete files
 * @param   array   $exclude    Array of file names to exclude from deletion
 * @return  bool
 */
if (! function_exists('delete_files')) {
    function delete_files($path, $del_dir = false, $level = 0, $exclude = array())
    {
        // Trim the trailing slash
        $path = rtrim($path, DIRECTORY_SEPARATOR);

        // If this isnt a directory, lets return false
        if (! is_dir($path) || ! $current_dir = @opendir($path)) {
            return false;
        }

        $exclude[] = '.';
        $exclude[] = '..';

        while (false !== ($filename = @readdir($current_dir))) {
            if (! in_array($filename, $exclude)) {
                if (is_dir($path . DIRECTORY_SEPARATOR . $filename)) {
                    // Ignore empty folders
                    if (substr($filename, 0, 1) != '.') {
                        delete_files($path . DIRECTORY_SEPARATOR . $filename, $del_dir, $level + 1, $exclude);
                    }
                } else {
                    unlink($path . DIRECTORY_SEPARATOR . $filename);
                }
            }
        }
        @closedir($current_dir);

        if ($del_dir == true and $level > 0) {
            return @rmdir($path);
        }

        return true;
    }
}

/**
 * Writes an index.html file to a specified path to ensure directories
 * cannot be indexed
 *
 * @access  public
 * @param   string  $path   Path to write index.html to
 * @return  bool    Success or failure of file writing
 */
if (! function_exists('write_index_html')) {
    function write_index_html($path)
    {
        $path = rtrim($path, '/') . '/';

        return write_file($path . 'index.html', 'Directory access is forbidden.');
    }
}

/**
 * Get Filenames
 *
 * Reads the specified directory and builds an array containing the filenames.
 * Any sub-folders contained within the specified path are read as well.
 *
 * @access  public
 * @param   string  path to source
 * @param   bool    whether to include the path as part of the filename
 * @param   bool    internal variable to determine recursion status - do not use in calls
 * @return  array
 */
if (! function_exists('get_filenames')) {
    function get_filenames($source_dir, $include_path = false, $_recursion = false)
    {
        static $_filedata = array();

        if ($fp = @opendir($source_dir)) {
            // reset the array and make sure $source_dir has a trailing slash on the initial call
            if ($_recursion === false) {
                $_filedata = array();
                $source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }

            while (false !== ($file = readdir($fp))) {
                if (@is_dir($source_dir . $file) && strncmp($file, '.', 1) !== 0) {
                    get_filenames($source_dir . $file . DIRECTORY_SEPARATOR, $include_path, true);
                } elseif (strncmp($file, '.', 1) !== 0) {
                    $_filedata[] = ($include_path == true) ? $source_dir . $file : $file;
                }
            }

            return $_filedata;
        } else {
            return false;
        }
    }
}

/**
 * Get Directory File Information
 *
 * Reads the specified directory and builds an array containing the filenames,
 * filesize, dates, and permissions
 *
 * Any sub-folders contained within the specified path are read as well.
 *
 * @access  public
 * @param   string  path to source
 * @param   bool    Look only at the top level directory specified?
 * @param   bool    internal variable to determine recursion status - do not use in calls
 * @return  array
 */
if (! function_exists('get_dir_file_info')) {
    function get_dir_file_info($source_dir, $top_level_only = true, $_recursion = false)
    {
        static $_filedata = array();
        $relative_path = $source_dir;

        if ($fp = @opendir($source_dir)) {
            // reset the array and make sure $source_dir has a trailing slash on the initial call
            if ($_recursion === false) {
                $_filedata = array();
                $source_dir = rtrim(realpath($source_dir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            }

            // foreach (scandir($source_dir, 1) as $file) // In addition to being PHP5+, scandir() is simply not as fast
            while (false !== ($file = readdir($fp))) {
                if (@is_dir($source_dir . $file) and strncmp($file, '.', 1) !== 0 and $top_level_only === false) {
                    get_dir_file_info($source_dir . $file . DIRECTORY_SEPARATOR, $top_level_only, true);
                } elseif (strncmp($file, '.', 1) !== 0) {
                    $_filedata[$file] = get_file_info($source_dir . $file);
                    $_filedata[$file]['relative_path'] = $relative_path;
                }
            }

            return $_filedata;
        } else {
            return false;
        }
    }
}

/**
* Get File Info
*
* Given a file and path, returns the name, path, size, date modified
* Second parameter allows you to explicitly declare what information you want returned
* Options are: name, server_path, size, date, readable, writable, executable, fileperms
* Returns FALSE if the file cannot be found.
*
* @access   public
* @param    string  path to file
* @param    mixed   array or comma separated string of information returned
* @return   array
*/
if (! function_exists('get_file_info')) {
    function get_file_info($file, $returned_values = array('name', 'server_path', 'size', 'date'))
    {
        if (! file_exists($file)) {
            return false;
        }

        if (is_string($returned_values)) {
            $returned_values = explode(',', $returned_values);
        }

        foreach ($returned_values as $key) {
            switch ($key) {
                case 'name':
                    $fileinfo['name'] = substr(strrchr($file, DIRECTORY_SEPARATOR), 1);

                    break;
                case 'server_path':
                    $fileinfo['server_path'] = $file;

                    break;
                case 'size':
                    $fileinfo['size'] = filesize($file);

                    break;
                case 'date':
                    $fileinfo['date'] = filemtime($file);

                    break;
                case 'readable':
                    $fileinfo['readable'] = is_readable($file);

                    break;
                case 'writable':
                    // There are known problems using is_weritable on IIS.  It may not be reliable - consider fileperms()
                    $fileinfo['writable'] = is_writable($file);

                    break;
                case 'executable':
                    $fileinfo['executable'] = is_executable($file);

                    break;
                case 'fileperms':
                    $fileinfo['fileperms'] = fileperms($file);

                    break;
            }
        }

        return $fileinfo;
    }
}

/**
 * Symbolic Permissions
 *
 * Takes a numeric value representing a file's permissions and returns
 * standard symbolic notation representing that value
 *
 * @access  public
 * @param   int
 * @return  string
 */
if (! function_exists('symbolic_permissions')) {
    function symbolic_permissions($perms)
    {
        if (($perms & 0xC000) == 0xC000) {
            $symbolic = 's'; // Socket
        } elseif (($perms & 0xA000) == 0xA000) {
            $symbolic = 'l'; // Symbolic Link
        } elseif (($perms & 0x8000) == 0x8000) {
            $symbolic = '-'; // Regular
        } elseif (($perms & 0x6000) == 0x6000) {
            $symbolic = 'b'; // Block special
        } elseif (($perms & 0x4000) == 0x4000) {
            $symbolic = 'd'; // Directory
        } elseif (($perms & 0x2000) == 0x2000) {
            $symbolic = 'c'; // Character special
        } elseif (($perms & 0x1000) == 0x1000) {
            $symbolic = 'p'; // FIFO pipe
        } else {
            $symbolic = 'u'; // Unknown
        }

        // Owner
        $symbolic .= (($perms & 0x0100) ? 'r' : '-');
        $symbolic .= (($perms & 0x0080) ? 'w' : '-');
        $symbolic .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x') : (($perms & 0x0800) ? 'S' : '-'));

        // Group
        $symbolic .= (($perms & 0x0020) ? 'r' : '-');
        $symbolic .= (($perms & 0x0010) ? 'w' : '-');
        $symbolic .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x') : (($perms & 0x0400) ? 'S' : '-'));

        // World
        $symbolic .= (($perms & 0x0004) ? 'r' : '-');
        $symbolic .= (($perms & 0x0002) ? 'w' : '-');
        $symbolic .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x') : (($perms & 0x0200) ? 'T' : '-'));

        return $symbolic;
    }
}

/**
 * Octal Permissions
 *
 * Takes a numeric value representing a file's permissions and returns
 * a three character string representing the file's octal permissions
 *
 * @access  public
 * @param   int
 * @return  string
 */
if (! function_exists('octal_permissions')) {
    function octal_permissions($perms)
    {
        return substr(sprintf('%o', $perms), -3);
    }
}

// EOF
