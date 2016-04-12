<?php

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

use EllisLab\ExpressionEngine\Service\File\Directory;

class Factory {

    /**
     * @var Int Site id used for import
     */
    private $site_id;

    /**
     * @param Int $site_id Current site
     */
    public function __construct($site_id)
    {
        $this->site_id = $site_id;
    }

    /**
     * Create a zip for a channel
     *
     * @param Array $channels Array/collection of channels
     * @return String Path to the zip file
     */
    public function export($channels)
    {
        $export = new Export();
        return $export->zip($channels);
    }

    /**
     * Create a set object from the contents of an item in the $_FILES array
     *
     * @param Array $upload Element in the $_FILES array
     * @return Set Channel set object
     */
    public function importUpload(array $upload)
    {
        $location = $upload['tmp_name'];
        $name = $upload['name'];

        $extractor = new ZipToSet($location);

        $set = $extractor->extractAs($name);
        $set->setSiteId($this->site_id);

		return $set;
    }

    /**
     * Create a set object from a directory
     *
     * @param String $dir Path to the channel set directory
     * @return Set Channel set object
     */
    public function importDir($dir)
    {
		$set = new Set($dir);
        $set->setSiteId($this->site_id);

        return $set;
    }
}
