<div class="pro-ee7 pro-variables">
    <?php $this->embed('ee:_shared/form'); ?>
</div>

<?php 
    $modal = ee('View')->make('ee:_shared/modal-form')->render([
        'name' => 'modal-form',
        'contents' => ''
    ]);

    ee('CP/Modal')->addModal('modal-form', $modal);
 ?>