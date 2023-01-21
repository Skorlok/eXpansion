<?php
/**
 */
namespace ManiaLive\Gui;

class Utils
{
	public static function destroy($object)
	{
		if ($object instanceof Control)
		{
			$object->destroy();
		}
	}
}
?>