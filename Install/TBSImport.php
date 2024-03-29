<?php
namespace GDO\TBS\Install;

use Exception;
use GDO\Core\Method\ClearCache;
use GDO\Country\GDO_Country;
use GDO\Country\Module_Country;
use GDO\Date\Time;
use GDO\DB\Database;
use GDO\Forum\GDO_ForumBoard;
use GDO\Forum\GDO_ForumPost;
use GDO\Forum\GDO_ForumThread;
use GDO\Forum\Method\Repair;
use GDO\Forum\Module_Forum;
use GDO\HTML\Module_HTML;
use GDO\Mail\Module_Mail;
use GDO\Register\Module_Register;
use GDO\TBS\GDO_TBS_Challenge;
use GDO\TBS\GDO_TBS_ChallengeSolved;
use GDO\TBS\GDT_TBS_ChallengeCategory;
use GDO\TBS\GDT_TBS_ChallengeStatus;
use GDO\TBS\Module_TBS;
use GDO\UI\GDT_Error;
use GDO\UI\GDT_Message;
use GDO\UI\GDT_Page;
use GDO\UI\Module_UI;
use GDO\User\GDO_Permission;
use GDO\User\GDO_User;
use GDO\User\GDO_UserPermission;
use GDO\User\Module_User;
use GDO\Util\CSV;
use GDO\Util\FileUtil;
use GDO\Util\Filewalker;
use GDO\Util\Strings;


/**
 * - Import TBS data from INPUT/ folder.
 *
 * - Import some CSV from Xaav
 *
 * - Import some HIDDEN/ files
 *
 * Problems:
 *
 *  - Some post is very very long. I truncate at roughly 10MB now.
 *    You have to set max packet size in my.ini to 40MB for this.
 *
 *  - Clarify how to merge challenges and solution forums nicely.
 *    In theory it already should be capable of repeated multi imports.
 *    Clarify for HIDDEN updates.
 *
 *  - Challenge import status is not in an easily readable format.
 *
 * @version 7.0.1
 * @since 6.10.0
 */
final class TBSImport
{

	# country.csv
	public const CSV_COUNTRY_ID = 0;
	public const CSV_COUNTRY_NAME = 1;
	public const CSV_USER_USERNAME = 0;

	# users.csv
	public const CSV_USER_RANK = 1;
	public const CSV_USER_SOLVED = 2;
	public const CSV_USER_EMAIL = 3;
	public const CSV_USER_COUNTRY = 4;
	public const CSV_USER_REGDATE = 5;
	public const CSV_USER_LASTACTIVITY = 6;
	public const CSV_USER_BIRTHDATE = 7;
	public const CSV_USER_WEBSITE = 8;
	public const CSV_USER_RANKED = 9;
	public const CSV_CHALL_ID = 0;

	# challenge.csv
	public const CSV_CHALL_CAT = 1;
	public const CSV_CHALL_ORDER = 2;
	public const CSV_CHALL_URL = 3;
	public const CSV_CHALL_AUTHOR = 4;
	public const CSV_CHALL_NAME = 5;
	public const CSV_CHALL_VOTE_COUNT = 6;
	public const CSV_CHALL_VOTE_DIFF = 7;
	public const CSV_CHALL_VOTE_CREATIVE = 8;
	public const CSV_CHALL_VOTE_EDUCATION = 9;
	public const CSV_CHALL_VOTE_PRESENTATION = 10;
	public const CSV_CHALL_CREATED = 11;
	public const CSV_SOLVE_CHALL_ID = 0;

	# solvers.csv
	public const CSV_SOLVE_USERNAME = 1;
	public const CSV_FORUM_ROOT_ID = 0;

	# forum_roots.csv
	public const CSV_FORUM_ROOT_TITLE = 1;
	public const CSV_FORUM_ROOT_PID = 2;
	public const CSV_FORUM_ROOT_CAT = 3;
	public const CSV_FORUM_BOARD_ID = 0;

