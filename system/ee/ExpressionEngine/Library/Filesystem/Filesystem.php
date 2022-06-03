<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2022, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Filesystem;

use ExpressionEngine\Dependency\League\Flysystem;
use FilesystemIterator;

/**
 * Filesystem
 */
class Filesystem
{
    protected $flysystem;

    public function __construct(?Flysystem\AdapterInterface $adapter = null, $config = [])
    {
        if (is_null($adapter)) {
            $adapter = new Flysystem\Adapter\Local($this->normalizeAbsolutePath(ee()->config->item('base_path') ?: $_SERVER['DOCUMENT_ROOT']));
        }else{
            // Fix prefixes
            $adapter->setPathPrefix($this->normalizeAbsolutePath($adapter->getPathPrefix()));
        }
        // Create the cache store
        $cacheStore = new Flysystem\Cached\Storage\Memory();
        $adapter = new Flysystem\Cached\CachedAdapter($adapter, $cacheStore);

        $defaults = [
            'visibility' => Flysystem\AdapterInterface::VISIBILITY_PUBLIC
        ];

        $config = array_merge($defaults, $config);

        $this->flysystem = new Flysystem\Filesystem($adapter, $config);
        $this->flysystem->addPlugin(new Flysystem\Plugin\GetWithMetadata());
    }

    /**
     * Read a file from disk
     *
     * @param String $path File to read
     * @return String File contents
     */
    public function read($path)
    {
        $path = $this->normalizeRelativePath($path);
        if (!$this->exists($path)) {
            throw new FilesystemException("File not found: {$path}");
        } elseif (!$this->isFile($path)) {
            throw new FilesystemException("Not a file: {$path}");
        } elseif (!$this->isReadable($path)) {
            throw new FilesystemException("Cannot read file: {$path}");
        }

        return $this->flysystem->read($this->normalize($path));
    }

    public function readStream($path)
    {
        $path = $this->normalizeRelativePath($path);
        return $this->flysystem->readStream($path);
    }

    /**
     * Read a file from disk line-by-line, good for large text files
     *
     * @param String $path File to read
     * @param Callable Callback to call for each line of the file, accepts one parameter
     */
    public function readLineByLine($path, callable $callback)
    {
        // @todo implement flysystem option, likely needs to read by stream
        if (!$this->exists($path)) {
            throw new FilesystemException("File not found: {$path}");
        } elseif (!$this->isFile($path)) {
            throw new FilesystemException("Not a file: {$path}");
        } elseif (!$this->isReadable($path)) {
            throw new FilesystemException("Cannot read file: {$path}");
        }

        $pointer = fopen($path, 'r');

        while (!feof($pointer)) {
            $callback(fgets($pointer));
        }

        fclose($pointer);
    }

    /**
     * Write a file to disk
     *
     * @param String $path File to write to
     * @param String $data Data to write
     * @param bool $overwrite Overwrite existing files?
     * @param bool $append Append to existing file?
     */
    public function write($path, $data, $overwrite = false, $append = false)
    {
        $path = $this->normalize($path);
        $path = $this->normalizeRelativePath($path);

        if ($this->isDir($path)) {
            throw new FilesystemException("Cannot write file, path is a directory: {$path}");
        } elseif ($this->isFile($path) && $overwrite == false && $append == false) {
            throw new FilesystemException("File already exists: {$path}");
        } elseif ($append && !($this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local)) {
            throw new FilesystemException("Appending to file not supported by adapter '" . get_class($this->flysystem->getAdapter()) . "'");
        }

        if ($overwrite == false && $append == true) {
            $flags = FILE_APPEND | LOCK_EX;
            file_put_contents($path, $data, $flags);
        } else {
            $this->flysystem->put($path, $data);
        }

        $this->ensureCorrectAccessMode($path);
    }

    public function writeStream($path, $resource, array $config = [])
    {
        $path = $this->normalizeRelativePath($path);
        return $this->flysystem->writeStream($path, $resource, $config);
    }

    /**
     * Append to an existing file
     *
     * @param String $path File to write to
     * @param String $data Data to write
     */
    public function append($path, $data)
    {
        $this->write($path, $data, false, true);
    }

