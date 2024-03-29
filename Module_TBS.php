<?php
declare(strict_types=1);
namespace GDO\TBS;

use GDO\Classic\Module_Classic;
use GDO\Contact\Method\Form;
use GDO\Core\Application;
use GDO\Core\CSS;
use GDO\Core\GDO_Module;
use GDO\Core\GDT_Checkbox;
use GDO\Core\GDT_Response;
use GDO\Core\GDT_Secret;
use GDO\Core\GDT_Template;
use GDO\Core\GDT_UInt;
use GDO\Core\Method;
use GDO\Date\GDT_Duration;
use GDO\DB\Query;
use GDO\Net\GDT_Url;
use GDO\Register\GDO_UserActivation;
use GDO\TBS\Install\InstallTBS;
use GDO\TBS\Method\Welcome;
use GDO\UI\GDT_Bar;
use GDO\UI\GDT_Card;
use GDO\UI\GDT_Container;
use GDO\UI\GDT_Link;
use GDO\User\GDO_User;
use GDO\User\GDT_ACLRelation;
use GDO\Votes\Module_Votes;

/**
 * TBS Website Revival as phpgdo7 module.
 *
 * - Read the Import instructions
 * - Solution to crypto1 is ahdefjuklgrbdsegf
 *
 * @TODO BBDecoder in Module_TBSBBMessage
 * @TODO Create a new challenge \o/
 *
 * @version 7.0.3
 * @author gizmore
 * @license Property of Erik and TBS
 */
final class Module_TBS extends GDO_Module
{

	public int $priority = 110;

	public string $license = 'TBS';

	public function isSiteModule(): bool
	{
		return true;
	}

    public function defaultMethod(): Method
    {
        return Welcome::make();
    }

    /**
	 * Indicate module provides the tbs theme.
	 */
	public function getTheme(): ?string
	{
		return 'tbs';
	}

	public function onLoadLanguage(): void
	{
		$this->loadLanguage('lang/tbs');
	}

	public function href_administrate_module(): string
	{
		return href('TBS', 'Admin');
	}

	# #############
	# ## Module ###
	# #############
	public function getDependencies(): array
	{
		return [
			'Admin',
			'Avatar',
			'Captcha',
			'Classic',
			'Country',
			'Contact',
			'Cronjob',
			'CSS',
			'Favicon',
			'FontAwesome',
			'Forum',
			'Javascript',
			'JQueryAutocomplete',
			'Login',
			'Markdown',
			'Mibbit',
			'News',
			'OnlineUsers',
			'Perf',
			'PM',
			'Python',
			'Recovery',
			'Register',
			'Statistics',
		];
	}

	/**
	 * Database tables to install.
	 */
	public function getClasses(): array
	{
		return [
			GDO_TBS_Challenge::class,
			GDO_TBS_ChallengeSolveAttempt::class,
			GDO_TBS_ChallengeSolved::class,
			GDO_TBS_ChallengeSolvedCategory::class,
		];
	}

	# #############
	# ## Config ###
	# #############
	public function getConfig(): array
	{
        $key = @include(GDO_PATH . 'GDO/TBS/xauth_token.php');
        $key = $key ?: 'you wanna know this key? :)';
		return [
			GDT_Duration::make('chall_solve_timeout')->initial('5m'),
			GDT_UInt::make('chall_solve_attempts')->initial('5'),
			GDT_Secret::make('chall_solver_user')->initial('gizmore3'),
			GDT_Secret::make('chall_solver_pass')->initial('11111111'),
			GDT_Secret::make('tbs_xauth_key')->initial($key),
		];
	}

	protected function getACLDefaults(): array
	{
		return [
			'tbs_website' => [
				GDT_ACLRelation::ALL,
				0,
				null,
			],
			'tbs_category' => [
				GDT_ACLRelation::ALL,
				0,
				null,
			],
		];
	}

	public function getUserSettings(): array
	{
		return [
			GDT_Checkbox::make('tbs_ranked')->initial('1')
				->notNull()
				->noacl(),
			GDT_Url::make('tbs_website')->allowExternal(),
			GDT_TBS_ChallengeCategory::make('tbs_category')->emptyInitial('tbs_category'),
		];
	}

	public function onInstall(): void
	{
		InstallTBS::onInstall();
	}

	public function onIncludeScripts(): void
	{
		if (Application::$INSTANCE->hasTheme('tbs'))
		{
			$this->addJS('js/tbs.js');
			$this->addCSS('css/gdo7-tbs.css');
			Module_Classic::instance()->addJS('js/gdo7-classic.js');
		}
	}

