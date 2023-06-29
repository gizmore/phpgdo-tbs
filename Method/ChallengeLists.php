<?php
namespace GDO\TBS\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Response;
use GDO\Core\Method;
use GDO\TBS\GDT_TBS_ChallengeCategory;
use GDO\UI\GDT_Accordeon;
use GDO\UI\GDT_Panel;
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

        $response->addField(GDT_Accordeon::makeWith(GDT_Panel::make()->text('tbs_help_challenge_tt'))->title('tbs_help_challenge_t'));


		foreach (GDT_TBS_ChallengeCategory::$CATS as $category)
		{
			$list = ChallengeList::make();
			$response->addField($list->execWithInputs(['category' => $category]));
		}

		return $response;
	}

}