    /**
     * Make a new directory
     *
     * @param String $path Directory to create
     * @param bool $with_index Add EE's default index.html file in the new dir?
     * @return bool Success or failure of mkdir()
     */
    public function mkDir($path, $with_index = true)
    {
        $path = $this->normalize($path);
        $path = $this->normalizeRelativePath($path);
        $result = $this->flysystem->createDir($path);

        if (!$result) {
            return false;
        }

        if ($with_index) {
            $this->addIndexHtml($path);
        }

        $this->ensureCorrectAccessMode($path);

        return true;
    }

    /**
     * Delete a file or directory
     *
     * @param String $path File or directory to delete
     */
    public function delete($path)
    {
        $path = $this->normalizeRelativePath($path);
        return $this->flysystem->delete($path);
    }

    /**
     * Delete a file
     *
     * @param String $path File to delete
     */
    public function deleteFile($path)
    {
        $path = $this->normalizeRelativePath($path);
        if (!$this->isFile($path)) {
            throw new FilesystemException("File does not exist {$path}");
        }

        return $this->flysystem->delete($this->normalize($path));
    }

    /**
     * Delete a directory
     *
     * @param String $path Directory to delete
     * @param bool $leave_empty Keep the empty root directory?
     */
    public function deleteDir($path, $leave_empty = false)
    {
        $path = $this->normalize($path);
        $path = $this->normalizeRelativePath(rtrim($path, '/'));

        if (!$this->isDir($path)) {
            throw new FilesystemException("Directory does not exist {$path}.");
        }

        if (!$leave_empty && $this->attemptFastDelete($path)) {
            return true;
        }

        $this->flysystem->deleteDir($path);

        if ($leave_empty) {
            $this->flysystem->createDir($path);
        }

        return true;
    }

    /**
     * Gets the contents of a directory as a flat array, with the option of
     * returning a recursive listing
     *
     * @param String $path Directory to search
     * @param bool $recursive Whether or not to do a recursive search
     * @param array Array of all paths found inside the specified directory
     */
    public function getDirectoryContents($path = '/', $recursive = false, $includeHidden = false)
    {
        $path = $this->normalizeRelativePath($path);

        if ($this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            if (!$this->exists($path)) {
                throw new FilesystemException('Cannot get contents of path, the path is invalid: ' . $path);
            }

            if (!$this->isDir($path)) {
                throw new FilesystemException('Cannot get contents of path, the path is not a directory: ' . $path);
            }
        }

        $contents = $this->flysystem->listContents($path);
        $contents_array = [];

        foreach ($contents as $item) {
            if ($item['type'] == 'dir' && $recursive) {
                $contents_array += $this->getDirectoryContents($item['path'], $recursive);
            } else if($includeHidden || strpos($item['path'], '.') !== 0) {
                $contents_array[] = $item['path'];
            }
        }

        return $contents_array;
    }

    /**
     * Get the file with metadata info
     *
     * @param string $path
     * @param array $metadata
     * @return array|false metadata
     */
    public function getWithMetadata($path, $metadata = [])
    {
        $path = $this->normalizeRelativePath($path);
        return $this->flysystem->getWithMetadata($this->normalize($path), $metadata);
    }

    /**
     * Empty a directory
     *
     * @param String $path Directory to empty
     * @param bool $add_index Add EE's default index.html file to the directory
     */
    public function emptyDir($path, $add_index = true)
    {
        $this->deleteDir($path, true);
        if($add_index) {
            $this->addIndexHtml($path);
        }
    }

    /**
     * Attempt to delete a file using the OS method
     *
     * We can't always do this, but it's much, much faster than iterating
     * over directories with many children.
     *
     * @param bool whether or not the fast system delete could be done
     */
    protected function attemptFastDelete($path)
    {
        if (!$this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            return false;
        }

        $path = $this->normalize($path);

        $delete_name = sha1($path . '_delete_' . mt_rand());
        $delete_path = PATH_CACHE . $delete_name;
        $this->rename($path, $delete_path);

        if ($this->exists($delete_path) && is_dir($delete_path)) {
            $delete_path = @escapeshellarg($delete_path);

            if (DIRECTORY_SEPARATOR == '/') {
                @exec("rm -rf {$delete_path}");
            } else {
                @exec("rd /s /q {$delete_path}");
            }

            return  ! $this->exists($delete_path);
        }

        return false;
    }