	/**
	 * Ignore these folders in documentation, code statistics, etc.
	 * Those are usally 3rd party libraries or data folders, and they default to npm/yarn/composer deployment folders.
	 */
	public function thirdPartyFolders(): array
	{
		return [
			'bin/',
			'challenges/',
			'downloads/',
			'DUMP/',
			'HIDDEN/',
			'HIDDEN_EXAMPLE/',
			'INPUT/',
			'scripts/',
			'tutorials/',
			'vulnerable_code/',
		];
	}

	public function cfgSolveTimeout(): int
	{
		return $this->getConfigValue('chall_solve_timeout');
	}

	public function cfgSolveAttempts(): int
	{
		return $this->getConfigValue( 'chall_solve_attempts');
	}

	public function cfgSolveUser(): ?string
	{
		return $this->getConfigVar('chall_solver_user');
	}

	public function cfgSolvePass(): ?string
	{
		return $this->getConfigVar('chall_solver_pass');
	}

	public function cfgXAuthKey(): ?string
	{
		return $this->getConfigVar('tbs_xauth_key');
	}

	# #############
	# ## Render ###
	# #############

	public function tutorialWWWPath(): string
	{
		return $this->wwwPath('tutorials/');
	}

	/**
	 * Get TBS Admin Section tabs.
	 */
	public function barAdminTabs(): GDT_Bar
	{
		$tabs = GDT_Bar::make()->horizontal();

		$tabs->addField(GDT_Link::make('link_tbs_import')->href(href('TBS', 'ImportRealTBS')));
		$tabs->addField(GDT_Link::make('link_tbs_recalc')->href(href('TBS', 'RecalcPoints')));

		return $tabs;
	}

	# ############
	# ## Hooks ###
	# ############

	public function rawIcon(string $path, string $title = ''): string
	{
		$path = $this->wwwPath("images/{$path}");
		$title = $title ? " title=\"{$title}\"" : $title;
		return sprintf('<img%s src="%s" alt="icon" />', $title, $path);
	}

	/**
	 * Add fields to profile card.
	 */
	public function hookProfileCard(GDO_User $user, GDT_Card $card): void
	{
		$card->addField($this->userSetting($user, 'tbs_website'));
		$card->addField($this->userSetting($user, 'tbs_ranked'));
	}

	public function hookProfileTemplate(GDO_User $user): void
	{
		echo $this->php('profile.php', [
			'user' => $user,
		]);
	}

	public function hookDecoratePostUser(GDT_Card $card, GDT_Container $cont, GDO_User $user): void
	{
		# Add likes
		$likes = Module_Votes::instance()->userSettingVar($user, 'likes');
		$cont->addField(GDT_UInt::make()->initial($likes)
			->label('btn_likes'));

		# Add groupmaster icons
		$cont2 = GDT_Container::make()->horizontal()->addClass('badge-container');
		foreach (GDT_TBS_ChallengeCategory::$CATS as $category)
		{
			$cont2->addField(GDT_TBS_GroupmasterIcon::make()->category($category)
				->gdo($user));
		}
		$cont->addField($cont2);
	}

	public function hookMethodQueryTable_Forum_Thread(Query $query): void
	{
		$join = 'LEFT JOIN gdo_tbs_challengesolvedcategory AS csc ON csc_user=post_creator';
		$query->join($join);
	}

	public function hookUserActivated(GDO_User $user, GDO_UserActivation $activation = null): void
	{
		# Create scoring upon activation.
		GDO_TBS_ChallengeSolvedCategory::updateUser($user);
	}

	# #########################
	# ## Contact Form Hooks ###
	# #########################

	public function hookBeforeExecute(Method $method, GDT_Response $response): void
	{
		if ($method instanceof Form)
		{
			$this->addContactCSS();
			$response->addField(GDT_Template::make()->template('TBS', 'page/contact_before.php'));
		}
	}

	private function addContactCSS(): void
	{
		$dir = $this->wwwPath();
		$css = <<<END
        dt {
          background-image: url('{$dir}images/backgrounds/headline2.png');
          background-color: #911501;
          color:#FFF;
          font-family: "Georgia", "Times New Roman", serif;
          font-size: 14px;
          line-height: 14px;
          font-weight: normal;
          padding:1px 0px 2px 4px;
          margin-top: 10px;
          border-style:solid;
          border-color:#000;
          border-width:1px;
        }
        dd {
          background-color: #CCB;
          border-color: #000;
          border-style: solid;
          border-width: 0px 1px 1px 1px;
          padding: 4px 8px 4px 4px;
          font-family: "Verdana",sans-serif;
          font-size: 12px;
          line-height: 150%;
          text-align: justify;
        }
        END;
		CSS::addInline($css);
	}

	public function hookAfterExecute(Method $method, GDT_Response $response): void
	{
		if ($method instanceof Form)
		{
			$response->addField(GDT_Template::make()->template('TBS', 'page/contact_after.php'));
		}
	}

}
