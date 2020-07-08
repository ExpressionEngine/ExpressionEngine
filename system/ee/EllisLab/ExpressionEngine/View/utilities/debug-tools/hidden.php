
<div class="box">
    <h1>Danger! Here be Dragons! This menu can do harm. Use with care.</h1>

    <div class="md-wrap">
        <h2><a href='<?= $flux->moduleUrl('deleteWhereNotCurrentSite') ?>'>Delete from database everything that is not the current side id</a></h2>
        <h2><a href='<?= $flux->moduleUrl('cleanOrphanedChannelData') ?>'>Clean orphaned channel data</a></h2>
        <h2><a href='<?= $flux->moduleUrl('deleteMemberGroupsIfNoChannelPermissions') ?>'>Delete all member groups with no channel permissions</a> - excludes super admin, banned, guest, pending, members</h2>
        <h2><a href='<?= $flux->moduleUrl('convertSmartdownToMarkdown') ?>'>Convert smartdown to markdown</h2>
        <h2><a href='<?= $flux->moduleUrl('cleanOrphanedMembers') ?>'>Clean orphaned members</h2>
    </div>
</div>


