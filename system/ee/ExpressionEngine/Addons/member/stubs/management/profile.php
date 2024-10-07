{layout="<?=$template_group?>/_layout"}

{if logged_out}
    {redirect="<?=$template_group?>/login"}
{/if}

{exp:member:custom_profile_data  {if segment_3} member_id="{segment_3}" {/if}}
{layout:set name="title"}{username} Profile{/layout:set}
<a href="{cp_url}?/cp/design/template/edit/{template_id}" target="_blank">View Template</a>

<div class="result">

    <table id="memberprofile" class="tableborder" border="0" cellpadding="3" cellspacing="0" style="width:100%;">
      <thead>
        <tr>
          <td class="memberprofileHead" style="width:25%;"><h3>Key</h3></td>
          <td class="memberprofileHead" style="width:75%;"><h3>Member Data</h3></td>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Username</td>
          <td>{username}</td>
        </tr>
        <tr>
          <td>Email</td>
          <td>{email}</td>
        </tr>
        <tr>
          <td>Screen Name</td>
          <td>{screen_name}</td>
        </tr>
        <tr>
          <td>Member ID</td>
          <td>{member_id}</td>
        </tr>
        <tr>
          <td>Member Group</td>
          <td>{group_title}</td>
        </tr>
        <tr>
          <td>Join Date</td>
          <td>{join_date format="%Y-%m-%d %H:%i:%s"}</td>
        </tr>
        <tr>
          <td>Last Visit</td>
          <td>{last_visit format="%Y-%m-%d %H:%i:%s"}</td>
        </tr>
        <tr>
          <td>Local Time</td>
          <td>{local_time format="%Y-%m-%d %H:%i:%s"}</td>
        </tr>
        {if avatar}
          <tr>
            <td>Avatar</td>
            <td><img src="{avatar_url}" width="{avatar_width}" height="{avatar_height}" alt="{screen_name}'s avatar"></td>
          </tr>
        {/if}

        <?php foreach (array_filter($fields, function ($field) { return $field['show_profile'] === 'y'; }) as $field) : ?>

            <tr>
                <?php if($show_comments ?? false): ?>

                {!-- Field: <?=$field['field_label']?> --}
                {!-- Fieldtype: <?=$field['field_type']?> --}
                {!-- Docs: <?=$field['docs_url']?> --}
                <?php endif; ?>
                <td><?=$field['field_label']?></td>
                <td>
                    <?=$this->embed($field['stub'], $field);?>
                </td>
                <?php if($show_comments ?? false): ?>

                {!-- End field: <?=$field['field_label']?> --}
                <?php endif; ?>
            </tr>

        <?php endforeach; ?>

        <tr>
          <td>Signature</td>
          <td>{signature}</td>
        </tr>
      </tbody>
    </table>

  {/exp:member:custom_profile_data}
</div>
