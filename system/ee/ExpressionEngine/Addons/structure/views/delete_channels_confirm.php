<?=form_open($action_url, $attributes)?>

<div class="ee7 box panel">
    <div class="panel-heading">
        <h1>Remove Channel(s) from Structure</h1>
    </div>
    <div class="padder panel-body">
        <input type="hidden" name="channel_ids" value="<?=$to_be_deleted?>">
        <div class="notice-text">Are you sure you want to permanently remove these channels from Structure?</div>
        <br />

        <ul class="plain">
        <?php foreach ($channel_titles as $channel) : ?>
            <li><strong><?=$channel['channel_title']; ?></strong></li>
        <?php endforeach; ?>
        </ul>

        <div class="note">
            Setting these channels to "Unmanaged" will <strong>remove every page created with them</strong> from the Structure nav tree. This will also remove any child pages under them. Make sure this is what you want to do before proceeding.
        </div>
    </div>
    <div class="panel-footer">
        <div class="form-btns">
            <?=form_submit(array('name' => 'submit', 'value' => "Yes I Know What I'm Doing, Remove Them!", 'class' => 'submit btn action'))?>
            <a href="<?=$base_url;?>" style="margin-left:10px;">Cancel</a>
        </div>
    </div>
</div>
<?=form_close()?>