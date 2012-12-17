<?php

/*
 * This file is part of Fork CMS.
 *
 * For the full copyright and license information, please view the license
 * file that was distributed with this source code.
 */

/**
 * This is the categories-action, it will display the overview of faq categories
 *
 * @author Lester Lievens <lester.lievens@netlash.com>
 * @author Annelies Van Extergem <annelies.vanextergem@netlash.com>
 * @author Jelmer Snoeck <jelmer.snoeck@netlash.com>
 * @author SIESQO <info@siesqo.be>
 */
class BackendFaqCategories extends BackendBaseActionIndex
{
	/**
	 * Deny the use of multiple categories
	 *
	 * @param bool
	 */
	private $multipleCategoriesAllowed;

	/**
	 * Execute the action
	 */
	public function execute()
	{
		parent::execute();

		// TODO extract
		$this->twig();

		$this->loadDataGrid();
		$this->parse();

		// TODO remove exception silencing.
		// There is no categories.tpl anymore, so BackendBaseActionIndex#display()
		// will throw an exception.
		try
		{
			$this->display();
		}
		catch(Exception $e)
		{
			// empty on purpose.
		}
	}

	/**
	 * Loads the dataGrid
	 */
	private function loadDataGrid()
	{
		// are multiple categories allowed?
		$this->multipleCategoriesAllowed = BackendModel::getModuleSetting('faq', 'allow_multiple_categories', true);

		// create dataGrid
		$this->dataGrid = new BackendDataGridDB(BackendFaqModel::QRY_DATAGRID_BROWSE_CATEGORIES, BL::getWorkingLanguage());
		$this->dataGrid->setHeaderLabels(array('num_items' => SpoonFilter::ucfirst(BL::lbl('Amount'))));
		if($this->multipleCategoriesAllowed) $this->dataGrid->enableSequenceByDragAndDrop();
		else $this->dataGrid->setColumnsHidden(array('sequence'));
		$this->dataGrid->setRowAttributes(array('id' => '[id]'));
		$this->dataGrid->setPaging(false);

		// check if this action is allowed
		if(BackendAuthentication::isAllowedAction('index'))
		{
			$this->dataGrid->setColumnFunction(array(__CLASS__, 'setClickableCount'), array('[num_items]', BackendModel::createURLForAction('index') . '&amp;category=[id]'), 'num_items', true);
		}

		// check if this action is allowed
		if(BackendAuthentication::isAllowedAction('edit_category'))
		{
			$this->dataGrid->setColumnURL('title', BackendModel::createURLForAction('edit_category') . '&amp;id=[id]');
			$this->dataGrid->addColumn('edit', null, BL::lbl('Edit'), BackendModel::createURLForAction('edit_category') . '&amp;id=[id]', BL::lbl('Edit'));
		}
	}

	/**
	 * Parse & display the page
	 */
	protected function parse()
	{
		echo $this->twigRender();
	}

	/**
	 * Convert the count in a human readable one.
	 *
	 * @param int $count
	 * @param string $link
	 * @return string
	 */
	public static function setClickableCount($count, $link)
	{
		// redefine
		$count = (int) $count;
		$link = (string) $link;

		// return link in case of more than one item, one item, other
		if($count > 1) return '<a href="' . $link . '">' . $count . ' ' . BL::getLabel('Questions') . '</a>';
		if($count == 1) return '<a href="' . $link . '">' . $count . ' ' . BL::getLabel('Question') . '</a>';
		return '';
	}



	//{{{ TWIG

	/**
	 * Setup Twig environment.
	 */
	private function twig()
	{
		$this->twig = new \Twig_Environment($this->twigLoader(), $this->twigConfig());
		$this->twigFilters();
		$this->twigGlobals();
	}

	/**
	 * @return Twig_LoaderInterface The template loader for the Twig environment.
	 */
	private function twigLoader()
	{
		$loader = new \Twig_Loader_Filesystem(BACKEND_CORE_PATH . '/layout/templates');
		$loader->addPath(BACKEND_MODULE_PATH . '/layout/templates', 'faq');
		return $loader;
	}

