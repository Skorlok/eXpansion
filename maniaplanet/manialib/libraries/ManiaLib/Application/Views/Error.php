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

namespace ManiaLib\Application\Views;

use ManiaLib\Gui\Elements\Button;
use ManiaLib\Gui\Elements\Bgs1;
use ManiaLib\Gui\Elements\Label;
use ManiaLib\Gui\Cards\Panel;
use ManiaLib\Gui\Manialink;

/**
 * Default error page
 */
class Error extends \ManiaLib\Application\View
{
	protected $width;
	protected $height;
	protected $message;

	protected function onConstruct()
	{
		$this->width = $this->response->get('width', 100);
		$this->height =  $this->response->get('height', 75);
		$this->message = $this->response->message ?: '$<$oOops!$>'."\n".'An error occured.';
	}


	function display()
	{
		Manialink::load();
		{
			$ui = new Panel($this->width, $this->height);
			$ui->setAlign('center', 'center');
			$ui->title->setStyle(Label::TextTitleError);
			$ui->titleBg->setSubStyle(Bgs1::BgTitle2);
			$ui->title->setText('Error');
			$ui->save();

			$ui = new Label($this->width - 4);
			$ui->enableAutonewline();
			$ui->setAlign('center', 'center');
			$ui->setPosition(0, -5, 1);
			$ui->setText($this->message);
			$ui->save();
			
			$ui = new Button();
			$ui->setText($this->response->errorButtonMessage ?: 'Back');
			$ui->setManiazone($this->response->errorManialink ?: $this->response->backLink);
			$ui->setPosition(0, -($this->height/2)+12, 1);
			$ui->setHalign('center');
			$ui->save();
		}
		Manialink::render();
	}
}

?>