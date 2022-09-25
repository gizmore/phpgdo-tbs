<?php
namespace GDO\TBS\Method;

use GDO\Core\GDO;
use GDO\Form\MethodCrud;
use GDO\TBS\GDO_TBS_Challenge;

final class ChallengeCRUD extends MethodCrud
{
    public function hrefList() : string
    {
        return href('TBS', 'ChallengeLists');
    }

    public function gdoTable() : GDO
    {
        return GDO_TBS_Challenge::table();
    }
    
}
