{exp:structure:nav_basic show_depth="4"}
{if root:count == 1}
<ul>
{/if}
  <li{if root:active} class="active"{/if}>
    <a href="{root:page_url}">{root:title}</a>
    {if root:has_children}
    <ul>
      {root:children}
      <li{if child:active} class="active"{/if}>
        <a href="{child:page_url}">{child:title}</a>
        {if child:has_children}
        <ul>
          {child:children}
          <li{if grandchild:active} class="active"{/if}>
            <a href="{grandchild:page_url}">{grandchild:title}</a>
            {if grandchild:has_children}
            <ul>
              {grandchild:children}
              <li{if great_grandchild:active} class="active"{/if}>
                <a href="{great_grandchild:page_url}">{great_grandchild:title}</a>
              </li>
              {/grandchild:children}
            </ul>
            {/if}
          </li>
          {/child:children}
        </ul>
        {/if}
      </li>
      {/root:children}
    </ul>
    {/if}
  </li>
{if root:count == root:total_results}
</ul>
{/if}
{/exp:structure:nav_basic}