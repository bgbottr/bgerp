<?php
/**
 * 
 * Ценоразписи за продукти от каталога
 * 
 * @category   BGERP
 * @package    catpr
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @title      Ценоразписи
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 *
 */
class catpr_Pricelists extends core_Master
{
	var $title = 'Ценоразписи';
	
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_Created, plg_Rejected, plg_RowTools,
                     catpr_Wrapper, plg_AlignDecimals';
    
    var $details = 'catpr_Pricelists_Details';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, date, discountId, currencyId, vat';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,user';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,catpr';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,catpr,broker';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,catpr,broker';
    
    var $canList = 'admin,catpr,broker';
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,catpr';
	
    
    function description()
	{
		$this->FLD('date', 'date', 'mandatory,input,caption=Към Дата');
		$this->FLD('discountId', 'key(mvc=catpr_Discounts,select=name,allowEmpty)', 'input,caption=По Отстъпка');
		$this->FLD('currencyId', 'key(mvc=currency_Currencies,select=name,allowEmpty)', 'input,caption=Валута');
		$this->FLD('vat', 'percent', 'input,caption=ДДС');
		$this->FLD('priceGroups', 'keylist(mvc=catpr_Pricegroups, select=name)', 'input,caption=Ценови групи');
	}
	
	
	function on_AfterSave($mvc, &$id, $rec)
	{
		// Изтриване на (евентуални) стари изчисления
		catpr_Pricelists_Details::delete("#pricelistId = {$rec->id}");
		
		$priceGroups = type_Keylist::toArray($rec->priceGroups);
		if (empty($priceGroups)) {
			// Не е заявена нито една ценова група.
			return;
		}
		
		$costsQuery = catpr_Costs::getQuery();
		
		// Ограничаваме се само до продукти със зададена себестойност от заявените ценови групи.
		$costsQuery->where('#priceGroupId IN ('.implode(',', $priceGroups).')');
		$costsQuery->groupBy('productId');
//		$costsQuery->show('productId'); // <- това не работи за сега, трябва поправка в core_Query

		$ProductIntf = cls::getInterface('cat_ProductAccRegIntf', 'cat_Products');
		
		while ($cRec = $costsQuery->fetch()) {
			
			$costRec = catpr_Costs::getProductCosts($cRec->productId, $rec->date);
			
			if (count($costRec) == 0) {
				// Продукта няма себестойност към зададената дата - не влиза в ценоразписа.
				continue;
			}
			
			$costRec = reset($costRec);
			
			if (!in_array($costRec->priceGroupId, $priceGroups)) {
				// Продукта е бил (или ще бъде, някога) в една от заявените ценови групи, но 
				// към избраната дата не е в нито една от тях.
				continue;
			}
			
			$price = $ProductIntf->getProductPrice($costRec->productId, $rec->date, $rec->discountId);
			
			if (!isset($price)) {
				// Ако цената на продукта не е дефинирана (най-вероятно няма себестойност), той
				// не влиза в ценоразпис.
				continue;
			}
			
			// Завишаване на цената с зададения процент ДДС
			$price = $price * (1 + $rec->vat);
			
			/*
			 * @TODO Конвертиране на $price към валутата $rec->currencyId
			 */ 
			
			catpr_Pricelists_Details::save(
				(object)array(
					'pricelistId'  => $rec->id,
					'priceGroupId' => $costRec->priceGroupId,
					'productId'    => $costRec->productId,
					'price'        => $price,
					'state'        => 'draft',
				)
			);
		}
	}
}