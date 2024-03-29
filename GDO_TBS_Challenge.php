<?php
namespace GDO\TBS;

use GDO\Core\GDO;
use GDO\Core\GDO_Exception;
use GDO\Core\GDT_AutoInc;
use GDO\Core\GDT_CreatedAt;
use GDO\Core\GDT_CreatedBy;
use GDO\Core\GDT_Decimal;
use GDO\Core\GDT_DeletedAt;
use GDO\Core\GDT_DeletedBy;
use GDO\Core\GDT_Index;
use GDO\Core\GDT_UInt;
use GDO\Core\GDT_Virtual;
use GDO\Crypto\GDT_Password;
use GDO\DB\Cache;
use GDO\Forum\GDT_ForumBoard;
use GDO\Net\GDT_Url;
use GDO\Net\URL;
use GDO\UI\GDT_Title;
use GDO\User\GDO_Permission;
use GDO\User\GDO_User;
use GDO\User\GDT_Level;
use GDO\User\GDT_Permission;

/**
 * A challenge on TBS.
 *
 * @version 6.10
 * @since 6.10
 * @author gizmore
 */
final class GDO_TBS_Challenge extends GDO
{

	/**
	 * @return self
	 */
	public static function getChallenge($category, $title)
	{
		return self::table()->select()->
		where('chall_category=' . quote($category))->
		where('chall_title=' . quote($title))->
		first()->exec()->fetchObject();
	}

	public static function getChallengeCount($category = null)
	{
		static $count = null;
		$key = 'tbs_challenge_count';
		$key = $category === null ? $key : $key . $category;
		if ($count === null)
		{
			if (null === ($count = Cache::get($key)))
			{
				$where = $category === null ? true : 'chall_category = ' . (int)$category;
				$count = self::table()->countWhere($where);
				Cache::set($key, $count);
			}
		}
		return $count;
	}

	/**
	 * Get a challenge by GDO url.
	 *
	 * @param string $url
	 *
	 * @return self
	 */
	public static function getByURL($url)
	{
		return self::table()->select()->
		where("chall_url LIKE '{$url}%'")->
		first()->exec()->fetchObject();
	}

	public function gdoColumns(): array
	{
		return [
			GDT_AutoInc::make('chall_id'),
			GDT_UInt::make('chall_order'),
			GDT_Level::make('chall_score')->notNull()->initial('1'),
			GDT_TBS_ChallengeCategory::make('chall_category')->notNull(),
			GDT_TBS_ChallengeStatus::make('chall_status'),
			GDT_Title::make('chall_title'),
			GDT_Url::make('chall_url')->allowInternal()->notNull(),

			GDT_Password::make('chall_solution'),
			GDT_Permission::make('chall_permission'),

			GDT_UInt::make('chall_votes')->notNull()->initial('0'),

			GDT_TBS_VoteField::make('chall_difficulty')->tooltip('tbs_tt_chall_difficulty'),
			GDT_TBS_VoteField::make('chall_creativity')->tooltip('tbs_tt_chall_creativity'),
			GDT_TBS_VoteField::make('chall_education')->tooltip('tbs_tt_chall_education'),
			GDT_TBS_VoteField::make('chall_presentation')->tooltip('tbs_tt_chall_presentation'),

			GDT_ForumBoard::make('chall_questions')->label('tbs_question_board'),
			GDT_ForumBoard::make('chall_solutions')->label('tbs_solution_board'),

			GDT_Virtual::make('chall_solver_count')->gdtType(GDT_UInt::make()->tooltip('tbs_tt_chall_solver_count'))->subquery('SELECT COUNT(*) FROM gdo_tbs_challengesolved cs WHERE cs.cs_challenge=chall_id'),
			GDT_TBS_ChallengeSolved::make('chall_solved'),

			GDT_CreatedBy::make('chall_creator'),
			GDT_CreatedAt::make('chall_created'),
			GDT_DeletedBy::make('chall_deletor'),
			GDT_DeletedAt::make('chall_deleted'),

			GDT_Index::make('index_chall_category')->indexColumns('chall_category'),
		];
	}

	public function displayTitle() { return $this->gdoDisplay('chall_title'); }

	public function getCategory() { return $this->gdoVar('chall_category'); }

	public function getStatus() { return $this->gdoVar('chall_status'); }

	public function getPermissionID() { return $this->getPermission()->getID(); }

    /**
     * @return GDO_Permission
     * @throws GDO_Exception
     */
	public function getPermission() { return GDO_Permission::findBy('perm_name', $this->getPermissionTitle()); }

	public function getPermissionTitle() { return $this->displayCategory() . '_' . $this->getOrder() . '_' . preg_replace('#[^0-9A-Za-z]#', '_', $this->getTitle()); }

	public function displayCategory() { return $this->gdoDisplay('chall_category'); }

	public function getOrder() { return $this->gdoVar('chall_order'); }

	public function getTitle() { return $this->gdoVar('chall_title'); }

	public function hasSolved(GDO_User $user)
	{
		return GDO_TBS_ChallengeSolved::hasSolved($user, $this);
	}

	/**
	 * @return GDO_User
	 */
	public function getCreator() { return $this->gdoValue('chall_creator'); }

	/**
	 * @return URL
	 */
	public function getURL() { return $this->gdoValue('chall_url'); }

	public function hrefEdit() { return href('TBS', 'ChallengeCRUD', "&id={$this->getID()}"); }

	public function hrefChallenge() { return href('TBS', 'Challenge', "&challenge={$this->getID()}"); }

	public function href_chall_questions() { return href('Forum', 'Boards', "&board={$this->getQuestionBoardID()}"); }

	###############
	### Factory ###
	###############

	public function getQuestionBoardID() { return $this->gdoVar('chall_questions'); }

	public function href_chall_solutions() { return href('Forum', 'Boards', "&board={$this->getSolutionBoardID()}"); }

	public function getSolutionBoardID() { return $this->gdoVar('chall_solutions'); }

	###############
	### Solving ###
	###############

	public function onSolve($answer)
	{
		return (new ChallSolveEngine($this))->onSolve($answer);
	}

	public function solved(GDO_User $user)
	{
		return (new ChallSolveEngine($this))->solved($user);
	}

}
