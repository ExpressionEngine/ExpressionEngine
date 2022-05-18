<?=form_open($form_url, '', [])?>

    <h2>New Folder</h2>

    <p>Create a folder in the following location:</p>

    <label for="upload_location">Location:</label>

    <select name="upload_location" id="upload_location">
        <?php foreach ($destinations as $id => $name):?>
        <option value='<?= $id ?>'><?= $name ?></option>
        <?php endforeach;?>
    </select>


    <label for="folder_name">Folder Name:</label>
    <input type="text" name="folder_name">

    <input type="submit" name="Save">
</form>




