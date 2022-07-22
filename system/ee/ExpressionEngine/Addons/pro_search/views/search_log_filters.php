<?=form_open($filter_url)?>
    <fieldset>
        <!-- <legend><?=lang('filter_search_log')?></legend> -->
        <input type="text" name="filter[keywords]" placeholder="<?=lang('keywords')?>"
            value="<?=(isset($active['keywords']) ? htmlspecialchars($active['keywords']) : '')?>" />

        <select name="filter[member_id]">
            <option value=""><?=lang('member')?></option>
            <?php foreach ($members as $member_id => $screen_name) : ?>
                <option value="<?=$member_id?>"
                    <?=((isset($active['member_id']) && $active['member_id'] == $member_id) ? 'selected="selected"' : '')?>>
                    <?=htmlspecialchars($screen_name)?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="filter[ip_address]" placeholder="<?=lang('ip_address')?>"
            value="<?=(isset($active['ip_address']) ? htmlspecialchars($active['ip_address']) : '')?>" />

        <select name="filter[search_date]">
            <option value=""><?=lang('search_date')?></option>
            <?php foreach ($dates as $date) : ?>
                <option value="<?=$date?>"
                    <?=((isset($active['search_date']) && $active['search_date'] == $date) ? 'selected="selected"' : '')?>>
                    <?=htmlspecialchars($date)?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="submit" class="btn" value="<?=lang('filter')?>" />

    </fieldset>
</form>
