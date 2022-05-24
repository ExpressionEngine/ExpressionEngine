<?php

namespace ExpressionEngine\Library\Filesystem\Adapter;

use ExpressionEngine\Dependency\League\Flysystem;

class Local extends Flysystem\Adapter\Local {

    public function __construct($settings) {
        parent::__construct($settings['path']);
    }

}