<?php
namespace GDO\TBS;

use GDO\Core\GDT_String;
use GDO\User\GDT_ProfileLink;
use GDO\Core\WithGDO;

/**
 * A challenge title.
 * A string with challenge creator link.
 * 
 * @author gizmore
 */
final class GDT_TBS_ChallTitle extends GDT_String
{
	use WithGDO;
	
    public function defaultLabel() : self { return $this->label('name'); }
    
    private GDT_ProfileLink $creator;
    
    protected function __construct()
    {
        parent::__construct();
        $this->creator = GDT_ProfileLink::make()->nickname();
    }
    
    private function getChallenge() : GDO_TBS_Challenge
    {
        return $this->gdo;
    }
    
    public function renderCell() : string
    {
        $chall = $this->getChallenge();
        
        # Creator string
        $creator = $chall->getCreator();
        if ($creator->getID() <= '2')
        {
            $creator = '';
        }
        else
        {
            $creator = $this->creator->user($creator)->render();
            $creator = sprintf(' <span>(made by %s)</span>', $creator);
        }
        
        # output
        return sprintf('<div class="tbs-chall-title"><a href="%s">%s</a>%s</div>',
            $chall->hrefChallenge(), $chall->displayTitle(), $creator);
    }
    
}