    /**
     * Rename a file or directory
     *
     * @param String $source File or directory to rename
     * @param String $dest New location for the file or directory
     */
    public function rename($source, $dest)
    {
        $source = $this->normalizeRelativePath($source);
        $dest = $this->normalizeRelativePath($dest);

        if (! $this->exists($source)) {
            throw new FilesystemException("Cannot rename non-existent path: {$source}");
        } elseif ($this->exists($dest)) {
            throw new FilesystemException("Cannot rename, destination already exists: {$dest}");
        }

        // Suppressing potential warning when renaming a directory to one that already exists.
        @$this->flysystem->rename($this->normalize($source), $this->normalize($dest));

        $this->ensureCorrectAccessMode($dest);
    }

    /**
     * Copy a file or directory
     *
     * @param String $source File or directory to copy
     * @param Stirng $dest Path to the duplicate
     */
    public function copy($source, $dest)
    {
        $source = $this->normalizeRelativePath($source);
        $dest = $this->normalizeRelativePath($dest);

        if (! $this->exists($source)) {
            throw new FilesystemException("Cannot copy non-existent path: {$source}");
        }

        if ($this->isDir($source)) {
            $this->recursiveCopy($source, $dest);
        } else {
            $this->flysystem->copy($this->normalize($source), $this->normalize($dest));
        }

        $this->ensureCorrectAccessMode($dest);
    }

    /**
     * Copies a directory to another directory by recursively iterating over its files
     *
     * @param String $source Directory to copy
     * @param Stirng $dest Path to the duplicate
     */
    protected function recursiveCopy($source, $dest)
    {
        $dir = $this->flysystem->listContents($source, false);
        $this->flysystem->createDir($dest);

        foreach($dir as $file) {
            if ($this->isDir($source . '/' . $file['path'])) {
                $this->recursiveCopy($source . '/' . $file['path'], $dest . '/' . $file['path']);
            } else {
                $this->flysystem->copy($source . '/' . $file['path'], $dest . '/' . $file['path']);
            }
        }
    }

    /**
     * Get the path to the parent directory
     *
     * @param String $path Path to extract dirname from
     * @return String Path to the parent directory
     */
    public function dirname($path)
    {
        return pathinfo($this->normalize($path), PATHINFO_DIRNAME);
    }

    /**
     * Get the filename and extension
     *
     * @param String $path Path to extract basename from
     * @return String Filename with extension
     */
    public function basename($path)
    {
        return basename($this->normalize($path));
    }

    /**
     * Get the filename without extension
     *
     * @param String $path Path to extract filename from
     * @return String Filename without extension
     */
    public function filename($path)
    {
        return pathinfo($this->normalize($path), PATHINFO_FILENAME);
    }

    /**
     * Get the extension
     *
     * @param String $path Path to extract extension from
     * @return String Extension
     */
    public function extension($path)
    {
        return pathinfo($this->normalize($path), PATHINFO_EXTENSION);
    }

    /**
     * Check if a path exists
     *
     * @param String $path Path to check
     * @return bool Path exists?
     */
    public function exists($path)
    {
        // We are intentionally not calling `$this->flysystem->has($path);` so that
        // we can handle calls to check the existence of the base path
        $path = $this->normalizeRelativePath($path);

        // If the path is the root of this filesystem it must exist or the
        // filesystem would have thrown an exception during construction
        if($path === '') {
            return true;
        }

        return (bool) $this->flysystem->getAdapter()->has($path);
    }

    /**
     * Get the last modified time
     *
     * @param String $path Path to directory or file
     * @return int Last modified time
     */
    public function mtime($path)
    {
        $path = $this->normalizeRelativePath($path);

        if (! $this->exists($path)) {
            throw new FilesystemException("File does not exist: {$path}");
        }

        return $this->flysystem->getTimestamp($this->normalize($path));
    }

    /**
     * Get the mimetype for file at $path
     *
     * @param String $path Path to file
     * @return String|false mime-type or false on failure
     */
    public function getMimetype($path)
    {
        $path = $this->normalizeRelativePath($path);
        $mime = $this->flysystem->getMimetype($path);

        // try another method to get mime
        if ($mime == 'application/octet-stream') {
            $opening = fread($this->flysystem->readStream($path), 50);
            $mime = ee('MimeType')->guessOctetStream($opening);
        }

        return $mime;
    }

    /**
     * Touch a file or directory
     *
     * @param String $path File/directory to touch
     * @param int Set the last modified time [optional]
     */
    public function touch($path, $time = null)
    {
        $path = $this->normalizeRelativePath($path);

        if (! $this->exists($path)) {
            throw new FilesystemException("Touching non-existent files is not supported: {$path}");
        }

        if (isset($time) && $this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            touch($this->flysystem->getAdapter()->applyPathPrefix($this->normalize($path)), $time);
        } else {
            $this->write($this->normalize($path), '');
        }
    }

