<?php
namespace GDO\TBS\Method;

use GDO\Core\GDT;
use GDO\Core\GDT_String;
use GDO\Core\Method;

final class Userdata extends Method
{

    public function execute(): GDT
    {
        return GDT_String::make()->initial('0:0:0:0:0');
    }

}
