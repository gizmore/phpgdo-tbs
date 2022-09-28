<?php
namespace GDO\TBS;

use GDO\Core\GDT_UInt;

final class GDT_TBS_ChallID extends GDT_UInt
{
    public static function make($name=null)
    {
        $obj = parent::make($name);
        $obj->labelNone();
        return $obj;
    }
    
    public function renderCell()
    {
        return $this->getVar() . ':';
    }
    
}
