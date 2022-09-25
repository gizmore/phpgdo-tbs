<?php
namespace GDO\TBS\Method;

use GDO\Core\GDO;
use GDO\Table\MethodQueryTable;
use GDO\TBS\GDO_TBS_Challenge;
use GDO\DB\Query;

/**
 * Show recently added challenges.
 * @author gizmore
 */
final class RecentAdditions extends MethodQueryTable
{
    public function gdoTable() : GDO
    {
        return GDO_TBS_Challenge::table();
    }
    
    public function getQuery() : Query
    {
        return parent::getQuery()->order('chall_created DESC')->limit(10);
    }
    
}
