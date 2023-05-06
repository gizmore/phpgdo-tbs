<?php
declare(strict_types=1);
namespace GDO\TBS;

use GDO\Core\GDT_Enum;
use GDO\Core\WithGDO;
use GDO\UI\GDT_Link;
use GDO\UI\GDT_Tooltip;
use GDO\User\GDO_User;

/**
 * Indicate challenge import status.
 *
 * @author gizmore
 */
final class GDT_TBS_ChallengeStatus extends GDT_Enum
{

	use WithGDO;

	final public const NOT_CHECKED = 'not_checked';
	final public const NOT_TRIED = 'not_tried';
	final public const IN_PROGRESS = 'in_progress';
	final public const WONT_FIX = 'wont_fix';
	final public const NEED_FILES = 'need_files';
	final public const WORKING = 'working';

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
		$this->emptyLabel('tbs_chall_status_unknown');
		$this->notNull();
		$this->initial(self::NOT_CHECKED);
		$this->tooltip = GDT_Tooltip::make('chall_tooltip');
		$this->editLink = GDT_Link::make('chall_edit_link');
	}

	public function displayVar(string $var = null): string
	{
		return t("tbs_tt_{$var}");
	}

	public function renderCell(): string
	{
		# Build status tooltip icon.
		$var = $this->getVar();
		$key = "tbs_tt_{$var}";
		$tt = $this->tooltip->tooltip($key)->render();
		$color = self::$COLORS[$var];
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

	public function getChallenge(): ?GDO_TBS_Challenge
	{
		return $this->gdo ?: null;
	}

}
