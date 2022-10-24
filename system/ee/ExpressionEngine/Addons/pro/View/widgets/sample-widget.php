{widget title="Demo dashboard widget" width="half"}

<p>Random entry: {exp:channel:entries dynamic="no" orderby="random" limit="1"}<a href="{cp_url}?/cp/publish/edit/entry/{entry_id}&S={cp_session_id}">{title}</a>{/exp:channel:entries}</p>

<p>To see this code please visit the template <a href="{cp_url}?/cp/design/template/edit/TMPL_ID&S={cp_session_id}">pro-dashboard-widgets/sample-widget</a>.</p>
