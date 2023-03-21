<?php
namespace GDO\TBS;

use GDO\Core\GDT_Template;
use GDO\UI\GDT_Bar;

/**
 * Render the TBS sidebar.
 *
 * @author gizmore
 */
final class GDT_TBS_Sidebar extends GDT_Bar
{

	protected function __construct()
	{
		parent::__construct();
	}

	public function render()
	{
		return GDT_Template::php('TBS', 'left_bar.php');
	}

}
