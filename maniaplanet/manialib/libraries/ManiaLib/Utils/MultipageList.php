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

namespace ManiaLib\Utils;

/**
 * This class helps to create multipage lists. Maybe difficult to use at
 * first... Doc should be written about it...
 */
class MultipageList
{

	protected $size;
	protected $urlParamName = 'page';
	protected $urlPageName = null;
	protected $currentPage;
	protected $defaultPage = 1;
	protected $perPage;
	protected $pageNumber;
	protected $hasMorePages;
	protected $linkCallback = 'setManialink';

	/**
	 * @var \ManiaLib\Gui\Cards\PageNavigator
	 */
	public $pageNavigator;

	function __construct($perPage = 8)
	{
		$this->pageNavigator = new \ManiaLib\Gui\Cards\PageNavigator;
		$this->perPage = $perPage;
	}

	function setSize($size)
	{
		$this->size = $size;
		if($this->getCurrentPage() > $this->getPageNumber())
			$this->currentPage = $this->getPageNumber();
	}

	function setPerPage($perPage)
	{
		$this->perPage = $perPage;
	}

	function setCurrentPage($page)
	{
		$this->currentPage = $page;
	}

	function setDefaultPage($page)
	{
		$this->defaultPage = $page;
	}

	function setUrlParamName($name)
	{
		$this->urlParamName = $name;
	}

	function setUrlPageName($file)
	{
		$this->urlPageName = $file;
	}

	function setLinkCallback($linkCallback)
	{
		$this->linkCallback = $linkCallback;
	}

	function goToLastPage()
	{
		$this->currentPage = $this->getPageNumber();
	}

	function getCurrentPage()
	{
		if($this->currentPage === null)
		{
			$request = \ManiaLib\Application\Request::getInstance();
			$this->currentPage = (int) $request->get($this->urlParamName,
					$this->defaultPage);
		}
		if($this->currentPage < 1)
		{
			$this->currentPage = 1;
		}
		return $this->currentPage;
	}

	function getPageNumber()
	{
		if(!$this->pageNumber && $this->perPage)
		{
			$this->pageNumber = ceil($this->size / $this->perPage);
		}
		return $this->pageNumber;
	}

	function getUrlParamName()
	{
		return $this->urlParamName;
	}

	/**
	 * @return array[int] offset, length
	 */
	function getLimit()
	{
		$offset = ($this->getCurrentPage() - 1) * $this->perPage;
		$length = $this->perPage;
		return array($offset, $length);
	}
	
	function getPerPage()
	{
		return $this->perPage;
	}

	function setHasMorePages($hasMorePages)
	{
		$this->hasMorePages = $hasMorePages;
	}

	function checkArrayForMorePages(&$array)
	{
		list($offset, $length) = $this->getLimit();
		$hasMorePages = (count($array) == $length + 1);
		if($hasMorePages)
		{
			array_pop($array);
		}
		$this->hasMorePages = $hasMorePages;
		$this->pageNavigator->showText(false);
	}

	function hasMorePages()
	{
		if($this->hasMorePages === null)
		{
			return $this->currentPage < $this->pageNumber;
		}
		return $this->hasMorePages;
	}

	function savePageNavigator()
	{
		$request = \ManiaLib\Application\Request::getInstance();

		if($this->hasMorePages !== null)
		{
			if($this->hasMorePages)
			{
				$this->setSize($this->getCurrentPage() * $this->perPage + 1);
			}
			else
			{
				$this->setSize($this->getCurrentPage() * $this->perPage);
			}
		}

		if($this->getPageNumber() > 1)
		{
			$ui = $this->pageNavigator;
			$ui->setPageNumber($this->getPageNumber());
			$ui->setCurrentPage($this->getCurrentPage());

			if($ui->isLastShown())
			{
				$request->set($this->urlParamName, 1);
				call_user_func(array($ui->arrowFirst, $this->linkCallback),
					$request->createLink($this->urlPageName));

				$request->set($this->urlParamName, $this->getPageNumber());
				call_user_func(array($ui->arrowLast, $this->linkCallback),
					$request->createLink($this->urlPageName));
			}

			if($ui->isFastNextShown())
			{
				$request->set($this->urlParamName, $this->currentPage + 5);
				call_user_func(array($ui->arrowFastNext, $this->linkCallback),
					$request->createLink($this->urlPageName));

				if($this->currentPage > 1)
				{
					$request->set($this->urlParamName,
						$this->currentPage - 5 < 1 ? 1 : $this->currentPage - 5);
					call_user_func(array($ui->arrowFastPrev, $this->linkCallback),
						$request->createLink($this->urlPageName));
				}
			}


			if($this->currentPage < $this->pageNumber)
			{
				$request->set($this->urlParamName, $this->currentPage + 1);
				call_user_func(array($ui->arrowNext, $this->linkCallback),
					$request->createLink($this->urlPageName));
			}

			if($this->currentPage > 1)
			{
				$request->set($this->urlParamName, $this->currentPage - 1);
				call_user_func(array($ui->arrowPrev, $this->linkCallback),
					$request->createLink($this->urlPageName));
			}

			$request->set($this->urlParamName, $this->currentPage);

			$ui->save();
		}
	}

}

?>