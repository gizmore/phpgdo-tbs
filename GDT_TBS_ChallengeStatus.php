<?php
namespace GDO\TBS;

use GDO\Core\GDT_Enum;
use GDO\UI\GDT_Tooltip;
use GDO\User\GDO_User;
use GDO\UI\GDT_Link;
use GDO\Core\WithGDO;

/**
 * Indicate challenge import status.
 * @author gizmore
 */
final class GDT_TBS_ChallengeStatus extends GDT_Enum
{
	use WithGDO;
	
    const NOT_CHECKED = 'not_checked';
    const NOT_TRIED = 'not_tried';
    const IN_PROGRESS = 'in_progress';
    const WONT_FIX = 'wont_fix';
    const NEED_FILES = 'need_files';
    const WORKING = 'working';

    public static array $COLORS = [
        self::NOT_CHECKED => '#666', # no auto checker. no exception for auto checker. initial
        self::NOT_TRIED => '#AAA', # auto checker, but no success yet. set automatically for not checked challs after import.
        self::IN_PROGRESS => '#933', # we have to manually work at it. manually assigned
        self::NEED_FILES => '#F77', # we are aware we need more files. manually assigned.
        self::WONT_FIX => '#F00', # cannot or won't fix. manually assigned.
        self::WORKING => '#0F0', # challenge should be working. manually assigned.
    ];
    
    private GDT_Tooltip $tooltip;

    private GDT_Link $editLink;
    
    protected function __construct()
    {
        parent::__construct();
        $this->enumValues(
            self::NOT_CHECKED,
            self::NOT_TRIED,
            self::IN_PROGRESS,
            self::NEED_FILES,
            self::WONT_FIX,
            self::WORKING);
        $this->label('tbs_chall_status');
        $this->notNull();
        $this->initial(self::NOT_CHECKED);
        $this->tooltip = GDT_Tooltip::make('chall_tooltip');
        $this->editLink = GDT_Link::make('chall_edit_link');
    }
    
    public function getChallenge() : GDO_TBS_Challenge
    {
        return $this->gdo;
    }
    
    public function displayVar(string $enumValue = null): string
    {
        return $enumValue === null ? t($this->emptyLabel, $this->emptyLabelArgs) : t("tbs_tt_$enumValue");
    }
    
    
    public function renderCell() : string
    {
        # Build status tooltip icon.
        $key = 'tbs_tt_'.$this->getVar();
        $tt = $this->tooltip->tooltip($key)->render();
        $color = self::$COLORS[$this->getVar()];
        $icon = sprintf('<div style="color: %s;">%s</div>', $color, $tt);
        
        # If we can edit we return a link with icon as label.
        if (GDO_User::current()->isStaff())
        {
        	if ($challenge = $this->getChallenge())
        	{
        		return $this->editLink->href($challenge->hrefEdit())->labelRaw($icon)->render();
        	}
        }
        
        # Else just the icon.
        return $icon;
    }

}
