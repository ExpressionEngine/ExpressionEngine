{!-- Write your tag logic here, accessing PHP variables from the getVariables() function of the generator --}
{exp:{{addon_shortname}}:tag channel="<?=$channel?>" numbers="<?=implode('|', $numbers);?>" color="<?=$color?>"}
    {title} - {path=<?=$template_group?>/entry/{url_title}}
{/exp:{{addon_shortname}}:tag}