	# forums.csv
	public const CSV_FORUM_BOARD_TITLE = 1;
	public const CSV_FORUM_BOARD_DESCR = 2;
	public const CSV_FORUM_BOARD_PID = 3;
	public const CSV_FORUM_BOARD_IS_SOLUTION = 4;
	public const CSV_FORUM_BOARD_CHALL_ID = 5;
	public const CSV_FORUM_TOPIC_ID = 0;

	# forum_topics.csv
	public const CSV_FORUM_TOPIC_TITLE = 1;
	public const CSV_FORUM_TOPIC_AUTHOR = 2;
	public const CSV_FORUM_TOPIC_BOARD_ID = 3;
	public const CSV_FORUM_TOPIC_CHALL_ID = 4;
	public const CSV_FORUM_POST_ID = 0;

	# forum_posts.csv
	public const CSV_FORUM_POST_TITLE = 1;
	public const CSV_FORUM_POST_MESSAGE = 2;
	public const CSV_FORUM_POST_DATE = 3;
	public const CSV_FORUM_POST_AUTHOR = 4;
	public const CSV_FORUM_POST_TOPIC = 5;
	public const CSV_FORUM_POST_EDITOR = 6;
	public const CSV_FORUM_POST_EDITED = 7;
	private static $COUNTRYMAPPING = null;
	/**
	 * Excludes from simple form solution checker
	 * Here we can place challenges that are excluded from simple solution checker.
	 *
	 * @TODO: Complete the list of exceptional / non simple solution files.
	 */
	private static $NO_SIMPLE_FORM = [
		'/challenges/exploit_long/index.php',
		'/challenges/exploits/exploit_analyse1/index.php',
	];
	private $userToId = [];
	private $boardMapping = [];

	##########################
	### Conversion helpers ###
	##########################
	private $forumTrashcan;

	public function import(array $config)
	{
		set_time_limit(60 * 60 * 2); # 120 minutes should be enough.
		ini_set('memory_limit', '256M');

		if ($config['import_users'])
		{
			$this->createUnknownUser();
			$this->importCountries();
			$this->importUsers();
		}

		if ($config['import_challs'])
		{
			$this->importChallenges();
			$this->importChallengeFiles();
		}

		if ($config['import_chall_solved'])
		{
			$this->importChallengeSolved();
		}

		if ($config['import_forum'])
		{
			GDO_ForumBoard::$BUILD_TREE_UPON_SAVE = false;
			$this->importForum();
			GDO_ForumBoard::$BUILD_TREE_UPON_SAVE = true;
		}

		if ($config['import_permissions'])
		{
			$this->importPermissionsFromChallSolved();
		}

		if ($config['import_forum'])
		{
			Repair::make()->execute();
		}

		# Clear cache for user permissions and maybe other things.
		ClearCache::make()->clearCache();
	}

	public function createUnknownUser()
	{
		if (GDO_User::getByName('UnknownUser'))
		{
			return true;
		}
		GDO_User::blank([
			'user_name' => 'UnknownUser',
			'user_type' => 'member',
		])->insert();
	}

	public function importCountries()
	{
		$path = $this->getImportPath('countries.csv');
		$csv = new CSV($path);
		$csv->eachLine(function ($row)
		{
			$n = $row[self::CSV_COUNTRY_NAME];
			$found = false;
			foreach (GDO_Country::table()->all() as $c)
			{
				if (stripos($c->displayEnglishName(), $n) !== false)
				{
					$found = true;
					break;
				}
			}

			if (!$found)
			{
				switch ($n)
				{
					case 'USA':
						$c = GDO_Country::table()->findBy('c_iso', 'US');
						break;
					case 'Saudi Arabia':
						$c = GDO_Country::table()->findBy('c_iso', 'SA');
						break;
					case 'Bosnia and Herzegovina':
						$c = GDO_Country::table()->findBy('c_iso', 'BA');
						break;
					case 'Trinidad &amp; Tobago':
						$c = GDO_Country::table()->findBy('c_iso', 'TT');
						break;
					case 'Palestine':
						$c = GDO_Country::table()->findBy('c_iso', 'PS');
						break;
					case 'Korea, South':
						$c = GDO_Country::table()->findBy('c_iso', 'KR');
						break;
					default:
						echo "<div>no country named $n</div>\n";
						return;
				}
			}

			self::$COUNTRYMAPPING[$row[self::CSV_COUNTRY_ID]] = $c;
		});
	}

