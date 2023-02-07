<?php
namespace GDO\TBS\Method;

use GDO\Admin\MethodAdmin;
use GDO\TBS\Module_TBS;
use GDO\UI\GDT_Page;
use GDO\Form\GDT_Form;
use GDO\Form\MethodForm;
use GDO\Form\GDT_Submit;
use GDO\Form\GDT_AntiCSRF;
use GDO\Core\GDT_Checkbox;
use GDO\DB\Database;
use GDO\TBS\Install\TBSImport;
use GDO\Cronjob\Module_Cronjob;

/**
 * Import data from the INPUT/ folder.
 *
 * @see README.md for import instructions.
 *     
 * @author gizmore
 */
final class ImportRealTBS extends MethodForm
{
	use MethodAdmin;

	public function isTransactional(): bool
	{
		return false;
	}

	/**
	 * Before execute we add the top tabs.
	 *
	 * @see MethodAdmin
	 */
	public function onRenderTabs(): void
	{
		$this->renderAdminBar();
		GDT_Page::$INSTANCE->topResponse()->addField(Module_TBS::instance()->barAdminTabs());
	}

	public function execute()
	{
		if (GDO_DB_DEBUG)
		{
			return $this->error('err_db_debug_level_too_high', [
				GDO_DB_DEBUG
			]);
		}
		if (module_enabled('Cronjob'))
		{
			Module_Cronjob::instance()->saveVar('module_enabled', '0');
			return $this->message('err_cronjob_disable');
		}
		return parent::execute();
	}

	public function createForm(GDT_Form $form): void
	{
		$form->text('tbs_import_info');
		$form->addFields(GDT_Checkbox::make('import_users')->initial('0'),
			GDT_Checkbox::make('import_challs')->initial('0'), GDT_Checkbox::make('import_chall_solved')->initial('0'),
			GDT_Checkbox::make('import_forum')->initial('0'), GDT_Checkbox::make('import_permissions')->initial('0'),
			GDT_AntiCSRF::make(),);
		$form->actions()->addField(GDT_Submit::make());
	}

	function formValidated(GDT_Form $form)
	{
		$importer = new TBSImport();
		try
		{
			$importer->import($form->getFormVars());
		}
		catch (\Throwable $e)
		{
			throw $e;
		}
		finally
        {
			Database::instance()->enableForeignKeyCheck();
			Module_Cronjob::instance()->saveVar('module_enabled', '1');
		}
		return $this->message('tbs_importer_done');
	}

}