    /**
     * Check if a given path is a directory
     *
     * @param String $path Path to check
     * @return bool Is a directory?
     */
    public function isDir($path = '/')
    {
        if ($this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            return is_dir($this->ensurePrefixedPath($path));
        }
        $path = $this->normalizeRelativePath($path);
        return empty($this->extension($path)) && $this->exists($path);
    }

    /**
     * Check if a given path is a file
     *
     * @param String $path Path to check
     * @return bool Is a file?
     */
    public function isFile($path)
    {
        $path = $this->normalizeRelativePath($path);

        if ($this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            return is_file($this->ensurePrefixedPath($this->normalize($path)));
        }

        return !empty($this->extension($path)) && $this->flysystem->has($path);
    }

    /**
     * Check if a path is readable
     *
     * @param String $path Path to check
     * @return bool Is readable?
     */
    public function isReadable($path = '')
    {
        if (!$this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            return true; // or is `return $this->flysystem->has($path);` better?
        }

        return is_readable($this->ensurePrefixedPath($this->normalize($path)));
    }

    /**
     * Change the access mode of a file
     *
     * @param String $path Path to Change
     * @param Int Mode, please provide an octal
     */
    public function chmod($path, $mode)
    {
        return @chmod($this->normalize($path), $mode);
    }

    /**
     * Check if a file or directory is writable
     *
     * Does some extra checks for safe_mode windows servers. Yuck.
     *
     * @param String $path Path to check
     * @return bool Is writable?
     */
    public function isWritable($path = '/')
    {
        if(! $this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            return true;
        }

        $path = $this->ensurePrefixedPath($this->normalize($path));

        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR == '/') {
            return is_writable($path);
        }

        // For windows servers and safe_mode "on" installations we'll actually
        // write a file then read it.  Bah...
        if ($this->isDir($path)) {
            $path = rtrim($path, '/') . '/' . md5(mt_rand(1, 100) . mt_rand(1, 100));

            if (($fp = @fopen($path, FOPEN_WRITE_CREATE)) === false) {
                return false;
            }

            fclose($fp);
            @chmod($path, DIR_WRITE_MODE);
            @unlink($path);

            return true;
        } elseif (($fp = @fopen($path, FOPEN_WRITE_CREATE)) === false) {
            return false;
        }

        fclose($fp);

