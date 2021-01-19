<?php

    $this->extend('_templates/default-nav', [], 'outer_box');
    $this->embed('_shared/form');

    $modal = ee('View')->make('ee:_shared/modal-form')->render([
        'name' => 'modal-form',
        'contents' => ''
    ]);
    ee('CP/Modal')->addModal('modal-form', $modal);