	/**
	 * return @array Configuration for the Twig environment.
	 */
	private function twigConfig()
	{
		$config = array(
			'cache' => BACKEND_CACHE_PATH . '/cached_templates/twig',
			'debug' => SPOON_DEBUG, // TODO remove Spoon dependency.
		);
		return $config;
	}

	/**
	 * Setup filters for the Twig environment.
	 */
	private function twigFilters()
	{
		$this->twig->addFilter('addslashes', new Twig_Filter_Function('addslashes'));
		$this->twig->addFilter('ucfirst', new Twig_Filter_Function('SpoonFilter::ucfirst'));
		$this->twig->addFilter('geturl', new Twig_Filter_Function('BackendTemplateModifiers::getURL'));
		$this->twig->addFilter('getnavigation', new Twig_Filter_Function('BackendTemplateModifiers::getNavigation'));
		$this->twig->addFilter('getmainnavigation', new Twig_Filter_Function('BackendTemplateModifiers::getMainNavigation'));
		$this->twig->addFilter('rand', new Twig_Filter_Function('BackendTemplateModifiers::random'));
		$this->twig->addFilter('formatfloat', new Twig_Filter_Function('BackendTemplateModifiers::formatFloat'));
		$this->twig->addFilter('truncate', new Twig_Filter_Function('BackendTemplateModifiers::truncate'));
		$this->twig->addFilter('camelcase', new Twig_Filter_Function('SpoonFilter::toCamelCase'));
		$this->twig->addFilter('stripnewlines', new Twig_Filter_Function('BackendTemplateModifiers::stripNewlines'));
		$this->twig->addFilter('dump', new Twig_Filter_Function('BackendTemplateModifiers::dump'));
		$this->twig->addFilter('formatdate', new Twig_Filter_Function('BackendTemplateModifiers::formatDate'));
		$this->twig->addFilter('formattime', new Twig_Filter_Function('BackendTemplateModifiers::formatTime'));
		$this->twig->addFilter('formatdatetime', new Twig_Filter_Function('BackendTemplateModifiers::formatDateTime'));
		$this->twig->addFilter('formatnumber', new Twig_Filter_Function('BackendTemplateModifiers::formatNumber'));
		$this->twig->addFilter('tolabel', new Twig_Filter_Function('BackendTemplateModifiers::toLabel'));
	}

	private function twigGlobals()
	{
		$this->twig->addGlobal('CRLF', "\n");
		$this->twig->addGlobal('TAB', "\t");
		$this->twig->addGlobal('now', time());
		$this->twig->addGlobal('LANGUAGE', BL::getWorkingLanguage());
		$this->twig->addGlobal('SITE_MULTILANGUAGE', SITE_MULTILANGUAGE);
		$this->twig->addGlobal(
			'SITE_TITLE',
			BackendModel::getModuleSetting(
				'core',
				'site_title_' . BL::getWorkingLanguage(), SITE_DEFAULT_TITLE
			)
		);
		// TODO goes up, here we assume the current user is authenticated already.
		$this->twig->addGlobal('user', BackendAuthentication::getUser());

		$languages = BackendLanguage::getWorkingLanguages();
		$workingLanguages = array();
		foreach($languages as $abbreviation => $label) $workingLanguages[] = array('abbr' => $abbreviation, 'label' => $label, 'selected' => ($abbreviation == BackendLanguage::getWorkingLanguage()));
		$this->twig->addGlobal('workingLanguages', $workingLanguages);
	}

	/**
	 * @internal Called by BackendFaqCategories#parse()
	 *
	 * @return string Rendered output of this action via the Twig environment.
	 */
	private function twigRender()
	{
		// TODO design
		//	- render template?
		//	- set (add to) context via method in the parent class?
		//	- return a response?
		$tplVars = array(
			'dataGrid' => ($this->dataGrid->getNumResults() ? $this->dataGrid->getContent() : false),
			'showFaqAddCategory' => BackendAuthentication::isAllowedAction('add_category')
				&& $this->multipleCategoriesAllowed
		);
		return $this->twig->loadTemplate('@faq/categories.html.twig')->render($tplVars);
	}

	//}}}



}
