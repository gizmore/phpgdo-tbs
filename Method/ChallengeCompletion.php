<?php
namespace GDO\TBS\Method;

use GDO\Core\GDO;
use GDO\TBS\GDO_TBS_Challenge;
use GDO\Core\MethodCompletion;

/**
 * Auto completion for challenges.
 *
 * @author gizmore
 * @version 7.0.1
 */
final class ChallengeCompletion extends MethodCompletion
{

	protected function gdoTable() : GDO
	{
		return GDO_TBS_Challenge::table();
	}

	protected function gdoHeaderFields() : array
	{
		return GDO_TBS_Challenge::table()->gdoColumnsExcept(
			'chall_deleted', 'chall_deletor', 'chall_solution',
			);
	}

	public function itemToCompletionJSON(GDO $gdo) : array
	{
		/** @var $gdo GDO_TBS_Challenge **/
		return [
			'id' => $gdo->getID(),
			'text' => $gdo->displayTitle(),
			'display' => $gdo->renderOption(),
		];
	}

}