	private function getImportPath($append = '') { return $this->getModule()->filePath('INPUT/CSV/' . $append); }

	/**
	 * @return Module_TBS
	 */
	private function getModule() { return Module_TBS::instance(); }


	public function importUsers()
	{
		$cnt = Module_Country::instance();
		$usr = Module_User::instance();
		$reg = Module_Register::instance();
		$mal = Module_Mail::instance();
		$path = $this->getImportPath('users.csv');
		$csv = new CSV($path);
		$csv->eachLine(function ($row) use ($reg, $usr, $cnt, $mal)
		{

			$username = $row[self::CSV_USER_USERNAME];
			$u = GDO_User::table()->getBy('user_name', $username);
			if (!$u)
			{
				$u = GDO_User::blank([
					'user_name' => $username,
					'user_type' => 'member',
					'user_level' => $row[self::CSV_USER_SOLVED],
				])->insert();

				if ($row[self::CSV_USER_WEBSITE])
				{
					Module_TBS::instance()->saveUserSetting($u, 'tbs_website', $row[self::CSV_USER_WEBSITE]);
				}

				if ($row[self::CSV_USER_RANKED] < 1)
				{
					Module_TBS::instance()->saveUserSetting($u, 'tbs_ranked', '0');
				}

				$mal->saveUserSetting($u, 'email', self::convertEmail($row[self::CSV_USER_EMAIL]));

				$cnt->saveUserSetting($u, 'country_of_origin', $this->convertCountryID($row[self::CSV_USER_COUNTRY]));
				$cnt->saveUserSetting($u, 'country_of_living', $this->convertCountryID($row[self::CSV_USER_COUNTRY]));

				$regdate = $this->convertDate($row[self::CSV_USER_REGDATE]);
				$reg->saveUserSetting($u, 'register_date', $regdate);

				$activity = $this->convertDate($row[self::CSV_USER_LASTACTIVITY]);
				$usr->saveUserSetting($u, 'last_activity', $activity);
			}
		});
	}

	private function convertEmail($mailto)
	{
		return Strings::substrFrom($mailto, 'mailto:', $mailto);
	}

	private function convertCountryID($cid)
	{
		return $cid ? self::$COUNTRYMAPPING[$cid]->getID() : null;
	}

