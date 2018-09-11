<?php


/**
 * Модел за "Детайл на зоните"
 *
 *
 * @category  bgerp
 * @package   rack
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_ZoneDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Детайл на зоните';
    
    
    /**
     * Кой може да листва?
     */
    public $canList = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canAdd = 'no_one';
    
    
    /**
     * Кой може да добавя?
     */
    public $canWrite = 'no_one';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'zoneId';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     *
     *  @var string
     */
    public $hideListFieldsIfEmpty = 'movementsHtml';
    
    
    /**
     * Полета в листовия изглед
     */
    public $listFields = 'productId, packagingId, status=Състояние,movementsHtml=@';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('zoneId', 'key(mvc=rack_Zones)', 'caption=Зона, input=hidden,silent,mandatory');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,tdClass=productCell leftCol wrap');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,input=hidden,mandatory,removeAndRefreshForm=quantity|quantityInPack|displayPrice,tdClass=nowrap');
        $this->FLD('documentQuantity', 'double(smartRound)', 'caption=Очаквано,mandatory');
        $this->FLD('movementQuantity', 'double(smartRound)', 'caption=Нагласено,mandatory');
        $this->FNC('status', 'varchar', 'tdClass=zone-product-status');
        
        $this->setDbUnique('zoneId,productId,packagingId');
    }
    
    
    /**
     * Изпълнява се преди преобразуването към вербални стойности на полетата на записа
     */
    protected static function on_BeforeRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (is_object($rec)) {
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            $rec->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
            $rec->movementQuantity = $rec->movementQuantity / $rec->quantityInPack;
            $rec->documentQuantity = $rec->documentQuantity / $rec->quantityInPack;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        $row->status = tr('|*' . $mvc->getFieldType('movementQuantity')->toVerbal($rec->movementQuantity) . " |от|* " . $mvc->getFieldType('documentQuantity')->toVerbal($rec->documentQuantity));
    }
    
    
    /**
     * След рендиране на детайлите се скриват ценовите данни от резултатите
     * ако потребителя няма права
     */
    protected static function on_AfterPrepareDetail($mvc, $res, &$data)
    {
        if(!count($data->rows)) return;
        setIfNot($data->masterData->rec->_isSingle, true);
        
        // Допълнително обикаляне на записите
        foreach ($data->rows as $id => &$row){
            $rec = $data->recs[$id];
            $movementsHtml = self::getInlineMovements($rec, $data->masterData->rec);
            if(!empty($movementsHtml)){
                $row->movementsHtml = $movementsHtml;
            }
            
            $row->ROW_ATTR['class'] = ($data->masterData->rec->_isSingle === false) ? 'row-added' : 'row-added zonesCommonRow';
        }
    }
    
    
    /**
     * Записва движение в зоната
     *
     * @param int   $zoneId      - ид на зона
     * @param int   $productId   - ид на артикул
     * @param int   $packagingId - ид на опаковка
     * @param float $quantity    - количество в основна мярка
     *
     * @return void
     */
    public static function recordMovement($zoneId, $productId, $packagingId, $quantity)
    {
        $newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$productId} AND #packagingId = {$packagingId}");
        if (empty($newRec)) {
            $newRec = (object) array('zoneId' => $zoneId, 'productId' => $productId, 'packagingId' => $packagingId, 'movementQuantity' => 0, 'documentQuantity' => null);
        }
        $newRec->movementQuantity += $quantity;
        
        self::save($newRec);
    }
    
    
    /**
     * Синхронизиране на зоните с документа
     *
     * @param int $zoneId
     * @param int $containerId
     */
    public static function syncWithDoc($zoneId, $containerId = null)
    {
        if (isset($containerId)) {
            $document = doc_Containers::getDocument($containerId);
            $products = $document->getProductsSummary();
            $exRecs = array();
            
            if (is_array($products)) {
                foreach ($products as $obj) {
                    $newRec = self::fetch("#zoneId = {$zoneId} AND #productId = {$obj->productId} AND #packagingId = {$obj->packagingId}");
                    if (empty($newRec)) {
                        $newRec = (object) array('zoneId' => $zoneId, 'productId' => $obj->productId, 'packagingId' => $obj->packagingId, 'movementQuantity' => null, 'documentQuantity' => 0);
                    }
                    $newRec->documentQuantity = $obj->quantity;
                    
                    self::save($newRec);
                    $exRecs[$newRec->id] = $newRec->id;
                }
            }
            
            // Тези които не са се обновили се изтриват
            if (count($exRecs)) {
                self::nullifyQuantityFromDocument($zoneId, $exRecs);
            }
        } else {
            self::nullifyQuantityFromDocument($zoneId);
        }
    }
    
    
    /**
     * Зануляване на очакваното количество по документи
     *
     * @param int   $zoneId
     * @param array $notIn
     */
    private static function nullifyQuantityFromDocument(int $zoneId, array $notIn = array())
    {
        $query = self::getQuery();
        $query->where("#zoneId = {$zoneId}");
        $query->where('#documentQuantity IS NOT NULL');
        if (count($notIn)) {
            $query->notIn('id', $notIn);
        }
        
        while ($rec = $query->fetch()) {
            $rec->documentQuantity = null;
            self::save($rec);
        }
    }
    
    
    /**
     * Изчислява какво количество от даден продукт е налично в зоните
     */
    public static function calcProductQuantityOnZones($productId)
    {
        $query = self::getQuery();
        $query->XPR('sum', 'double', 'sum(#movementQuantity)');
        $query->where("#productId = {$productId}");
        $rec = $query->fetch();
        $res = 0;
        if ($rec) {
            $res = $rec->sum;
        }
        
        return $res;
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Ако няма никакви количества се изтрива
        if (empty($rec->documentQuantity) && empty($rec->movementQuantity)) {
            self::delete($rec->id);
        }
        
        // Обновяване на информацията за количествата от продукта в зоните
        $storeId = store_Stores::getCurrent();
        $storeProductRec = rack_Products::fetch("#productId = {$rec->productId} AND #storeId = {$storeId}");
        if(is_object($storeProductRec)){
            $productQuantityOnZones = self::calcProductQuantityOnZones($rec->productId);
            $storeProductRec->quantityOnZones = $productQuantityOnZones;
            rack_Products::save($storeProductRec, 'quantityOnZones');
        }
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->query->orderBy('documentQuantity', 'DESC');
    }
    
    
    /**
     * Рендиране на детайла накуп
     * 
     * @param stdClass $masterRec
     * @param core_Mvc $masterMvc
     * @return core_ET
     */
    public static function renderInlineDetail($masterRec, $masterMvc)
    {
        $tpl = new core_ET();
        
        $me = cls::get(get_called_class());
        $dData = (object)array('masterId' => $masterRec->id, 'masterMvc' => $masterMvc, 'masterData' => $masterRec, 'listTableHideHeaders' => true, 'inlineDetail' => $masterRec->_isSingle);
        $dData = $me->prepareDetail($dData);
        if(!count($dData->recs)) return $tpl;
        
        $tpl = $me->renderDetail($dData);
        $tpl->removePlaces();
        $tpl->removeBlocks();
        
        return $tpl;
    }
    
    
    /**
     * Рендира таблицата със движения към зоната
     *
     * @param stdClass $rec
     * @return core_ET $tpl
     */
    private function getInlineMovements($rec, $masterRec)
    {
        $Movements = clone cls::get('rack_Movements');
        $data = (object) array('recs' => array(), 'rows' => array(), 'listTableMvc' => $Movements);
        $data->listFields = arr::make('movement=Движение,workerId=Работник', true);
        $Movements->setField('workerId', "tdClass=inline-workerId");
        $skipClosed = ($masterRec->_isSingle === true) ? false : true;
        $movementArr = rack_Zones::getCurrentMovementRecs($rec->zoneId, $skipClosed);
        list($productId, $packagingId) = array($rec->productId, $rec->packagingId);
        $data->recs = array_filter($movementArr, function($o) use($productId, $packagingId){return $o->productId == $productId && $o->packagingId == $packagingId;});
        
        foreach ($data->recs as $mRec) {
            $fields = $Movements->selectFields();
            $fields['-list'] = true;
            $fields['-inline'] = true;
            $data->rows[$mRec->id] = rack_Movements::recToVerbal($mRec, $fields);
        }
       
        // Рендиране на таблицата
        $tpl = new core_ET('');
        if (count($data->rows)) {
            $tableClass = ($masterRec->_isSingle === true) ? 'listTable' : 'simpleTable';
            $table = cls::get('core_TableView', array('mvc' => $data->listTableMvc, 'tableClass' => $tableClass, 'thHide' => true));
            $Movements->invoke('BeforeRenderListTable', array($tpl, &$data));
            
            $tpl->append($table->get($data->rows, $data->listFields));
            $tpl->append("style='width:100%;'", 'TABLE_ATTR');
        }
        
        $tpl->removePendings('COMMON_ROW_ATTR');
        
        return $tpl;
    }
}
