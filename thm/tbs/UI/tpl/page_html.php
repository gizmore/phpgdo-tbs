<?php
namespace GDO\TBS\thm\tbs\UI\tpl;

use GDO\Core\Javascript;
use GDO\Core\Module_Core;
use GDO\Core\Website;
use GDO\TBS\GDT_TBS_Sidebar;
use GDO\TBS\GDT_TBS_TopBar;
use GDO\UI\GDT_Loading;
use GDO\UI\GDT_Page;

/**
 * TBS page layout.
 */
/** @var $page GDT_Page * */
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?=Website::displayTitle()?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<meta name="generator" content="GDO v<?=Module_Core::GDO_REVISION?>"/>
	<?=Website::displayMeta()?>
	<?=Website::displayLink()?>
</head>
<body>
<div id="gdo-pagewrap">
	<div class="gdo-body">
		<?=GDT_TBS_Sidebar::make()->render()?>
		<?=GDT_TBS_TopBar::make()->render()?>
		<div class="gdo-main">
			<?php
			#$page->topBar()->render()
			?>
			<?=$page->topResponse()->render()?>
			<?=$page->html?>
		</div>
	</div>
	<div><?=$page->bottomBar()->render()?></div>
</div>
<?=GDT_Loading::make()->render()?>
<?=Javascript::displayJavascripts()?>
</body>
</html>
