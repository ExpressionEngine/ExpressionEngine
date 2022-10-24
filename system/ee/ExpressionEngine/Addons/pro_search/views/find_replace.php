<div id="pro-find-replace" class="panel box">

    <h1 class="panel-heading"><?=lang('find_replace')?></h1>

    <div class="panel-body">

    <?=form_open($action)?> <!-- preview=yes -->

        <div class="pro-tabs col w-4" data-names="legend">
            <div class="pro-tabs-pages">

                <fieldset class="pro-tab active">
                    <legend><?=lang('channels')?></legend>

                    <div class="pro-boxes">
                        <label><input type="checkbox" class="pro-select-all" /> <?=lang('select_all')?></label>
                    </div>

                    <?php foreach ($channels as $channel_id => $row) : ?>
                    <div class="pro-boxes">
                        <h4><span><?=htmlspecialchars($row['channel_title'])?></span></h4>
                        <?php foreach ($row['fields'] as $field_id => $field_name) : ?>
                            <label>
                                <input type="checkbox" name="fields[<?=$channel_id?>][]" value="<?=$field_id?>" />
                                <?=htmlspecialchars($field_name)?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <?php endforeach; ?>

                </fieldset>

                <?php if ($categories) : ?>
                    <fieldset class="pro-tab">
                        <legend><?=lang('categories')?></legend>

                        <div class="pro-boxes">
                            <label><input type="checkbox" class="pro-select-all" /> <?=lang('select_all')?></label>
                        </div>

                        <?php foreach ($categories as $group_id => $row) : ?>
                        <div class="pro-boxes">
                            <h4><span><?=htmlspecialchars($row['group_name'])?></span></h4>
                            <?php foreach ($row['cats'] as $cat_id => $cat) : ?>
                                <label>
                                    <?=$cat['indent']?>
                                    <input type="checkbox" name="cats[]" value="<?=$cat_id?>" />
                                    <?=$cat['name']?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>

                    </fieldset>
                <?php endif; ?>

            </div> <!-- .pro-tabs-pages -->
        </div> <!-- .pro-sidebar -->

        <div class="pro-find col w-12">
            <fieldset class="pro-inline-form">
                <input type="text" id="pro-keywords" name="keywords" autocomplete="off" placeholder="Find text" />
                <input type="submit" class="btn" value="<?=lang('show_preview')?>" />
            </fieldset>
        </div>

    </form>

    <div class="pro-dynamic-content col w-12">
        <?php if (isset($feedback)) {
            include(PATH_ADDONS . '/pro_search/views/ajax_replace_feedback.php');
        } ?>
        <?php if (isset($preview)) {
            include(PATH_ADDONS . '/pro_search/views/ajax_preview.php');
        } ?>
    </div>

    </div>

</div>
