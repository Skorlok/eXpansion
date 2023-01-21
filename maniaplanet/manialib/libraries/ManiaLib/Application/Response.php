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

namespace ManiaLib\Application;

/**
 * Allows to pass values between filters, controllers and views
 * 
 * @method \ManiaLib\Application\Response getInstance()
 */
class Response extends \ManiaLib\Utils\Singleton
{

	protected $vars;
	protected $body;
	protected $views;

	/**
	 * @var \ManiaLib\Application\DialogHelper
	 */
	public $dialog;
	protected $registerDefaultViews = true;

	/**
	 * @var \ManiaLib\Application\Rendering\RendererInterface
	 */
	protected $renderer;
	public $cache;

	function __construct()
	{
		$this->vars = array();
		$this->views = array();
		$this->body = '';

		$config = Config::getInstance();

		$this->setRenderer($config->getRenderer());
	}

	public function __set($name, $value)
	{
		$this->vars[$name] = $value;
	}

	public function __get($name)
	{
		if(array_key_exists($name, $this->vars))
		{
			return $this->vars[$name];
		}
		else
		{
			return null;
		}
	}

	public function get($name, $default=null)
	{
		if(array_key_exists($name, $this->vars))
		{
			return $this->vars[$name];
		}
		else
		{
			return $default;
		}
	}

	function setRenderer($renderer)
	{
		if(!class_exists($renderer))
		{
			throw new \Exception(sprintf('%s does not exists', $renderer));
		}
		$this->renderer = new $renderer;
		$this->registerDefaultViews = ($renderer == 'ManiaLib\\Application\\Rendering\\Manialink');
	}

	/**
	 * Returns all defined response vars
	 * @return array
	 */
	public function getAll()
	{
		$params = $this->vars;
		$params['dialog'] = $this->dialog;
		return $params;
	}

	public function getViewClassName($controllerName, $actionName)
	{
		$className =
			Config::getInstance()->namespace.
			'\\'.
			'Views'.
			'\\'.
			$controllerName;

		if($actionName)
		{
			$className .= '\\'.ucfirst($actionName);
		}

		return $className;
	}

	public function registerDialog(\ManiaLib\Application\DialogHelper $dialog)
	{
		$this->dialog = $dialog;
	}

	/**
	 * ManiaLib\Application\Response::registerView() now only takes one
	 * parameter: the view name.
	 *
	 * It can be a class name when using ManiaLib views for Manialinks
	 * (eg. ManiaLibDemo\Views\Home\Index)
	 *
	 * It can also be a ressource name when using Simple Templates: it is the
	 * filename starting from the ressource folder, less the extension
	 * eg. ManiaLib\Views\Example will map to ressources/ManiaLib/Views/Example.php
	 */
	public function registerView($viewName)
	{
		$this->views[] = $viewName;
	}

	function resetViews()
	{
		$this->views = array();
	}

	function redirect($URL)
	{
		$this->renderer->redirect($URL);
	}

	function disableDefaultViews()
	{
		$this->registerDefaultViews = false;
	}

	protected function registerDefaultViews()
	{
		if($this->dialog)
		{
			array_unshift($this->views, $this->dialog->className);
		}

		$config = Config::getInstance();
		$viewsNS = $config->getViewsNS();
		$headerIncluded = false;
		$footerIncluded = false;

		foreach($viewsNS as $namespace)
		{
			if(!$headerIncluded && class_exists($namespace.'Header'))
			{
				array_unshift($this->views, $namespace.'Header');
				$headerIncluded = true;
			}
			if(!$footerIncluded && class_exists($namespace.'Footer'))
			{
				array_push($this->views, $namespace.'Footer');
				$footerIncluded = true;
			}
		}
	}

	function registerErrorView()
	{
		$this->disableDefaultViews();
		$this->resetViews();

		$config = Config::getInstance();
		$viewsNS = $config->getViewsNS();

		foreach($viewsNS as $namespace)
		{
			if(call_user_func(array($this->renderer, 'exists'), $namespace.'Error'))
			{
				$this->registerView($namespace.'Error');
				break;
			}
		}
	}
	
	function registerException(\Exception $e)
	{
		$this->renderer->registerException($e);
	}

	public function render()
	{
		if($this->registerDefaultViews)
		{
			$this->registerDefaultViews();
		}

		ob_start();
		try
		{
			foreach($this->views as $view)
			{
				call_user_func(array($this->renderer, 'render'), $view);
			}
			$this->body = ob_get_contents();
		}
		catch(\Exception $e)
		{
			ob_end_clean();
			throw $e;
		}
		ob_end_clean();
		call_user_func(array($this->renderer, 'header'));
		echo $this->body;
	}

}

?>