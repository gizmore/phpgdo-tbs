<?php
namespace GDO\TBS\thm\tbs\UI\tpl;

use GDO\UI\GDT_Message;

/** @var $field GDT_Message * */
?>
<div class="gdt-container<?=$field->classError()?>">
	<label<?=$field->htmlForID()?>><?=$field->htmlIcon()?><?=$field->renderLabel()?></label>
	<textarea
   <?=$field->htmlID()?>
   class="<?=$field->classEditor()?>"
   <?=$field->htmlName()?>
   rows="6"
   <?=$field->htmlRequired()?>
		<?=$field->htmlDisabled()?>><?=html($field->getVarInput())?></textarea>
	<?=$field->htmlError()?>
</div>
