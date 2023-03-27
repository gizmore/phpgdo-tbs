<?php
namespace GDO\TBS\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_Response;
use GDO\Core\Method;

final class Solve extends Method
{

	public function execute(): GDT
	{
		return GDT_Response::make();
	}

}
