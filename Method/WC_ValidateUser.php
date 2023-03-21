<?php
namespace GDO\TBS\Method;

use GDO\Core\Application;
use GDO\Core\Method;
use GDO\Mail\GDT_Email;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * WC API for validating user emails.
 * This script shall simply return 1 or 0.
 *
 * @version 7.0.2
 * @since 6.10
 * @author gizmore
 */
final class WC_ValidateUser extends Method
{

	public function isTrivial(): bool
	{
		return true;
	}

	public function gdoParameters(): array
	{
		return [
			GDT_User::make('user')->notNull(),
			GDT_Email::make('email')->notNull(),
		];
	}

	public function execute()
	{
		$user = $this->getUser();
		$mail = $this->gdoParameterVar('email');
		$code = (int)($user->getMail() === $mail);
		echo "{$code}\n";
		Application::exit($code);
	}

	private function getUser(): GDO_User
	{
		return $this->gdoParameterValue('user');
	}

}
