<?php



/**
 * Плъгин даващ възможност да се печатат етикети от обект
 * 
 * @category  bgerp
 * @package   label
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class label_plg_Print extends core_Plugin
{
	
	
	/**
	 * След дефиниране на полетата на модела
	 *
	 * @param core_Mvc $mvc
	 */
	public static function on_AfterDescription(core_Mvc $mvc)
	{
		setIfNot($mvc->canPrintlabel, 'label, admin, ceo');
	}
	
	
	/**
	 * След преобразуване на записа в четим за хора вид.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $row Това ще се покаже
	 * @param stdClass $rec Това е записа в машинно представяне
	 */
	public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
	{
		if(isset($fields['-list']) && $mvc->hasPlugin('plg_RowTools2')){
			$btnParams = self::getLabelBtnParams($mvc, $rec);
			if(!empty($btnParams['url'])){
				$row->_rowTools->addLink('Етикетиране', $btnParams['url'], $btnParams['attr']);
			}
		}
	}
	
	
	/**
	 * Параметрите на бутона за етикетиране
	 * 
	 * @param core_mvc $mvc
	 * @param stdClass $rec
	 * @return array $res -
	 * 			['url'] - урл, ако има права
	 * 			['attr] - атрибути
	 */
	private static function getLabelBtnParams($mvc, $rec)
	{
		$res = array('url' => NULL, 'attr' => '');
	
		if($mvc->haveRightFor('printlabel', $rec)){
			$templates = label_Templates::getTemplatesByDocument($mvc, $rec->id, TRUE);
			$error = (!count($templates)) ? ",error=Няма наличен шаблон за етикети от \"{$mvc->title}\"" : '';
			
			if(label_Prints::haveRightFor('add', (object)array('classId' => $mvc->getClassId(), 'objectId' => $rec))) {
				core_Request::setProtected(array('classId, objectId'));
				$res['url'] = array('label_Prints', 'add', 'classId' => $mvc->getClassId(), 'objectId' => $rec->id, 'ret_url' => TRUE);
				$res['url'] = toUrl($res['url']);
				core_Request::removeProtected('classId,objectId');
				
				$res['attr'] = "target=_blank,ef_icon = img/16/price_tag_label.png,title=Разпечатване на етикети от|* |{$mvc->title}|* №{$rec->id}{$error}";
			}
		}
		
		return $res;
	}
	
	
	/**
	 * След подготовка на тулбара на единичен изглед.
	 *
	 * @param core_Mvc $mvc
	 * @param stdClass $data
	 */
	public static function on_AfterPrepareSingleToolbar($mvc, &$data)
	{
		$btnParams = self::getLabelBtnParams($mvc, $data->rec);
		if(!empty($btnParams['url'])){
			$data->toolbar->addBtn('Етикетиране', $btnParams['url'], NULL, $btnParams['attr']);
		}
	}
	
	
	/**
	 * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
	 *
	 * @param core_Mvc $mvc
	 * @param string $requiredRoles
	 * @param string $action
	 * @param stdClass $rec
	 * @param int $userId
	 */
	public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
	{
		if($action == 'printlabel' && isset($rec)){
			if(in_array($rec->state, array('rejected', 'draft', 'template'))){
				$requiredRoles = 'no_one';
			}
		}
	}
}
