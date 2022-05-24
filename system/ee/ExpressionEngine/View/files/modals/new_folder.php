<?=form_open($form_url, '', [])?>

    <h2><?= lang('new_folder') ?></h2>

    <p><?= lang('create_folder_location') ?></p>

    <p>
        <label for="upload_location"><?= lang('location') ?></label>
    </p>

    <p>
        <select name="upload_location" id="upload_location">
            <?php foreach ($destinations as $id => $name):?>
            <option value='<?= $id ?>'><?= $name ?></option>
            <?php endforeach;?>
        </select>
    </p>

    <p>
        <label for="folder_name"><?= lang('folder_name') ?></label>
        <input type="text" name="folder_name">
    </p>

    <input type="submit" name="Save">
</form>




