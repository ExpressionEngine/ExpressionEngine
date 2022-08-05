<div class="panel box pro-log">
    <div class="panel-body tbl-ctrls">

        <!-- Action buttons -->
        <?php if (! empty($actions)) : ?>
            <fieldset class="tbl-search right">
            <?php foreach ($actions as $key => $val) : ?>
                <a class="btn tn action" href="<?=$val?>"><?=lang($key)?></a>
            <?php endforeach; ?>
            </fieldset>
        <?php endif; ?>

        <!-- Page title -->
        <h1><?=$cp_page_title?></h1>

        <!-- Inline alerts -->
        <?=ee('CP/Alert')->getAllInlines()?>

        <!-- Status message -->
        <?php if (isset($status)) : ?>
            <p class="pro-log-status"><?=$status?></p>
        <?php endif; ?>

        <!-- Filters form -->
        <?php if (isset($filters)) : ?>
            <div class="pro-log-filters"><?=$filters?></div>
        <?php endif; ?>

        <!-- The data table list -->
        <?php $this->embed('ee:_shared/table', $table); ?>

        <!-- Pagination -->
        <?php if (isset($pagination)) {
            echo $pagination;
        } ?>

        <!-- Bulk actions -->
        <?php if (isset($bulk_action)) : ?>
            <?=form_open($bulk_action)?>
                <fieldset class="tbl-bulk-act">
                    <button type="submit" class="btn remove m-link" rel="modal-remove-all"><?=$bulk_button?></button>
                </fieldset>
            </form>
        <?php endif; ?>

    </div>
</div>

<?php

        if (isset($bulk_action)) :
            $modal = $this->make('ee:_shared/modal_confirm_remove')
                ->render(array(
                    'name'      => 'modal-remove-all',
                    'form_url'  => $bulk_action,
                    'checklist' => array(array(
                        'kind' => $bulk_button,
                        'desc' => lang('all')
                    ))
                ));

            ee('CP/Modal')->addModal('all', $modal);
        endif;
