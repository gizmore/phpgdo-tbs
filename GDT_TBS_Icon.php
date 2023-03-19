<?php
namespace GDO\TBS;

use GDO\Core\GDT;

/**
 * A sidebar icon.
 *
 * @author gizmore
 */
final class GDT_TBS_Icon extends GDT
{
    public string $iconName;
    public function iconName(string $iconName): static
    {
        $this->iconName = $iconName;
		return $this;
    }

    public function render()
    {
        return $this->renderCell();
    }
    
    public function renderCell(): string
    {
        $path = Module_TBS::instance()->wwwPath("img/sidebar/{$this->iconName}.gif");
        return sprintf("<img src=\"%s\" />\n", $path);
    }
    
}
