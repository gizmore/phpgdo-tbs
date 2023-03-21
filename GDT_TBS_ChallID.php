<?php
namespace GDO\TBS;

use GDO\Core\GDT_UInt;

/**
 * A challenge ID. Why is this needed?
 */
final class GDT_TBS_ChallID extends GDT_UInt
{

	public static function make(string $name = null): self
	{
		$obj = parent::make($name);
		$obj->labelNone();
		return $obj;
	}

	public function renderCell(): string
	{
		return $this->getVar() . ':';
	}

}
