<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Queue
{
    public function get()
    {
        $job = ee('Model')->get('queue:Job')->all();
    }
}
