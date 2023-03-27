<?php
declare(strict_types=1);
namespace GDO\TBS\Method;

use GDO\Core\GDO;
use GDO\Core\GDT_Virtual;
use GDO\Core\WithFileCache;
use GDO\Country\GDT_Country;
use GDO\DB\Query;
use GDO\Table\GDT_PageMenu;
use GDO\Table\MethodQueryTable;
use GDO\TBS\GDO_TBS_ChallengeSolvedCategory;
use GDO\TBS\GDT_TBS_ChallengeCategory;
use GDO\TBS\GDT_TBS_GroupmasterIcon;
use GDO\TBS\GDT_TBS_Rank;
use GDO\User\GDO_User;
use GDO\User\GDT_Level;
use GDO\User\GDT_ProfileLink;

/**
 * TBS ranking table.
 *
 * @author gizmore
 */
final class Ranking extends MethodQueryTable
{

	use WithFileCache;

	public function isOrdered(): bool { return false; }

	public function isFiltered(): bool { return false; }

	public function getDefaultOrder(): ?string { return 'user_level DESC'; }

	public function getDefaultIPP(): int { return 100; }

	public function getMethodTitle(): string
	{
		return t('mt_tbs_ranking');
	}

	public function gdoTable(): GDO
	{
		return GDO_TBS_ChallengeSolvedCategory::table();
	}

	public function getQuery(): Query
	{
		return $this->gdoTable()->select()->
		joinObject('csc_user')->
		where('user_type="member"');
	}

	public function gdoFetchAs(): GDO { return GDO_User::table(); }

	public function gdoHeaders(): array
	{
		$page = $this->getPage();
		$ipp = $this->getIPP();
		$from = GDT_PageMenu::getFromS($page, $ipp);
		return [
			GDT_TBS_Rank::make('rank')->startRank($from),
			GDT_Virtual::make()->gdtType(GDT_Country::make('country'))->subquery("SELECT uset_value FROM gdo_usersettings WHERE uset_user=gdo_user.value AND uset_key='country'"),
			GDT_ProfileLink::make('username')->nickname(),
			GDT_Level::make('user_level')->label('solved'),
			$this->groupmasterIcon(0),
			$this->groupmasterIcon(1),
			$this->groupmasterIcon(2),
			$this->groupmasterIcon(3),
			$this->groupmasterIcon(4),
			$this->groupmasterIcon(5),
			$this->groupmasterIcon(6),
			$this->groupmasterIcon(7),
			$this->groupmasterIcon(8),
			$this->groupmasterIcon(9),
			$this->groupmasterIcon(10),
			$this->groupmasterIcon(11),
			$this->groupmasterIcon(12),
		];
	}

	private function groupmasterIcon($id)
	{
		return GDT_TBS_GroupmasterIcon::make()->
		category(GDT_TBS_ChallengeCategory::$CATS[$id]);
	}

}
