<div class="panel">
    <div class="tbl-ctrls">
        <?=form_open($subForm['base_url'])?>
        <div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
        <div class="panel-heading">
            <div class="title-bar">
                <h3 class="title-bar__title">Bulk subscribe</br></h3>
            </div>
			
			<?= ee('View')->make('ee:_shared/form')->render($subForm); ?>
        </div>
        <?=form_close()?>
    </div>
</div>
