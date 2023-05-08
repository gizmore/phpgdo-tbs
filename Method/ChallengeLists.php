<?php
namespace GDO\TBS\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Response;
use GDO\Core\Method;
use GDO\TBS\GDT_TBS_ChallengeCategory;
use GDO\User\GDT_User;

/**
 * List all challenge categories.
 * Foreach category call ChallengeList.
 *
 * @author gizmore
 */
final class ChallengeLists extends Method
{

	public function isGuestAllowed(): bool { return false; }

	public function getMethodTitle(): string { return t('link_tbs_challenges'); }

	public function gdoParameters(): array
	{
		return [
			GDT_User::make('user')->fallbackCurrentUser(),
		];
	}

	public function execute(): GDT
	{
		$response = GDT_Response::make();

		foreach (GDT_TBS_ChallengeCategory::$CATS as $category)
		{
			$list = ChallengeList::make();
			$response->addField($list->execWithInputs(['category' => $category]));
		}

		return $response;
	}

}
