<?php
namespace GDO\TBS\Method;

use GDO\UI\MethodPage;

final class VulnerableCode extends MethodPage
{
	public function isTrivial(): bool
	{
		return false;
	}

}