	private function convertDate($german)
	{
		$m = [];
		preg_match('#^(\\d{2})\\.(\\d{2})\\.(\\d{4});? (\\d{2}):(\\d{2}):(\\d{2})$#', $german, $m);
		return sprintf('%3$s-%2$s-%1$s %4$s:%5$s:%6$s.000', $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
	}

	################
	### Importer ###
	################

	public function importChallenges()
	{
		$path = $this->getImportPath('challenges.csv');
		$csv = new CSV($path);
		$csv->eachLine(function ($row)
		{

			$cid = $row[self::CSV_CHALL_ID];

			$title = trim($row[self::CSV_CHALL_NAME]);
			$title = $title ? $title : 'Crypto 51';

			$data = [
				'chall_id' => $cid,
				'chall_order' => $row[self::CSV_CHALL_ORDER],
				'chall_score' => '1',
				'chall_category' => $this->convertCategory($row[self::CSV_CHALL_CAT]),
				'chall_title' => $title,
				'chall_url' => $this->convertChallengeURL($row[self::CSV_CHALL_URL]),
				'chall_votes' => $row[self::CSV_CHALL_VOTE_COUNT],
				'chall_difficulty' => $row[self::CSV_CHALL_VOTE_DIFF],
				'chall_creativity' => $row[self::CSV_CHALL_VOTE_CREATIVE],
				'chall_education' => $row[self::CSV_CHALL_VOTE_EDUCATION],
				'chall_presentation' => $row[self::CSV_CHALL_VOTE_PRESENTATION],
				'chall_creator' => $this->convertChallAuthor($row[self::CSV_CHALL_AUTHOR]),
				'chall_created' => Time::getDate($row[self::CSV_CHALL_CREATED]),
			];

			if (!($chall = GDO_TBS_Challenge::table()->getById($cid)))
			{
				$chall = GDO_TBS_Challenge::blank($data);
				$chall->insert();
			}
			else
			{
				# Reset autochecker status
				if ($chall->getStatus() === GDT_TBS_ChallengeStatus::NOT_TRIED)
				{
					$data['chall_status'] = GDT_TBS_ChallengeStatus::NOT_CHECKED;
				}

				$chall->saveVars($data);
			}

			$this->createChallengePermission($chall);
		});
	}

	#############
	### Users ###
	#############

	private function convertCategory($cat)
	{
		$cats = GDT_TBS_ChallengeCategory::$CATS;
		return isset($cats[$cat]) ? $cats[$cat] : null;
	}

	private function convertChallengeURL($url)
	{
		return $url;
	}

	private function convertChallAuthor($username)
	{
		if (!$username)
		{
			return null;
		}
		$username = Strings::substrTo($username, ' and ', $username);
		$username = Strings::substrTo($username, ',', $username);
		return $this->convertUsernameToID($username);
	}


	##################
	### Challenges ###
	##################

	private function convertUsernameToID($username)
	{
		$username = $username ? $username : 'UnknownUser';
		if (!($user = GDO_User::getByName($username)))
		{
			echo "Cannot find user $username<br/>\n";
			$user = GDO_User::getByName('UnknownUser');
		}
		return $user->getID();
	}

	public function createChallengePermission(GDO_TBS_Challenge $chall)
	{
		if (!(GDO_Permission::getByName($chall->getPermissionTitle())))
		{
			$perm = GDO_Permission::blank([
				'perm_name' => $chall->getPermissionTitle(),
			])->insert();
			$chall->saveVar('chall_permission', $perm->getID());
		}
	}

	public function importChallengeFiles()
	{
		# Reset path
		FileUtil::removeDir($this->challengesPath());
		FileUtil::createDir($this->challengesPath());

		# Copy public files
		$path = $this->getModule()->filePath('DUMP/challenges/');
		Filewalker::traverse($path, null, [$this, 'importChallengeFile'], null, 255, null, '/');

		# Merge manual created files
		$path = $this->getModule()->filePath('HIDDEN/');
		$path2 = $this->getModule()->filePath('challenges/');
		FileUtil::mergeDirectory($path, $path2);
	}

	private function challengesPath($append = '')
	{
		return $this->getModule()->filePath('challenges/' . $append);
	}

	public function importChallengeSolved()
	{
		# Open CSV
		$path = $this->getImportPath('challenge_solved.csv');
		$csv = new CSV($path);

		$table_solved = GDO_TBS_ChallengeSolved::table();
		$fields = [
			$table_solved->gdoColumn('cs_challenge'),
			$table_solved->gdoColumn('cs_user'),
			$table_solved->gdoColumn('cs_solved_at'),
		];
		$data = [];
		$now = Time::getDate();
		# Iterate
		$csv->eachLine(function ($row) use ($fields, &$data, $now)
		{

			$data[] = [
				$row[self::CSV_SOLVE_CHALL_ID],
				$this->usernameToID($row[self::CSV_SOLVE_USERNAME]),
				$now,
			];

			if (count($data) >= 100)
			{
				GDO_TBS_ChallengeSolved::bulkReplace($fields, $data, 100);
				$data = [];
			}
		});

		if (count($data) >= 1)
		{
			GDO_TBS_ChallengeSolved::bulkReplace($fields, $data, 100);
			$data = [];
		}
	}

	public function usernameToID($username)
	{
		if (!(isset($this->userToId[$username])))
		{
			$this->userToId[$username] = GDO_User::findBy('user_name', $username)->getID();
		}
		return $this->userToId[$username];
	}

	####################
	### Chall solved ###
	####################

	public function importForum()
	{
		# Fix decoder to purify for now
//		GDT_Message::$DECODER = [Decoder::class, 'purify']; # default purifier

		$this->importForumRoots();
		Module_Forum::instance()->saveConfigVar('forum_root', $this->boardMapped('13'));
		$this->importForumBoards();
		$this->importForumFixes(); # Trashcan
		$this->importForumThreads();
		$this->importForumPosts();
		$this->markLatestPostAsMailed();
	}

	public function importForumRoots()
	{
		# Open CSV
		$path = $this->getImportPath('forum_roots.csv');
		$csv = new CSV($path);

		Database::instance()->disableForeignKeyCheck();

		Database::instance()->truncateTable(GDO_ForumPost::table());
		Database::instance()->truncateTable(GDO_ForumThread::table());
		Database::instance()->truncateTable(GDO_ForumBoard::table());
		GDO_ForumBoard::table()->clearCache();

		$roots = $csv->all();

		foreach ($roots as $row)
		{
			$bid = $row[self::CSV_FORUM_ROOT_ID];
			$board = GDO_ForumBoard::getById($this->boardMapped($bid));
			if (!$board)
			{
				$board = GDO_ForumBoard::blank([
					'board_id' => $bid,
					'board_title' => $row[self::CSV_FORUM_ROOT_TITLE],
					'board_created' => Time::getDate(),
					'board_creator' => '1',
				]);
				$board->save();
			}
			$this->boardMapping[$bid] = $board->getID();
		}


		# Fix PID again because foreign keys
		foreach ($roots as $row)
		{
			$board = GDO_ForumBoard::getById($this->boardMapped($row[self::CSV_FORUM_ROOT_ID]));
			if ($pid = $this->boardMapped($row[self::CSV_FORUM_ROOT_PID]))
			{
				$board->saveVar('board_parent', $pid);
			}
		}

		GDO_ForumBoard::table()->clearCache();
		GDO_ForumBoard::table()->rebuildFullTree();

		Database::instance()->enableForeignKeyCheck();

		Module_Forum::instance()->saveConfigVar('forum_root', '13');

		Repair::make()->executeWithInputs(['submit' => 1], false);

		GDO_ForumPost::table()->update()
			->set('post_message_editor="HTML"')
			->exec();
	}

	private function boardMapped($tbsBoardId)
	{
		return $tbsBoardId; # Keep TBS mapping.
		//         return @$this->boardMapping[$tbsBoardId];
	}

	#############
	### Forum ###
	#############

	public function importForumBoards()
	{
		# Open CSV
		$path = $this->getImportPath('forums.csv');
		$csv = new CSV($path);

		Database::instance()->disableForeignKeyCheck();

		# Iterate
		$csv->eachLine(function ($row)
		{

			$bid = $row[self::CSV_FORUM_BOARD_ID];
			$board = GDO_ForumBoard::getById($this->boardMapped($bid));

			if (!$board)
			{
				$board = GDO_ForumBoard::blank([
					'board_id' => $this->boardMapped($bid),
					'board_parent' => $this->boardMapped($row[self::CSV_FORUM_BOARD_PID]),
					'board_title' => $this->convertBoardTitle($row),
					'board_description' => $row[self::CSV_FORUM_BOARD_DESCR],
					'board_created' => Time::getDate(),
					'board_creator' => '1',
					'board_allow_threads' => '1',
				]);
			}
			else
			{
				$board->setVars([
					'board_parent' => $this->boardMapped($row[self::CSV_FORUM_BOARD_PID]),
					'board_title' => $this->convertBoardTitle($row),
					'board_description' => $row[self::CSV_FORUM_BOARD_DESCR],
					'board_created' => Time::getDate(),
					'board_creator' => '1',
					'board_allow_threads' => '1',
				]);
			}

			if ($row[self::CSV_FORUM_BOARD_IS_SOLUTION])
			{
				$challId = $row[self::CSV_FORUM_BOARD_CHALL_ID];
				$permId = $this->convertChallengeToPermissionId($challId);
				$board->setVar('board_permission', $permId);
			}

			$board->save();

			$this->boardMapping[$bid] = $board->getID();

			if ($row[self::CSV_FORUM_BOARD_CHALL_ID])
			{
				$challId = $row[self::CSV_FORUM_BOARD_CHALL_ID];
				$chall = GDO_TBS_Challenge::findById($challId);
				$bid = $board->getID();
				if ($row[self::CSV_FORUM_BOARD_IS_SOLUTION])
				{
					$chall->setVar('chall_solutions', $bid);
				}
				else
				{
					$chall->setVar('chall_questions', $bid);
				}
				$chall->save();
			}
		});
		Database::instance()->enableForeignKeyCheck();
		GDO_ForumBoard::table()->rebuildFullTree();
	}

	private function convertBoardTitle($row)
	{
		$bid = $row[self::CSV_FORUM_BOARD_ID];
		if (($bid == 276) || ($bid == 638))
		{
			return 'Crypto 51'; # the mysterious empty title challenge
		}
		return $row[self::CSV_FORUM_BOARD_TITLE];
	}

	private function convertChallengeToPermissionId($challId)
	{
		return GDO_TBS_Challenge::findById($challId)->getPermissionId();
	}

	public function importForumFixes()
	{
		if (!($board = GDO_ForumBoard::getBy('board_title', 'Trashcan')))
		{
			$board = GDO_ForumBoard::blank([
				'board_title' => 'Trashcan',
				'board_description' => 'Posts that are subject to be deleted.',
				'board_creator' => '1',
				'board_parent' => $this->boardMapped('13'),
			]);
		}
		else
		{
			$board->setVars([
				'board_title' => 'Trashcan',
				'board_description' => 'Posts that are subject to be deleted.',
				'board_creator' => '1',
				'board_parent' => $this->boardMapped('13'),
			]);
		}

		$this->forumTrashcan = $board->save();
		GDO_ForumBoard::table()->rebuildFullTree();
	}

	public function importForumThreads()
	{
		# Open CSV
		$path = $this->getImportPath('forum_topics.csv');
		$csv = new CSV($path);

		# Iterate
		$csv->eachLine(function ($row)
		{

			$tid = $row[self::CSV_FORUM_TOPIC_ID];
			$bid = $row[self::CSV_FORUM_TOPIC_BOARD_ID];
			if (!($thread = GDO_ForumThread::getById($tid)))
			{
				$thread = GDO_ForumThread::blank([
					'thread_id' => $tid,
					'thread_board' => $bid ? $this->boardMapped($bid) : $this->forumTrashcan->getID(),
					'thread_title' => $this->convertThreadTitle($row),
					'thread_creator' => '1',
					'thread_lastposted' => Time::getDate(),
				]);
			}
			$thread->save();
		});
	}

	private function convertThreadTitle(array $row)
	{
		$title = $row[self::CSV_FORUM_TOPIC_TITLE];
		$title = preg_replace('# Pages:.*$#', '', (string)$title);
		return $title ? $title : 'No Title';
	}

	public function importForumPosts()
	{
		# Open CSV
        GDT_Message::setDecoder('RAW');
        Module_UI::instance()->saveConfigVar('default_editor', 'RAW');

        $path = $this->getImportPath('forum_posts.csv');
        $csv = new CSV($path);

        # Iterate
        $csv->eachLine(function ($row)
		{

			$postID = $row[self::CSV_FORUM_POST_ID];

//			if (!($post = GDO_ForumPost::getById($postID)))
//			{
				try
				{
					$post = GDO_ForumPost::blank([
						'post_id' => $postID,
						'post_thread' => $row[self::CSV_FORUM_POST_TOPIC],
						'post_message' => $this->purify($row[self::CSV_FORUM_POST_MESSAGE]),
						'post_created' => Time::getDate($row[self::CSV_FORUM_POST_DATE]),
						'post_creator' => $this->convertUsernameToID($row[self::CSV_FORUM_POST_AUTHOR]),
					]);
					$post->softReplace();
				}
				catch (Exception $ex)
				{
					$tid = $row[self::CSV_FORUM_POST_TOPIC];
					GDT_Page::instance()->topResponse()->addField(GDT_Error::make()->textRaw("Forum post ID:{$postID} cannot find it's thread ID:{$tid}"));
					return;
				}
//			}

			if (@$row[self::CSV_FORUM_POST_EDITED])
			{
				$post->setVars([
					'post_edited' => Time::getDate($row[self::CSV_FORUM_POST_EDITED]),
					'post_editor' => $this->convertUsernameToID($row[self::CSV_FORUM_POST_EDITOR]),
				]);
				$post->save(false);
			}
		});
	}

	/**
	 * TBS posts are crazy.
	 * Replace a few crazy things, then purify for safety.
	 *
	 * @TODO: Analyze more posts. Sadly this has to happen before purify. No idea yet.
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	private function purify($message): string
    {
		# Replace local images
		$message = str_replace('/files/images/',
			Module_TBS::instance()->wwwPath('images/'), $message);

		# Replace crazy php tags.
		$message = str_replace('<?php', '&lt;?php', $message);
		$message = str_replace('?>', '?&gt;', $message);

		# Replace crazy html comments.
		$message = str_replace('<!--', '&lt;!--', $message);
		$message = str_replace('-->', '--&gt;', $message);

		# Purifier
        $message = Module_HTML::instance()->purify($message);
        return $message;
	}

	private function markLatestPostAsMailed()
	{
		$postId = GDO_ForumPost::table()->select('MAX(post_id)')->
		first()->exec()->fetchVar();
		Module_Forum::instance()->
		saveConfigVar('forum_mail_sent_for_post', $postId);
	}

	/**
	 * Grant challenge permissions via chall solved.
	 * Bulk insert for fast import here. :)
	 */
	public function importPermissionsFromChallSolved()
	{
		$cs_table = GDO_TBS_ChallengeSolved::table();
		$solved = $cs_table->select()->exec();
		$perm_table = GDO_UserPermission::table();
		$fields = [
			$perm_table->gdoColumn('perm_user_id'),
			$perm_table->gdoColumn('perm_perm_id'),
			$perm_table->gdoColumn('perm_created_at'),
			$perm_table->gdoColumn('perm_created_by'),
		];
		$data = [];
		$now = Time::getDate();
		/** @var $row GDO_TBS_ChallengeSolved * */
		while ($row = $solved->fetchAssoc())
		{
			$data[] = [
				'perm_user_id' => $row['cs_user'],
				'perm_perm_id' => $this->convertChallengeIdToPermId($row['cs_challenge']),
				'perm_created_at' => $now,
				'perm_created_by' => '1',
			];
			if (count($data) >= 100)
			{
				GDO_UserPermission::bulkInsert($fields, $data, 100, 'REPLACE');
				$data = [];
			}
		}
		if (count($data) > 0)
		{
			GDO_UserPermission::bulkInsert($fields, $data, 100, 'REPLACE');
		}
	}

	private function convertChallengeIdToPermId($challId)
	{
		static $MAP = [];
		if (!(isset($MAP[$challId])))
		{
			$MAP[$challId] = GDO_TBS_Challenge::findById($challId)->getPermissionID();
		}
		return $MAP[$challId];
	}

	public function importChallengeFile($entry, $fullpath)
	{
		$outPath = $this->convertChallengePath($fullpath);

		FileUtil::createDir(dirname($outPath));

		$module = $this->getModule();
		$fileData = file_get_contents($fullpath);

		# Replace CSS path
		$fileData = str_replace('/styles.css', $module->wwwPath('css/tbs_challenge.css'), $fileData);

		# Replace Challenge Lists Link
		$fileData = str_replace('/hackchallenge.php', $module->href('ChallengeLists'), $fileData);

		# Replace Image paths
		$fileData = str_replace('/files/images/', $module->wwwPath('images/'), $fileData);

		//        # Set parent target (BAD IDEA?)
		//        $fileData = str_replace('<head>', "<head>\n<base target=\"_parent\">\n", $fileData);

		# Remove chall meta (intro that is not needed anymore?)
		$fileData = preg_replace('#<body([^>]*)>.*</table></td></tr></table></div>#s', '<body$1>', (string)$fileData);

		//         # Fix windows.location (JS challs) (BAD IDEA?)
		//         $fileData = str_replace('window.location.href=', 'window.top.location.href=', $fileData);

		# Replace answer forms with solution checker
		$fileData = $this->enhanceFormsToUseSolutionChecking($fileData, $fullpath);

		# Fix URLs to /challenges/
		$fileData = str_replace('/challenges', $module->wwwPath('challenges/'), $fileData);

		# Save
		file_put_contents($outPath, $fileData);
	}

	private function convertChallengePath($fullpath)
	{
		$fullpath = Strings::substrFrom($fullpath, 'GDO/TBS/DUMP/challenges/');
		return $this->challengesPath($fullpath);
	}

	###################
	### Permissions ###
	###################

	/**
	 * Enhance forms that submit to thyself.
	 *
	 * @param string $fileData
	 * @param string $fullpath
	 *
	 * @return string
	 */
	private function enhanceFormsToUseSolutionChecking($fileData, $fullpath)
	{
		# Get relative path to self.
		$self = Strings::substrFrom($fullpath, '/TBS/DUMP');

		# Skip by exclusion rule?
		if (in_array($self, self::$NO_SIMPLE_FORM, true))
		{
			$challenge = GDO_TBS_Challenge::getByURL($self);
			if ($challenge->getStatus() === GDT_TBS_ChallengeStatus::NOT_CHECKED)
			{
				$challenge->saveVar(
					'chall_status', GDT_TBS_ChallengeStatus::IN_PROGRESS);
			}
			return $fileData;
		}

		# Replace form with checker + form
		$count = 0;
		$fileData = preg_replace_callback(
			'#<form action="' . $self . '/index.php".*</form>#ms',
			[$this, '_enhanceForms'], $fileData, 1, $count);
		if (!$count)
		{
			$fileData = preg_replace_callback(
				'#<form action="' . $self . '".*</form>#ms',
				[$this, '_enhanceForms'], $fileData, 1, $count);
		}

		# On replace mark this challenge as not tried, if still not checked.
		if ($count)
		{
			$challenge = GDO_TBS_Challenge::getByURL($self);
			if ($challenge->getStatus() === GDT_TBS_ChallengeStatus::NOT_CHECKED)
			{
				$challenge->saveVar('chall_status', GDT_TBS_ChallengeStatus::NOT_TRIED);
			}
		}

		# Done
		return $fileData;
	}

	/**
	 * Prepend solution checker code to simple forms.
	 *
	 * @param array $matches
	 *
	 * @return string
	 */
	public function _enhanceForms($matches)
	{
		$path = GDO_PATH . 'GDO/TBS/chall_include_checker.php';
		$solutionCheckerCode = sprintf('<?php require "%s"; ?>', $path);
		return $solutionCheckerCode . "\n" . $matches[0];
	}


}
