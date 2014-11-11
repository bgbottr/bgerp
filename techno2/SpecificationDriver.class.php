<?php

/**
 * Драйвър за нестандартен артикул
 *
 *
 * @category  bgerp
 * @package   techno
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Драйвър за нестандартен артикул
 */
abstract class techno2_SpecificationDriver extends core_BaseClass
{
	
	
	/**
	 * За конвертиране на съществуващи MySQL таблици от предишни версии
	 */
	public $oldClassName = 'techno_SpecificationDriver';
	
	
	/**
	 * Инстанция на класа имплементиращ интерфейса
	 */
	public $class;
	
	
	
	/**
	 * Кой може да избира драйвъра
	 */
	public $canSelectSource = 'ceo, techno';
	
	
	/**
	 * Интерфейси които имплементира
	 */
	public $interfaces = 'techno2_SpecificationDriverIntf';
	

	/**
	 * Вътрешната форма
	 *
	 * @param mixed $innerForm
	 */
	protected $innerForm;
	
	
	/**
	 * Вътрешното състояние
	 *
	 * @param mixed $innerState
	 */
	protected $innerState;
	
	
	/**
	 * Задава вътрешната форма
	 *
	 * @param mixed $innerForm
	 */
	public function setInnerForm($innerForm)
	{
		$this->innerForm = $innerForm;
	}
	
	
	/**
	 * Задава вътрешното състояние
	 *
	 * @param mixed $innerState
	 */
	public function setInnerState($innerState)
	{
		$this->innerState = $innerState;
	}
	
	
	/**
	 * Подготвя формата за въвеждане на данни за вътрешния обект
	 *
	 * @param core_Form $form
	 */
	public function prepareEmbeddedForm(core_Form &$form)
	{
	
	}
	
	
	/**
	 * Проверява въведените данни
	 *
	 * @param core_Form $form
	 */
	public function checkEmbeddedForm(core_Form &$form)
	{
	
	}
	
	
	/**
	 * Подготвя вътрешното състояние, на база въведените данни
	 *
	 * @param core_Form $innerForm
	 */
	public function prepareInnerState()
	{
	
	}


	/**
	 * Можели вградения обект да се избере
	 */
	public function canSelectInnerObject($userId = NULL)
	{
		return core_Users::haveRole($this->canSelectSource, $userId);
	}


	/**
	 * Преди запис
	 */
	public static function on_BeforeSave($mvc, &$is, $filter, $rec)
	{
		if(isset($filter)){
			$is = clone $filter;
		}
	}


	/**
	 * Връща масив с мета данните които ще се форсират на продукта
	 */
	public function getDefaultMetas()
	{
		return array();
	}


	/**
	 * Рендиране на параметрите
	 *
	 * @param данни за параметрите $paramData
	 * @param core_ET $tpl - шаблон
	 */
	public function renderParams($paramData, &$tpl, $short = FALSE)
	{
		$blockName = ($short) ? "SHORT" : "LONG";
		$paramTpl = getTplFromFile('techno2/tpl/Parameters.shtml')->getBlock($blockName);
	
		if(count($paramData->params)){
			foreach ($paramData->params as $row){
				$block = clone $paramTpl->getBlock('PARAMS');
				$block->placeObject($row);
				$block->removeBlocks();
				$block->append2master();
			}
		} else{
			$paramTpl = new ET("[#ADD#]");
		}
			
		if($paramData->addParamUrl){
			if(cat_Params::count()){
				$btn = ht::createBtn('Нов параметър', $paramData->addParamUrl, NULL, NULL, 'ef_icon = img/16/star_2.png,title=Добавяне на нов параметър');
			} else {
				$btn = ht::createErrBtn('Нов параметър', 'Няма продуктови параметри');
			}
			$paramTpl->replace($btn, 'ADD');
		}
	
		$tpl->replace($paramTpl, 'PARAMS');
	}
}