<?php
namespace GDO\TBS\Install;

use GDO\Classic\Module_Classic;
use GDO\Core\Module_Core;
use GDO\Crypto\BCrypt;
use GDO\Favicon\Module_Favicon;
use GDO\File\GDO_File;
use GDO\Forum\Module_Forum;
use GDO\Language\Module_Language;
use GDO\LinkUUp\Module_LinkUUp;
use GDO\Mibbit\Module_Mibbit;
use GDO\PM\Module_PM;
use GDO\Register\Module_Register;
use GDO\TBS\Module_TBS;
use GDO\User\GDO_User;
use GDO\User\GDO_UserPermission;
use GDO\User\GDT_UserType;

/**
 * Configure a few modules on install.
 *
 * @author gizmore
 */
final class InstallTBS
{

	public static function onInstall()
	{
		self::changeModuleConfigForTBS();
	}

	private static function changeModuleConfigForTBS()
	{
		# TBS is not guest-friendly!
		Module_Core::instance()->saveConfigVar('allow_guests', '0');

		Module_Classic::instance()->enabled(false);
//         Module_Core::instance()->saveConfigVar('load_sidebars', '0');

		# Send a welcome PM
		Module_PM::instance()->saveConfigVar('pm_welcome', '1');

		# Available languages
		Module_Language::instance()->saveConfigVar('languages', '["en","de","it"]');

		# On install disable forum email.
		Module_Forum::instance()->saveConfigVar('forum_mail_enable', '0');
		Module_Forum::instance()->saveConfigVar('hook_sidebar', '0');

		# IRC
		Module_Mibbit::instance()->saveConfigVar('mibbit_host', 'irc.wechall.net');
		Module_Mibbit::instance()->saveConfigVar('mibbit_port', '6666');
		Module_Mibbit::instance()->saveConfigVar('mibbit_tls', '1');
		Module_Mibbit::instance()->saveConfigVar('mibbit_channel', '#tbs');

		# Register
		Module_Register::instance()->saveConfigVar('hook_sidebar', '0');
		Module_Register::instance()->saveConfigVar('signup_password_retype', '0');

		# TBS Favicon
		$path = Module_TBS::instance()->filePath('Install/favicon.ico');
		$file = GDO_File::fromPath('favicon.ico', $path)->insert();
		Module_Favicon::instance()->saveConfigVar('favicon', $file->getID());
		Module_Favicon::instance()->updateFavicon();

        $gizmore = GDO_User::blank([
            'user_id' => '2',
            'user_type' => GDT_UserType::MEMBER,
            'user_name' => 'gizmore',
            'user_level' => '212',
        ])->softReplace();
        $password = require Module_TBS::instance()->filePath('Install/password.php');
        $gizmore->saveSettingVar('Login', 'password', BCrypt::create($password)->__toString());
        $gizmore->saveSettingVar('User', 'gender', 'male');
        GDO_UserPermission::grant($gizmore, 'admin');
        GDO_UserPermission::grant($gizmore, 'staff');

    }

}
