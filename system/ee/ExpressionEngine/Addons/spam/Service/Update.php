<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Addons\Spam\Service;

/**
 * Spam Update
 */
class Update
{
    public function download()
    {
        $location = 'https://expressionengine.com/asset/file/spam.zip';

        try {
            $compressed = ee('Curl')->get($location)->exec();
        } catch (\Exception $e) {
            // let's just bubble this up
            throw $e;
        }

        // Write the training data to a tmp file and return the file name

        $handle = fopen($this->path() . "spam.zip", "w");
        fwrite($handle, $compressed);
        fclose($handle);
        $zip = new \ZipArchive();

        if ($zip->open($this->path() . "spam.zip") === true) {
            $zip->extractTo($this->path());
            $zip->close();
            unlink($this->path() . "spam.zip");
        } else {
            return false;
        }

        return true;
    }

    public function prepare()
    {
        $path = $this->path() . "training/prepare.sql";
        $prep = file_get_contents($path);
        ee()->db->query($prep);
    }

    public function updateParameters($limit = 500)
    {
        $path = $this->path() . "training/parameters.sql";
        $lines = array_filter(file($path));
        $parameters = implode(',', array_slice($lines, 0, $limit));
        $remaining = implode("", array_slice($lines, $limit));

        $sql = "INSERT INTO exp_spam_parameters VALUES $parameters";

        ee()->db->query($sql);

        if (empty($remaining)) {
            return false;
        }

        file_put_contents($path, $remaining);

        return true;
    }

    public function updateVocabulary($limit = 500)
    {
        $path = $this->path() . "training/vocabulary.sql";
        $lines = array_filter(file($path));
        $vocabulary = implode(',', array_slice($lines, 0, $limit));
        $remaining = implode("", array_slice($lines, $limit));

        $sql = "INSERT INTO exp_spam_vocabulary VALUES $vocabulary";

        ee()->db->query($sql);

        if (empty($remaining)) {
            return false;
        }

        file_put_contents($path, $remaining);

        return true;
    }

    private function path()
    {
        $cache_path = ee()->config->item('cache_path');

        if (empty($cache_path)) {
            $cache_path = SYSPATH . 'user' . DIRECTORY_SEPARATOR . 'cache/';
        }

        $cache_path .= 'spam/';

        if (! is_dir($cache_path)) {
            mkdir($cache_path, DIR_WRITE_MODE);
            @chmod($cache_path, DIR_WRITE_MODE);
        }

        return $cache_path;
    }
}

// EOF
