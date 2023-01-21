<?php
/**
 * ManiaLib - Lightweight PHP framework for Manialinks
 * 
 * @see         http://code.google.com/p/manialib/
 * @copyright   Copyright (c) 2009-2011 NADEO (http://www.nadeo.com)
 * @license     http://www.gnu.org/licenses/lgpl.html LGPL License 3
 * @version     $Revision$:
 * @author      $Author$:
 * @date        $Date$:
 */

namespace ManiaLib\Application\Views\Dialogs;

use ManiaLib\Gui\Manialink;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Quad;

/**
 * @deprecated
 */
class OneButton extends \ManiaLib\Application\View implements DialogInterface
{

	function display()
	{
		$ui = new Bgs1(320, 200);
		$ui->setAlign('center', 'center');
		$ui->setPosition(0, 0, 14);
		$ui->setSubStyle(Bgs1::BgWindow1);
		$ui->save();

		Manialink::beginFrame(
			$this->response->dialog->posX, $this->response->dialog->posY,
			$this->response->dialog->posZ);
		{
			$ui = new \ManiaLib\Gui\Cards\Dialogs\OneButton(
					$this->response->dialog->width, $this->response->dialog->height);
			$ui->setAlign('center', 'center');
			$ui->title->setText($this->response->dialog->title);
			$ui->titleBg->setSubStyle(Bgs1::BgTitle3_1);
			$ui->text->setText($this->response->dialog->message);
			$ui->button->setText($this->response->dialog->buttonLabel);
			$ui->button->setManiazone($this->response->dialog->buttonManialink);
			if($this->response->dialog->buttonAddplayerid)
			{
				$ui->button->addPlayerId();
			}
			$ui->save();
		}
		Manialink::endFrame();
	}

}

?>