        return true;
    }

    /**
     * Returns a hash for a given file and hashing algorithm
     *
     * @param String $algo PHP hashing algorithm, as specified in hash_algos()
     * @param String $path Path to check
     * @return String Hash of file
     */
    public function hashFile($algo, $filename)
    {
        if (! $this->exists($filename)) {
            throw new FilesystemException("File does not exist: {$filename}");
        }

        return hash($algo, $this->read($filename));
    }

    /**
     * Returns the amount of free bytes at a given path
     *
     * @param   String  $path   Path to check
     * @return  Mixed   Number of bytes as a float, or FALSE on failure
     */
    public function getFreeDiskSpace($path = '/')
    {
        if(!$this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            return null;
        }

        return @disk_free_space($path);
    }

    /**
     * include() a file
     *
     * @param   string  $filename   Full path to file to include
     */
    public function include_file($filename)
    {
        include_once($filename);
    }

    /**
     * Given a path this returns a unique filename by appending "_n" (where "n"
     * is a number) if a file by the same name already exists, i.e. "image002_1.jpg".
     *
     * @param String $path Path to make unique
     * @return string The path to the file.
     */
    public function getUniqueFilename($path)
    {
        $path = $this->normalize($path);

        // The path is good! We're done here.
        if (! $this->exists($path)) {
            return $path;
        }

        $i = 0;
        $extension = $this->extension($path);
        $dirname =  $this->dirname($path) . DIRECTORY_SEPARATOR;
        $filename = $this->filename($path);

        // Glob only works with local filesytem but is more performant than filtering directory results
        if ($this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            $files = array_map(function($file) {
                return $this->filename($file);
            }, glob($dirname . $filename . '_*' . $extension));
        }else{
            // Filter out any files that do not start with our filename
            $files = array_filter($this->getDirectoryContents($dirname), function($file) use($filename) {
                return strpos($file, "{$filename}_") === 0;
            });
        }

        // If we do not have any matching files at this point it gets the _1 suffix
        if(empty($files)) {
            return $dirname . "{$filename}_1.{$extension}";
        }

        // Try to figure out if we already have a file we've renamed, then
        // we can pick up where we left off, and reduce the guessing.
        rsort($files, SORT_NATURAL);

        foreach ($files as $file) {
            $number = str_replace(array($filename, $extension), '', $file);
            if (substr_count($number, '_') == 1 && strpos($number, '_') === 0) {
                $number = str_replace('_', '', $number);
                if (is_numeric($number)) {
                    $i = (int) $number;
                    break;
                }
            }
        }

        $uniqueName = '';

        do {
            $i++;
            $uniqueName = $filename . '_' . $i . '.' . $extension;
        } while (in_array($uniqueName, $files));

        return $dirname . $uniqueName;
    }

    /**
     * Finds string and replaces it
     * @param  string $file
     * @param  string $search
     * @param  string $replace
     * @return void
     */
    public function findAndReplace($file, $search, $replace)
    {
        if (!$this->exists($file)) {
            return;
        }

        // If we're given a directory iterate over the files and recursively call findAndReplace()
        if ($this->isDir($file)) {
            foreach ($this->getDirectoryContents($file) as $file) {
                $this->findAndReplace($file, $search, $replace);
            }

            return;
        }

        $contents = $this->read($file);

        if (strpos($search, '/') === 0) {
            $contents = preg_replace($search, $replace, $contents);
        } else {
            $contents = str_replace($search, $replace, $contents);
        }

        $this->write($file, $contents, true);
    }

    /**
     * Add EE's default index file to a directory
     */
    public function addIndexHtml($dir)
    {
        $dir = rtrim($dir, '/');
        $dir = $this->normalizeRelativePath($dir);

        if (! $this->isDir($dir)) {
            throw new FilesystemException("Cannot add index file to non-existent directory: {$dir}");
        }

        if (! $this->isFile($dir . '/index.html')) {
            $this->write($dir . '/index.html', 'Directory access is forbidden.');
        }
    }

    /**
     * Writing files and directories should respect the write modes
     * specified. Otherwise on some crudy hosts you end up unable
     * to change those files via FTP.
     *
     * @param String $path Path to ensure access to
     */
    public function ensureCorrectAccessMode($path)
    {
        // This function is only relevant to Local filesystems
        if(!$this->flysystem->getAdapter() instanceof Flysystem\Adapter\Local) {
            return;
        }

        if ($this->isDir($path)) {
            $this->chmod($path, DIR_WRITE_MODE);
        } else {
            $this->chmod($path, FILE_WRITE_MODE);
        }
    }

    protected function normalizeAbsolutePath($path)
    {
        return str_replace('//', '/', implode([
            in_array(substr($path, 0, 1), ['/', '\\']) ? '/' : '',
            Flysystem\Util::normalizePath($path),
            in_array(substr($path, -1), ['/', '\\']) ? '/' : ''
        ]));
    }

    protected function ensurePrefixedPath($path)
    {
        $adapter = $this->flysystem->getAdapter();
        $normalized = $this->normalizeAbsolutePath($path);
        $prefix = rtrim($this->getPathPrefix(), '\\/');

        if (strpos($normalized, $prefix) === 0) {
            return $normalized;
        }

        return $adapter->applyPathPrefix(Flysystem\Util::normalizePath($path));
    }

    protected function getPathPrefix()
    {
        return $this->normalizeAbsolutePath($this->flysystem->getAdapter()->getPathPrefix());
    }

    protected function removePathPrefix($path)
    {
        $prefix = $this->getPathPrefix();
        return (strpos($path, $prefix) === 0) ? str_replace($prefix, '', $path) : $path;
    }

    protected function normalizeRelativePath($path)
    {
        $path = $this->normalizeAbsolutePath($path);
        return ltrim($this->removePathPrefix($path), '\\/');
    }

    /**
     * Normalize the path for a native function call
     *
     * This is used by classes that extend this one to, for example, root
     * the filesystem in a specific location. It can also be used for sanity
     * checks, but beware that it is slow.
     */
    protected function normalize($path)
    {
        return $path;
    }

    public function getBaseAdapter()
    {
        $adapter = $this->flysystem->getAdapter();

        return ($adapter instanceof Flysystem\Cached\CachedAdapter) ? $adapter->getAdapter() : $adapter;
    }
}

// EOF
