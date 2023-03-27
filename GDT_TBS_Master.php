<?php
namespace GDO\TBS;

use GDO\Core\GDO;
use GDO\Core\GDT;
use GDO\UI\GDT_Container;
use GDO\User\GDO_User;

/**
 * A site master icon.
 */
final class GDT_TBS_Master extends GDT_Container
{

	protected function __construct()
	{
		parent::__construct();
		$this->horizontal();
	}

	public function gdo(?GDO $gdo): GDT
	{
		$this->removeFields();
		$this->addMasterIcons($gdo);
		return parent::gdo($gdo);
	}

	private function addMasterIcons(GDO_User $user): void
	{
		foreach (GDT_TBS_ChallengeCategory::$CATS as $cat)
		{
			$this->addField(
				GDT_TBS_GroupmasterIcon::make()->category($cat)->gdo($user));
		}
	}

}
