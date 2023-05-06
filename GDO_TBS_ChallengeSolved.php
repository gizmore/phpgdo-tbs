<?php
namespace GDO\TBS;

use GDO\Core\GDO;
use GDO\Date\GDT_DateTime;
use GDO\Date\Time;
use GDO\User\GDO_User;
use GDO\User\GDO_UserPermission;
use GDO\User\GDT_User;

/**
 * When did a user solve a challenge?
 *
 * @author gizmore
 */
final class GDO_TBS_ChallengeSolved extends GDO
{

	private static $cacheChallengeCount = [];
	private static $cacheMaxPoints = [];

	public static function hasSolved(GDO_User $user, GDO_TBS_Challenge $chall)
	{
		$row = self::table()->getById($chall->getID(), $user->getID());
		if (!$row)
		{
			return false;
		}
		return !!$row->getSolveDate();
	}

	public function getSolveDate() { return $this->gdoVar('cs_solved_at'); }

	public static function challengeSolved(GDO_TBS_Challenge $challenge, GDO_User $user)
	{
		$row = self::getOrCreate($challenge, $user);
		if (!$row->getSolveDate())
		{
			$row->saveVar('cs_solved_at', Time::getDate());
			GDO_UserPermission::grantPermission($user, $challenge->getPermission());
			$user->changedPermissions(); # recache
		}
		return true;
	}

	public static function getOrCreate(GDO_TBS_Challenge $challenge, GDO_User $user)
	{
		if (!($row = self::get($challenge, $user)))
		{
			$row = self::blank([
				'cs_challenge' => $challenge->getID(),
				'cs_user' => $user->getID(),
				'cs_viewed_at' => Time::getDate(),
			])->insert();
		}
		return $row;
	}

	public static function get(GDO_TBS_Challenge $challenge, GDO_User $user)
	{
		return self::table()->select()->
		where("cs_challenge={$challenge->getID()}")->
		where("cs_user={$user->getID()}")->
		first()->exec()->fetchObject();
	}

	public static function queryNumSolved(GDO_User $user, $category = null)
	{
		$query = self::table()->select('COUNT(*)');
		$query->joinObject('cs_challenge')->where("cs_user={$user->getID()}");
		if ($category)
		{
			$query->where('chall_category=' . quote($category));
		}
		$value = $query->exec()->fetchVar();
		return $value === null ? '0' : $value;
	}

	public static function queryChallengeCount($category = null)
	{
		$catID = 0;
		if ($category)
		{
			$catID = GDT_TBS_ChallengeCategory::getCategoryID($category) + 1;
		}
		if (!isset(self::$cacheChallengeCount[$catID]))
		{
			$query = GDO_TBS_Challenge::table()->select('COUNT(*)');
			if ($category)
			{
				$query->where('chall_category=' . quote($category));
			}
			self::$cacheChallengeCount[$catID] = $query->exec()->fetchVar();
		}
		return self::$cacheChallengeCount[$catID];
	}

	public static function queryNumPoints(GDO_User $user, $category = null)
	{
		$query = self::table()->select('SUM(chall_score)');
		$query->joinObject('cs_challenge')->where("cs_user={$user->getID()}");
		if ($category)
		{
			$query->where('chall_category=' . quote($category));
		}
		$value = $query->exec()->fetchVar();
		return $value === null ? '0' : $value;
	}

	public static function queryMaxPoints($category = null)
	{
		$catID = 0;
		if ($category)
		{
			$catID = GDT_TBS_ChallengeCategory::getCategoryID($category) + 1;
		}
		if (!isset(self::$cacheMaxPoints[$catID]))
		{
			$query = GDO_TBS_Challenge::table()->select('SUM(chall_score)');
			if ($category)
			{
				$query->where('chall_category=' . quote($category));
			}
			self::$cacheMaxPoints[$catID] = $query->exec()->fetchVar();
		}
		return self::$cacheMaxPoints[$catID];
	}

	public function gdoCached(): bool { return false; }

	public function gdoColumns(): array
	{
		return [
			GDT_TBS_Challenge::make('cs_challenge')->primary(),
			GDT_User::make('cs_user')->primary(),
			GDT_DateTime::make('cs_viewed_at'),
			GDT_DateTime::make('cs_solved_at'),
		];
	}

	/**
	 * @return GDO_User
	 */
	public function getUser() { return $this->gdoValue('cs_user'); }

	public function getUserID() { return $this->gdoVar('cs_user'); }

	/**
	 * @return GDO_TBS_Challenge
	 */
	public function getChallenge() { return $this->gdoValue('cs_challenge'); }

	public function getChallengeID() { return $this->gdoVar('cs_challenge'); }

}
