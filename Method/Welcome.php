<?php
namespace GDO\TBS\Method;

use GDO\UI\MethodPage;

final class Welcome extends MethodPage
{
    public function getMethodTitle() : string
    {
    	return t('tbs_welcome_title');
    }
    
}
