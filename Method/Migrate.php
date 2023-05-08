<?php
declare(strict_types=1);
namespace GDO\TBS\Method;

use GDO\Captcha\GDT_Captcha;
use GDO\Core\GDT;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_Validator;
use GDO\Form\MethodForm;
use GDO\Mail\GDT_Email;
use GDO\Net\GDT_Url;
use GDO\Net\HTTP;
use GDO\Recovery\GDO_UserRecovery;
use GDO\TBS\Module_TBS;
use GDO\User\GDO_User;
use GDO\User\GDT_User;
use GDO\User\GDT_Username;
use GDO\User\GDT_UserType;
use GDO\Util\Common;

/**
 * Recover your TBS account via WeChall authentication.
 *
 * 1. Call WeChall. It will send an email to your wechall mail.
 * 2. Click link in migration mail.
 * 3. Change your password via Recovery module.
 *
 * @version 7.0.3
 * @since 6.10.2
 * @author gizmore
 */
final class Migrate extends MethodForm
{

	public function isUserRequired(): bool { return false; }

	public function getUserType(): ?string { return GDT_UserType::GHOST; }

	public function getMethodTitle(): string
	{
		return t('tbs_account_migration_title');
	}

	public function execute(): GDT
	{
		if ($token = Common::getRequestString('token'))
		{
			$tbs = Common::getRequestString('tbs');
			$wechall = Common::getRequestString('wechall');
			$email = Common::getRequestString('email');
			return $this->onMigrate($tbs, $wechall, $email, $token);
		}
		return parent::execute();
	}

	public function onMigrate(string $tbs, string $wechall, string $email, string $token): GDT
	{
		if (!($user = GDO_User::getByLogin($tbs)))
		{
			return $this->error('err_user');
		}
		elseif ($token !== $this->getMigrationToken($tbs, $wechall, $email))
		{
			return $this->error('err_tbs_migrate_token');
		}
		else
		{
			# Initiate recovery
			$token = GDO_UserRecovery::blank(['pw_user_id' => $user->getID()])->replace();
			$href = href('Recovery', 'Change', "&userid={$user->getID()}&token=" . $token->getToken());
			return $this->redirectMessage('msg_tbs_migrate_recovery', null, $href);
		}
	}

	public function getMigrationToken($tbs, $wechall, $email): string
	{
		return $this->getMigrationCrypto($tbs, $wechall, $email);
	}

	##############
	### Crypto ###
	##############

	public function getMigrationCrypto(string $tbs, string $wechall, string $email): string
	{
		$auth = Module_TBS::instance()->cfgXAuthKey();
		$user = GDO_User::findBy('user_name', $tbs);
		$hash = $user->gdoHashcode();
		return md5(sha1(GDO_SALT . $hash . GDO_SALT . $email . GDO_SALT . $auth . GDO_SALT));
	}

	protected function createForm(GDT_Form $form): void
	{
		$form->text('tbs_migration_info');
		$tu = GDT_User::make('tbs_user')->notNull()->label('tbs_username');
		$form->addFields(
			$tu,
			GDT_Email::make('wechall_mail')->notNull()->label('wechall_email'),
			GDT_Username::make('wechall_name')->notNull()->label('wechall_username'),
			GDT_Validator::make()->validator($form, $tu, [$this, 'validateAlreadyActive']),
			GDT_AntiCSRF::make(),
		);
		if (module_enabled('Captcha'))
		{
			$form->addField(GDT_Captcha::make());
		}
		$form->actions()->addField(GDT_Submit::make());
	}

	public function formValidated(GDT_Form $form): GDT
	{
		$user = $form->getFormValue('tbs_user');
		$tbs = $user->gdoVar('user_name');
		$wechall = $form->getFormVar('wechall_name');
		$email = $form->getFormVar('wechall_mail');
		$url = $this->getMigrationURL($tbs, $wechall, $email);
		$response = HTTP::getFromURL($url);
		switch ($response)
		{
			case 'msg_mail_sent':
				return $this->message('msg_tbs_migrate_mail_sent');

			default:
				return
					$this->error('err_tbs_wc_migrate', [html($response)])->
					addField($this->renderPage());
		}
	}

	##############
	### Step 1 ###
	##############

	/**
	 * Build the wechall url for a migration request.
	 */
	public function getMigrationURL(string $tbs, string $wechall, string $email): string
	{
		$host = 'https://www.wechall.net';
		return sprintf(
			'%s/index.php?mo=WeChall&me=TBSMigration&tbs=%s&wc=%s&email=%s&token=%s&xauth=%s&host=%s',
			$host,
			urlencode($tbs),
			urlencode($wechall),
			urlencode($email),
			urlencode($this->getMigrationToken($tbs, $wechall, $email)),
			urlencode(Module_TBS::instance()->cfgXAuthKey()),
			urlencode(GDT_Url::protocol() . '://' . $_SERVER['HTTP_HOST']),
		);
	}

	##############
	### Step 2 ###
	##############

	public function validateAlreadyActive(GDT_Form $form, GDT $field, $value): bool
	{
		/** @var GDO_User $value **/
		if ($value && $value->gdoVar('user_password'))
		{
			return $field->error('err_tbs_migrate_not_needed');
		}
		return true;
	}

}
