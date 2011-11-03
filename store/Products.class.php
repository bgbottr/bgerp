<?php
/**
 * Продукти
 */
class store_Products extends core_Manager
{
    /**
     * Поддържани интерфейси
     */
    var $interfaces = 'store_AccRegIntf,acc_RegisterIntf';
    
    /**
     *  @todo Чака за документация...
     */
    var $title = 'Продукти';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $loadList = 'plg_RowTools, plg_Created, store_Wrapper, plg_Search';
    
    
    /**
     * Права
     */
    var $canRead = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canEdit = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canAdd = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canView = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $canDelete = 'admin,store';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $listFields = 'id, tools=Пулт, name, storeId, quantity, quantityNotOnPallets, quantityOnPallets, makePallets';
    
    
    /**
     *  @todo Чака за документация...
     */
    var $rowToolsField = 'tools';
    
    
    function description()
    {
        $this->FLD('name',                 'key(mvc=cat_Products, select=name)',    'caption=Име,remember=info');
        $this->FLD('storeId',              'varchar(mvc=store_Stores,select=name)', 'caption=Склад');
        $this->FLD('quantity',             'int',                                   'caption=Количество->Общо');
        $this->FNC('quantityNotOnPallets', 'int',                                   'caption=Количество->Непалетирано');
        $this->FLD('quantityOnPallets',    'int',                                   'caption=Количество->На палети');
        $this->FNC('makePallets',          'varchar(255)',                          'caption=Палетирай');
    }
    
    
    /**
     * Извличане записите само от избрания склад
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $selectedStoreId = store_Stores::getCurrent();
        $data->query->where("#storeId = {$selectedStoreId}");
    }

    
    /**
     * При добавяне/редакция на палетите - данни по подразбиране 
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        // storeId
        $selectedStoreId = store_Stores::getCurrent();
        $data->form->setReadOnly('storeId', $selectedStoreId);

        $data->form->showFields = 'storeId,name,quantity';
    }
    
    
    /**
     * Изпълнява се след конвертирането на $rec във вербални стойности
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, $row, $rec)
    {
    	if (haveRole('admin,store')) {
    	    $row->makePallets = Ht::createBtn('Палетирай', array('store_Pallets', 'add', 'productId' => $rec->id));	
    	}
    	
        $row->quantityNotOnPallets = $rec->quantity - $rec->quantityOnPallets;
    }

    
    /**
     * Филтър 
     * 
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->title = 'Търсене';
        $data->listFilter->view  = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter,class=btn-filter');
         

        
        $data->listFilter->showFields = 'search';
        
        // Активиране на филтъра
        $recFilter = $data->listFilter->input();

        // Ако филтъра е активиран
        if ($data->listFilter->isSubmitted()) {
            if ($recFilter->productIdFilter) {
                $condProductId = "#id = '{$recFilter->productIdFilter}'";                  
            }            
            
            // query
            if ($condProductId) $data->query->where($condProductId);
        }        
    }    
	
    
    /*******************************************************************************************
     * 
     * ИМПЛЕМЕНТАЦИЯ на интерфейса @see crm_ContragentAccRegIntf
     * 
     ******************************************************************************************/
    
    /**
     * @see crm_ContragentAccRegIntf::getItemRec
     * @param int $objectId
     */
    static function getItemRec($objectId)
    {
        $self = cls::get(__CLASS__);
        $result = null;
        
        if ($rec = $self->fetch($objectId)) {
            $result = (object)array(
                'num' => $rec->id,
                'title' => $rec->name,
                'features' => 'foobar' // @todo!
            );
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::getLinkToObj
     * @param int $objectId
     */
    static function getLinkToObj($objectId)
    {
        $self = cls::get(__CLASS__);
        
        if ($rec  = $self->fetch($objectId)) {
            $result = ht::createLink($rec->name, array($self, 'Single', $objectId)); 
        } else {
            $result = '<i>неизвестно</i>';
        }
        
        return $result;
    }
    
    /**
     * @see crm_ContragentAccRegIntf::itemInUse
     * @param int $objectId
     */
    static function itemInUse($objectId)
    {
        // @todo!
    }
    
    /**
     * КРАЙ НА интерфейса @see acc_RegisterIntf
     */

}