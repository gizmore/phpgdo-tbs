<?php
namespace GDO\TBS\Method;

use GDO\Admin\MethodAdmin;
use GDO\Core\GDT;
use GDO\Form\GDT_AntiCSRF;
use GDO\Form\GDT_Form;
use GDO\Form\GDT_Submit;
use GDO\Form\MethodForm;
use GDO\TBS\GDO_TBS_ChallengeSolvedCategory;
use GDO\TBS\Module_TBS;
use GDO\UI\GDT_Page;

final class RecalcPoints extends MethodForm
{

	use MethodAdmin;

	public function isTransactional(): bool { return false; }

	public function createForm(GDT_Form $form): void
	{
		$form->text('tbs_reacalc_points_info');
		$form->addFields(
			GDT_AntiCSRF::make(),
		);
		$form->actions()->addField(GDT_Submit::make());
	}

	public function formValidated(GDT_Form $form): GDT
	{
		set_time_limit(60 * 60);
		ini_set('memory_limit', '512M');
		GDO_TBS_ChallengeSolvedCategory::updateUsers();
		return $this->message('tbs_msg_recalced');
	}

	public function onRenderTabs(): void
	{
		$this->renderAdminBar();
		GDT_Page::$INSTANCE->topResponse()->addField(
			Module_TBS::instance()->barAdminTabs()
		);
	}

}
