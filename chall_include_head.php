<?php

use GDO\Core\Application;
use GDO\Core\Debug;
use GDO\Core\Logger;
use GDO\Core\ModuleLoader;
use GDO\Core\Website;
use GDO\DB\Database;
use GDO\Language\Trans;
use GDO\Session\GDO_Session;
use GDO\TBS\GDO_TBS_Challenge;
use GDO\UI\GDT_Error;
use GDO\User\GDO_User;

require 'GDO7.php';
require 'protected/config.php';

Database::init();
new ModuleLoader(GDO_PATH . 'GDO/');
GDO_Session::init(GDO_SESS_NAME, GDO_SESS_DOMAIN, GDO_SESS_TIME, !GDO_SESS_JS, GDO_SESS_HTTPS);
Application::init();
ModuleLoader::instance()->loadModulesCache();

# Bootstrap
Trans::setISO(GDO_LANGUAGE);
Logger::init(null, GDO_ERROR_LEVEL); # 1st init as guest
Debug::init();
Debug::enableErrorHandler();
Debug::enableExceptionHandler();
Debug::setDieOnError(GDO_ERROR_DIE);
Debug::setMailOnError(GDO_ERROR_MAIL);
GDO_Session::instance();
?>
    <!DOCTYPE html>
    <html>
<head>
    <title>TBS Challenge</title>
	<?=Website::displayHead()?>
	<?=Website::displayMeta()?>
	<?=Website::displayLink()?>
</head>
<?php
if (!GDO_User::current()->isMember())
{
	echo GDT_Error::responseWith('err_members_only')->render();
}
else
{
	$chall = GDO_TBS_Challenge::getChallenge(TBS_CHALL_CATEGORY, TBS_CHALL_TITLE);
}
