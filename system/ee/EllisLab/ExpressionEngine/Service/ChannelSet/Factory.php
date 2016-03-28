<?php

namespace EllisLab\ExpressionEngine\Service\ChannelSet;

use EllisLab\ExpressionEngine\Service\File\Directory;

class Factory {

    public function importUpload(array $upload)
    {
        $location = $upload['tmp_name'];
        $name = $upload['name'];

        $importer = new ZipToSet($location);

		return $importer->extractAs($name);
    }

    public function importDir($dir)
    {
		return new Set($dir);
    }
}
