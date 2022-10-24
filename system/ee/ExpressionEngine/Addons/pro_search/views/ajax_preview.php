<?php if ($total_entries = count($preview)) : ?>
    <?=form_open($form_action, 'id="pro-previewed-entries"', array('encoded_preview' => $encoded_preview))?>

    <div class="pro-replace">

        <fieldset class="pro-inline-form">
            <input type="text" id="pro-replacement" name="replacement" autocomplete="off" placeholder="Replacement" />
            <input type="submit" class="btn" value="<?=lang('replace_selected')?>">
        </fieldset>

        <div class="title-bar title-bar--large">
            <h3 class="title-bar__title"><?=lang('matching_entries_for')?> “<?=$keywords?>”: <?=$total_entries?></h3>
        </div>

        <div class="js-list-group-wrap">
            <?php if ($total_entries > 1) : ?>
                <div class="list-group-controls">
                    <label class="ctrl-all"><span><?=lang('select_all')?></span> <input type="checkbox" class="pro-select-all checkbox--small"></label>
                </div>
            <?php endif ?>
            <div class="js-nestable-categories">
                <ul class="list-group list-group--nested">
                    <?php foreach ($preview as $row) : ?>
                        <li class="js-nested-item">
                            <div class="list-item list-item--action">
                                <a class="list-item__content" href="<?=$row['edit_entry_url']?>">
                                    <div class="list-item__title">
                                        <?=htmlspecialchars($channels[$row['channel_id']]['channel_title'])?>:
                                        <b><?=htmlspecialchars($row['title'])?></b>
                                    </div>
                                    <div class="list-item__secondary">
                                        <dl>
                                            <?php foreach ($row['matches'] as $field_id => $matches) : ?>
                                                <dt><?=$channels[$row['channel_id']]['fields'][$field_id]?>:</dt>
                                                <?php foreach ($matches as $match) : ?>
                                                    <dd>&hellip;<?=$match?>&hellip;</dd>
                                                <?php endforeach; ?>
                                            <?php endforeach; ?>
                                        </dl>
                                    </div>
                                </a>
                                <div class="item list-item__checkbox">
                                    <input type="checkbox" name="entries[<?=$row['channel_id']?>][]" value="<?=$row['entry_id']?>">
                                </div>
                            </div>
                        </li>
                    <?php endforeach ?>
                </ul>
            </div>
        </div>
    </div>
</form>

<?php else : ?>
    <div class="empty no-results">
        <p><?=lang('no_matching_entries_found')?></p>
    </div>

<?php endif; ?>
