<?php
namespace GDO\TBS\tpl\ajax;
use GDO\User\GDT_ProfileLink;
use GDO\TBS\Method\Heartbeat;
use GDO\User\GDO_User;

$users = Heartbeat::make()->getOnlineUsers();

$guestcount = 0;
$onlineUsers = '';

$c = GDO_User::table()->cache;
foreach ($users as $user)
{
    $user = $c->getDummy()->setGDOVars($user);
    if ($user->isAnon())
    {
        $guestcount++;
    }
    else
    {
        $profileLink = GDT_ProfileLink::make()->nickname()->user($user);
        $onlineUsers .= sprintf("<div>%s<b>%s</b></div>\n",
            $profileLink->render(), $user->gdoVar('user_level'));
    }
}
?>
<div id="tbs-online-list">
  <?=$onlineUsers?>
  <div id="tbs-anonymous"><?=t('tbs_guestcount', [$guestcount])?></div>
</div>
