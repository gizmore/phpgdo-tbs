<?php
namespace GDO\TBS;

use GDO\Core\GDO;
use GDO\Core\GDT;
use GDO\Core\GDT_UInt;
use GDO\User\GDO_User;

/**
 * Rank column and utility.
 *
 * @author gizmore
 */
final class GDT_TBS_Rank extends GDT_UInt
{

	public int $rank = 1;
	public int $startRank = 1;

	public bool $searchable = false;

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

	public function gdo(?GDO $gdo): static
	{
		$this->var($this->rank++);
		return $this;
	}

	public function renderTHead(): string
	{
		return t('rank');
	}

}
