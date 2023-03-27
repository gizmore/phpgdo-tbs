<?php
namespace GDO\TBS\Method;

use GDO\Core\GDT;
use GDO\Core\Method;
use GDO\TBS\GDO_TBS_Challenge;
use GDO\TBS\GDT_TBS_Challenge;

/**
 * Load a challenge and display challenge template.
 *
 * @version 6.10.1
 * @since 6.10.0
 * @author gizmore
 */
final class Challenge extends Method
{

	public function isGuestAllowed(): string { return false; }

	public function gdoParameters(): array
	{
		return [
			GDT_TBS_Challenge::make('challenge')->notNull(),
		];
	}

	public function execute(): GDT
	{
		$challenge = $this->getChallenge();
		return $this->templatePHP('challenge.php', [
			'challenge' => $challenge]);
	}

	/**
	 * @return GDO_TBS_Challenge
	 */
	public function getChallenge()
	{
		return $this->gdoParameterValue('challenge');
	}

	public function getMethodTitle(): string
	{
		$challenge = $this->getChallenge();
		return t('title_challenge', [
			$challenge->getTitle(),
			$challenge->displayCategory()]);
	}

}
