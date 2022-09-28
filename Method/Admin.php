<?php
namespace GDO\TBS\Method;

use GDO\Core\Method;
use GDO\Admin\MethodAdmin;
use GDO\TBS\Module_TBS;
use GDO\UI\GDT_Page;

final class Admin extends Method
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
    
    public function execute()
    {
    }
    
}
