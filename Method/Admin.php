<?php
namespace GDO\TBS\Method;

use GDO\Admin\MethodAdmin;
use GDO\Core\GDT;
use GDO\Core\Method;
use GDO\TBS\Module_TBS;
use GDO\UI\GDT_Page;
use GDO\UI\MethodPage;

final class Admin extends MethodPage
{

	use MethodAdmin;

	/**
	 * Before execute we add the top tabs.
	 *
	 * @see MethodAdmin
	 */
	public function onRenderTabs(): void
	{
		$this->renderAdminBar();
		GDT_Page::instance()->topResponse()->addField(
			Module_TBS::instance()->barAdminTabs()
		);
	}

}
