<?php
namespace GDO\TBS;

use GDO\Core\GDT;
use GDO\DB\Cache;
use GDO\OnlineUsers\GDT_OnlineUsers;
use GDO\Statistics\GDO_Statistic;
use GDO\UI\GDT_Image;
use GDO\User\GDO_User;

/**
 * Render the TOP bar.
 * Image.
 * Page counter.
 * User counter.
 * Online counter.
 * Chall counter.
 *
 * @version 7.0.2
 * @since 6.10.0
 * @author gizmore
 */
final class GDT_TBS_TopBar extends GDT
{

	public function renderHTML(): string
	{
		$mo = Module_TBS::instance();

		$html = "<div id=\"tbs_top_bar\">\n";

		$countViews = GDO_Statistic::simpleHitsTotal();
		$countChalls = GDO_TBS_Challenge::getChallengeCount();
		$countUsers = $this->getMemberCount();
		$countOnline = GDT_OnlineUsers::getOnlineUsers();

		$views = t('tbs_top_views');
		$challs = t('tbs_top_challs');
		$users = t('tbs_top_users');
		$online = t('tbs_top_online');

		$img = GDT_Image::make()->src($mo->wwwPath('img/header_2.jpeg'));
		$img->addClass('fl');

		$html .= $img->renderCell();

		$html .= "<div id=\"tbs_top_counts\">\n";
		$html .= "<table>\n";
		$html .= "<tbody>\n";

		$html .= "<tr>\n";
		$html .= "<th>{$views}: </th><td>{$countViews}</td>\n";
		$html .= "<th>{$challs}: </th><td>{$countChalls}</td>\n";
		$html .= "</tr>\n";

		$html .= "<tr>\n";
		$html .= "<th>{$users}: </th><td>{$countUsers}</td>\n";
		$html .= "<th>{$online}: </th><td>{$countOnline}</td>\n";
		$html .= "</tr>\n";

		$html .= '</tbody>';
		$html .= '</table>';

		$html .= '</div>';
		$html .= "<div style=\"clear: both;\"></div>\n";
		$html .= '</div>';

		return $html;
	}

	public function getMemberCount(): int
	{
		static $cache;
		if ($cache === null)
		{
			$key = 'tbs_membercount';
			if (null === ($cache = Cache::get($key)))
			{
				$cache = $this->queryMemberCount();
				Cache::set($key, $cache);
			}
		}
		return $cache;
	}

	private function queryMemberCount(): int
	{
		return GDO_User::table()->countWhere('user_type="member"');
	}

}
