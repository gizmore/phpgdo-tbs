<?php
namespace GDO\TBS\Method;

use GDO\Core\CSS;
use GDO\Core\GDT;
use GDO\UI\MethodPage;

/**
 * Show support us page.
 *
 * @author gizmore
 */
final class Support extends MethodPage
{

	public function getMethodTitle(): string
	{
		return t('tbs_support');
	}

	public function onMethodInit(): ?GDT
	{
		$webroot = GDO_WEB_ROOT;
		$css = <<<END
        p.contribs {
            margin: 5px 10px 0px 10px;
            text-align: justify;
        }
        ul.list {
            padding: 4px 8px 4px 28px;
        }
        ul.list, ul.list li {
            list-style-image:url({$webroot}GDO/TBS/images/misc/bullet1.gif);
        }
        END;
		CSS::addInline($css);
		return null;
	}

}
