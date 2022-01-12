<?=ellipsize($comment->comment, 50)?><br>
<span class="meta-info">&mdash; <?=lang('by')?>: <a href="mailto:<?=$comment->email?>"><?=$comment->name?></a>, <?=lang('on')?>: <a href="<?=ee('CP/URL', '/publish/comments/entry/' . $comment->getEntry()->getId())?>"><?=$comment->getEntry()->title?></a></span>
