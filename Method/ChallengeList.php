<?php
namespace GDO\TBS\Method;

use GDO\Core\GDO;
use GDO\TBS\GDO_TBS_Challenge;
use GDO\TBS\GDT_TBS_ChallengeCategory;
use GDO\User\GDT_User;
use GDO\Table\GDT_Table;
use GDO\Table\MethodTable;
use GDO\TBS\GDT_TBS_ChallTitle;
use GDO\TBS\Module_TBS;
use GDO\UI\GDT_Button;
use GDO\User\GDO_User;
use GDO\TBS\GDT_TBS_GroupmasterIcon;
use GDO\DB\ArrayResult;

/**
 * List all challenges for a category and user.
 * 
 * @author gizmore
 */
final class ChallengeList extends MethodTable
{
    public function gdoTable() : GDO { return GDO_TBS_Challenge::table(); }

    public function isPaginated() { return false; }
    public function isFiltered() { return false; }
    public function isGuestAllowed() : bool { return false; }
    
    public function getDefaultOrder() : ?string { return 'chall_order'; }
    
    public function gdoParameters() : array
    {
        return [
            GDT_User::make('user')->fallbackCurrentUser(),
            GDT_TBS_ChallengeCategory::make('category')->notNull(),
        ];
    }
    
    /**
     * @return GDO_User
     */
    public function getUser()
    {
        return $this->gdoParameterValue('user');
    }
    
    public function gdoHeaders() : array
    {
        $challs = GDO_TBS_Challenge::table();
        return [
            $challs->gdoColumn('chall_order'),
            $challs->gdoColumn('chall_status'),
            GDT_TBS_ChallTitle::make('chall_title'),
            $challs->gdoColumn('chall_votes'),
            $challs->gdoColumn('chall_difficulty'),
            $challs->gdoColumn('chall_creativity'),
            $challs->gdoColumn('chall_education'),
            $challs->gdoColumn('chall_presentation'),
            $challs->gdoColumn('chall_solver_count'),
            GDT_Button::make('chall_questions')->labelNone()->rawIcon(Module_TBS::instance()->rawIcon('misc/challenge_forum_questions.gif')),
            GDT_Button::make('chall_solutions')->labelNone()->rawIcon(Module_TBS::instance()->rawIcon('misc/challenge_forum_solutions.gif')),
            $challs->gdoColumn('chall_solved')->user($this->getUser()),
        ];
    }
    
    public function getCategory()
    {
        return $this->gdoParameterVar('category');
    }
    
    public function getResult() : ArrayResult
    {
        $cat = $this->getCategory();
        $all = GDO_TBS_Challenge::table()->allCached('chall_order');
        $all = array_filter($all, function(GDO_TBS_Challenge $chall) use ($cat) {
            return $chall->getCategory() === $cat; });
        return new ArrayResult($all, GDO_TBS_Challenge::table());
    }
    
    public function setupTitle(GDT_Table $table)
    {
        $table->name($this->getCategory());
        $user = $this->getUser();
        $category = $this->getCategory();
        $icon = GDT_TBS_GroupmasterIcon::make()->gdo($user)->category($category);
        
        $table->titleRaw($icon->render() . ' ' .
            t('tbs_table_challs', [$table->countItems(), $category]));
    }
    
}
