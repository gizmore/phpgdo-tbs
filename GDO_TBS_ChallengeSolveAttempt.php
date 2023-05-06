<?php
namespace GDO\TBS;

use GDO\Core\Application;
use GDO\Core\GDO;
use GDO\Core\GDT_CreatedAt;
use GDO\Date\Time;
use GDO\User\GDO_User;
use GDO\User\GDT_User;

/**
 * Record solving attempts to prevent bruteforce.
 *
 * @author gizmore
 */
final class GDO_TBS_ChallengeSolveAttempt extends GDO
{

	public static function getTimeout(GDO_User $user)
	{
		$max = self::getMaxAttempts();
		$amt = self::getAttempts($user);
		if ($amt >= $max)
		{
			$last = self::getOldestAttemptInFrame($user);
			$diff = Time::getDiff($last->getDate());
			return Time::humanDuration($diff);
		}
		else
		{
			return false;
		}
	}

	private static function getMaxAttempts()
	{
		return Module_TBS::instance()->cfgSolveAttempts();
	}

	private static function getAttempts(GDO_User $user)
	{
		return self::table()->select('COUNT(*)')->
		where("csa_user={$user->getID()}")->
		where('csa_date >' . quote(self::getTimeCut()))->
		exec()->fetchVar();
	}

	private static function getTimeCut()
	{
		return Application::$TIME - self::getTimeframe();
	}

	private static function getTimeframe()
	{
		return Module_TBS::instance()->cfgSolveTimeout();
	}

	###############
	### Private ###
	###############

	/**
	 * @param GDO_User $user
	 *
	 * @return GDO_TBS_ChallengeSolveAttempt
	 */
	private static function getOldestAttemptInFrame(GDO_User $user)
	{
		return self::table()->select()->
		where("csa_user={$user->getID()}")->
		where('csa_date >' . quote(self::getTimeCut()))->
		first()->exec()->fetchObject();
	}

	public function getDate() { return $this->gdoVar('csa_date'); }

	public static function tried(GDO_User $user, GDO_TBS_Challenge $challenge)
	{
		return self::blank([
			'csa_user' => $user->getID(),
			'csa_challenge' => $challenge->getID(),
		])->insert();
	}

	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_User::make('csa_user'),
			GDT_TBS_Challenge::make('csa_challenge'),
			GDT_CreatedAt::make('csa_date'),
		];
	}

}
