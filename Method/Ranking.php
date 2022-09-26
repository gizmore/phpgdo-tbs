<?php
namespace GDO\TBS\Method;

use GDO\Table\MethodQueryTable;
use GDO\TBS\GDT_TBS_Rank;
use GDO\Core\GDO;
use GDO\Country\GDT_Country;
use GDO\User\GDT_Level;
use GDO\TBS\GDT_TBS_GroupmasterIcon;
use GDO\TBS\GDT_TBS_ChallengeCategory;
use GDO\User\GDT_ProfileLink;
use GDO\TBS\GDO_TBS_ChallengeSolvedCategory;
use GDO\User\GDO_User;
use GDO\DB\Query;

/**
 * TBS ranking table.
 * @author gizmore
 */
final class Ranking extends MethodQueryTable
{
    public function fileCached() { return true; }
    
    public function isOrdered() : bool { return false; }
    public function isFiltered() { return false; }
    public function getDefaultOrder() : ?string { return 'user_level DESC'; }
    public function getDefaultIPP() : int { return 100; }
    public function fetchAs() { return GDO_User::table(); }
    
    public function getMethodTitle() : string
    {
        return t('mt_tbs_ranking');
    }
    
    public function gdoTable() : GDO
    {
        return GDO_TBS_ChallengeSolvedCategory::table();
    }
    
    public function getQuery() : Query
    {
        return $this->gdoTable()->select('*')->
                joinObject('csc_user')->
                where('user_type="member"')->
                fetchTable(GDO_User::table());
    }
    
    public function gdoHeaders() : array
    {
//         $o = $this->table->headers->name;
//         $page = $this->table->headers->getField('page')->getRequestVar($o, 1);
//         $ipp = $this->table->headers->getField('ipp')->getRequestVar($o, 100);
//         $from = $this->table->pagemenu->getFromS($page, $ipp);
        return [
            GDT_TBS_Rank::make('rank')->startRank(1),
            GDT_Country::make('user_country')->labelNone()->withName(false),
            GDT_ProfileLink::make('username')->nickname(),
            GDT_Level::make('user_level')->label('solved'),
            $this->groupmasterIcon(0),
            $this->groupmasterIcon(1),
            $this->groupmasterIcon(2),
            $this->groupmasterIcon(3),
            $this->groupmasterIcon(4),
            $this->groupmasterIcon(5),
            $this->groupmasterIcon(6),
            $this->groupmasterIcon(7),
            $this->groupmasterIcon(8),
            $this->groupmasterIcon(9),
            $this->groupmasterIcon(10),
            $this->groupmasterIcon(11),
            $this->groupmasterIcon(12),
        ];
    }
    
    private function groupmasterIcon($id)
    {
        return GDT_TBS_GroupmasterIcon::make()->
            category(GDT_TBS_ChallengeCategory::$CATS[$id]);
    }
    
}
