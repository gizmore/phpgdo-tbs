<?php
namespace GDO\TBS\Method;

use GDO\Core\GDT;
use GDO\Core\MethodAjax;

final class ActiveUsers extends MethodAjax
{

	public function execute(): GDT
	{
		return $this->templatePHP('ajax/active_users.php');
	}

}
