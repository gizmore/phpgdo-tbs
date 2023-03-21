<?php
namespace GDO\TBS;

use GDO\Core\GDO;
use GDO\Core\GDT_UInt;
use GDO\User\GDO_User;

/**
 * Rank column and utility.
 *
 * @author gizmore
 */
final class GDT_TBS_Rank extends GDT_UInt
{

	public $rank = 1;
	public $startRank = 1;

	public static function getRankForUser(GDO_User $user)
	{
		return 1;
	}

	public function startRank($startRank)
	{
		$this->rank = $startRank;
		$this->startRank = $startRank;
		return $this;
	}

	public function gdo(GDO $gdo = null): self
	{
		$this->var($this->rank++);
		return $this;
	}

	public function renderTHead(): string
	{
		return t('rank');
	}

}
