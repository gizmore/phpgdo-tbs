<?php
namespace GDO\TBS\Test;

use GDO\TBS\GDO_TBS_Challenge;
use GDO\TBS\GDO_TBS_ChallengeSolvedCategory;
use GDO\TBS\Method\ChallengeLists;
use GDO\Tests\GDT_MethodTest;
use GDO\Tests\TestCase;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertTrue;

/**
 * Test a few things :)
 *
 * @author gizmore
 */
final class TBSTest extends TestCase
{

	public static function testChallCreation()
	{
		$chall = GDO_TBS_Challenge::blank([
			'chall_order' => '1',
			'chall_category' => 'JavaScript',
			'chall_title' => 'Simple',
			'chall_url' => 'challenge/1/Test',
			'chall_solution' => 'test',
		])->insert();
		assertTrue($chall->isPersisted());
	}

	public function testUpdateQuery()
	{
		GDO_TBS_ChallengeSolvedCategory::updateUsers();
		assertTrue(true);
	}

	/**
	 * GDO Core rendering test.
	 */
	public function testChallengeLists()
	{
		# 4 times the string JavaScript has to appear.
		$r = GDT_MethodTest::make()->method(ChallengeLists::make())->execute();
		$html = $r->renderHTML();
		assertEquals(13, substr_count($html, 'JavaScript'), 'Test if challenge list category is only rendered once.');
	}

}
