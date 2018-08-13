<?php


/**
 * Движения в палетния склад
 *
 *
 * @category  bgerp
 * @package   pallet
 *
 * @author    Ts. Mihaylov <tsvetanm@ep-bags.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class rack_Movements extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Движения';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Движение';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_Created, rack_Wrapper, plg_SaveAndNew, plg_State, plg_Sorting,plg_Search,plg_AlignDecimals2';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,rack';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,rack,storeWorker';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo';
    
    
    /**
     * Кой може да приключи движение
     */
    public $canDone = 'ceo,admin,rack,storeWorker';
    
    
    /**
     * Кой може да заяви движение
     */
    public $canToggle = 'ceo,admin,rack,storeWorker';
    
    
    /**
     * Полета за листовия изглед
     */
    public $listFields = 'productId,packagingId,zones=Нагласяне,packQuantity,palletId=От,palletToId=Към,workerId=Изпълнител,note=Бележка,createdOn,createdBy';
    
    
    /**
     * Полета по които да се търси
     */
    public $searchFields = 'palletId,position,positionTo,workerId,note';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = note;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('storeId', 'key(mvc=store_Stores, select=name)', 'caption=Склад,column=none');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=rack_Products::getSellableProducts)', 'tdClass=productCell,caption=Артикул,silent,removeAndRefreshForm=packagingId|quantity|quantityInPack|zones|palletId,mandatory,remember');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=shortName)', 'caption=Мярка,input=hidden,mandatory,smartCenter,removeAndRefreshForm=quantity|quantityInPack,silent');
        $this->FNC('packQuantity', 'double(Min=0)', 'caption=Количество,smartCenter,silent');
        $this->FNC('movementType', 'varchar', 'silent,input=hidden');
        
        // Палет, позиции и зони
        $this->FLD('palletId', 'key(mvc=rack_Pallets, select=label)', 'caption=Движение->Палет,input=hidden,silent,placeholder=Под||Floor,removeAndRefreshForm=position|positionTo,silent');
        $this->FLD('position', 'rack_PositionType', 'caption=Движение->Позиция,input=none');
        $this->FLD('positionTo', 'rack_PositionType', 'caption=Движение->Нова,input=none');
        $this->FLD('palletToId', 'key(mvc=rack_Pallets, select=label)', 'caption=Движение->Палет към,input=none');
        $this->FLD('zones', 'table(columns=zone|quantity,captions=Зона|Количество,widths=10em|10em,validate=rack_Movements::validateZonesTable)', 'caption=Движение->Зони,smartCenter,input=none');
        
        $this->FLD('quantity', 'double', 'caption=Количество,input=none');
        $this->FLD('quantityInPack', 'double', 'input=none');
        
        $this->FLD('state', 'enum(pending=Чакащо, active=Активно, closed=Приключено)', 'caption=Състояние,smartCenter');
        $this->FLD('workerId', 'user', 'caption=Движение->Товарач,smartCenter,input=none');
        
        $this->FLD('note', 'varchar(64)', 'caption=Движение->Забележка,column=none,smartCenter');
        $this->FLD('zoneList', 'keylist(mvc=rack_Zones, select=num)', 'caption=Зони,input=none');
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = $form->rec;
        
        if ($form->isSubmitted()) {
            
            // Проверка новата позиция допустима ли е
            if ($rec->positionTo && ($rec->poisition != $rec->positionTo)) {
                if (!rack_Racks::isPlaceUsable($rec->positionTo, $rec->productId, $rec->storeId, $error, $status)) {
                    if ($status == 'reserved') {
                        $form->setWarning('positionTo', $error);
                    } else {
                        $form->setError('positionTo', $error);
                    }
                }
                
                if (!rack_Pallets::isEmpty($rec->productId, $rec->positionTo, $rec->storeId, $error)) {
                    $form->setError('positionTo', $error);
                }
            }
            
            $mvc->getZoneArr($rec, $quantityInZones);
            
            if (empty($quantityInZones)) {
                if (empty($rec->packQuantity) && empty($rec->positionTo)) {
                    $form->setError('packQuantity,zones,positionTo', 'Не може да се направи празно движение');
                }
                
                if (!empty($rec->packQuantity) && empty($rec->palletId) && empty($rec->positionTo)) {
                    $form->setError('packQuantity,zones,positionTo', 'Не може количеството да остане на същата позиция');
                }
                
                if ($rec->position == $rec->positionTo) {
                    $form->setError('packQuantity,zones,positionTo', 'Не може да се направи празно движение');
                }
            }
            
            if (!$form->gotErrors()) {
                $availableQuantity = rack_Pallets::getAvailableQuantity($rec->palletId, $rec->productId, $rec->storeId);
                
                if ($availableQuantity < 0) {
                    $form->setError('packQuantity,zones', 'От този продукт няма достатъчна наличност');
                }
                
                $restQuantity = $availableQuantity - $quantityInZones;
                
                if ($restQuantity < 0) {
                    $form->setError('packQuantity,zones', 'Количеството по зони е по-голямо от очакваното');
                }
                
                if (!$form->gotErrors()) {
                    $defQuantity = rack_Pallets::getDefaultQuantity($rec->productId, $rec->storeId);
                    if (empty($rec->packQuantity)) {
                        if (!empty($defQuantity)) {
                            $rec->packQuantity = $defQuantity / $rec->quantityInPack;
                        } else {
                            $form->setError('packQuantity', 'Въведете количество');
                        }
                    }
                    
                    if (!$form->gotErrors()) {
                        $rec->quantity = $rec->quantityInPack * $rec->packQuantity;
                        if ($rec->state == 'closed') {
                            $rec->_isCreatedClosed = true;
                        }
                        
                        if (!empty($rec->position) && empty(Request::get('positionTo'))) {
                            $rec->positionTo = $rec->position;
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc     $mvc    Мениджър, в който възниква събитието
     * @param int          $id     Първичния ключ на направения запис
     * @param stdClass     $rec    Всички полета, които току-що са били записани
     * @param string|array $fields Имена на полетата, които sa записани
     * @param string       $mode   Режим на записа: replace, ignore
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec, &$fields = null, $mode = null)
    {
        $zoneListArr = array();
        if (isset($rec->zones)) {
            $zoneArr = type_Table::toArray($rec->zones);
            if (count($zoneArr)) {
                foreach ($zoneArr as $obj) {
                    $zoneListArr[$obj->zone] = $obj->zone;
                }
            }
        }
        
        $saveAgain = false;
        $updateFields = array();
        
        if ($rec->state == 'active' || $rec->_canceled === true || $rec->_isCreatedClosed === true) {
            if (empty($rec->workerId)) {
                $saveAgain;
                $rec->workerId = core_Users::getCurrent('id', false);
                $updateFields['workerId'] = 'workerId';
            }
        }
        
        if (empty($rec->zoneList)) {
            $saveAgain = true;
            $updateFields['zoneList'] = 'zoneList';
            $rec->zoneList = (count($zoneListArr)) ? keylist::fromArray($zoneListArr) : null;
        }
        
        if ($saveAgain === true && count($updateFields)) {
            $mvc->save_($rec, $updateFields);
        }
        
        if ($rec->state == 'active' || $rec->_canceled === true || $rec->_isCreatedClosed === true) {
            $rollback = ($rec->_canceled === true) ? true : false;
            $mvc->makeTransaction($rec, $rollback);
        }
    }
    
    
    /**
     * Изпълнява посоченото движение
     */
    private function makeTransaction($rec, $rollback = false)
    {
        $zoneArr = $this->getZoneArr($rec, $quantityInZones);
        
        //$restQuantity = $restQuantity - $quantityInZones;
        
        
        foreach ($zoneArr as $obj) {
            $sign = ($rollback === true) ? -1 : 1;
            $quantity = $obj->quantity * $rec->quantityInPack;
            rack_ZoneDetails::recordMovement($obj->zone, $rec->productId, $rec->packagingId, $sign * $quantity);
        }
        
        $exPalletId = null;
        if (!empty($rec->palletId)) {
            $direction = ($rollback === false) ? true : false;
            $palletRec = rack_Pallets::fetch($rec->palletId);
            $q = !empty($rec->quantity) ? $rec->quantity : $quantityInZones;
            rack_Pallets::increment($palletRec->productId, $palletRec->storeId, $palletRec->position, $q, $direction);
            $palletRec = rack_Pallets::fetch($rec->palletId);
            if ($palletRec->state == 'closed') {
                $exPalletId = $palletRec->id;
            }
        }
        
        if (!empty($rec->positionTo) && ($rec->position != $rec->positionTo)) {
            $quantityTo = $rec->quantity - $quantityInZones;
            expect($palletId = rack_Pallets::increment($rec->productId, $rec->storeId, $rec->positionTo, $quantityTo, $rollback, $exPalletId));
            if (empty($rec->palletToId)) {
                $rec->palletToId = $palletId;
                $this->save_($rec, 'palletToId');
            }
        }
        
        core_Cache::remove('UsedRacksPossitions', $rec->storeId);
        
        $rMvc = cls::get('rack_Racks');
        
        if ($rec->positionTo) {
            $rMvc->updateRacks[$rec->storeId . '-' . $rec->positionTo] = true;
        }
        
        if ($rec->position) {
            $rMvc->updateRacks[$rec->storeId . '-' . $rec->position] = true;
        }
    }
    
    
    private function getZoneArr($rec, &$quantityInZones)
    {
        $quantityInZones = 0;
        $zoneArr = array();
        if (isset($rec->zones)) {
            $zoneArr = type_Table::toArray($rec->zones);
            if (count($zoneArr)) {
                foreach ($zoneArr as $obj) {
                    $quantityInZones += $obj->quantity;
                }
            }
        }
        
        return $zoneArr;
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     *
     * @param core_Mvc $mvc
     * @param stdClass $rec
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = $data->form;
        $rec = &$form->rec;
        
        $form->setDefault('storeId', store_Stores::getCurrent());
        $form->setField('storeId', 'input=hidden');
        
        if (isset($rec->productId)) {
            $form->setField('packagingId', 'input');
            
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            $form->setDefault('packagingId', key($packs));
            
            $form->setField('palletId', 'input');
            $form->setField('positionTo', 'input');
            $form->setField('packQuantity', 'input');
            
            $zones = rack_Zones::getFreeZones($rec->storeId);
            if (count($zones)) {
                $form->setFieldTypeParams('zones', array('zone_opt' => array('' => '') + $zones));
                $form->setField('zones', 'input');
            } else {
                $form->setField('zones', 'input=none');
            }
            
            // Възможния избор на палети от склада
            $pallets = rack_Pallets::getPalletOptions($rec->productId, $rec->storeId);
            $form->setOptions('palletId', array('' => tr('Под||Floor')) + $pallets);
            
            $packRec = cat_products_Packagings::getPack($rec->productId, $rec->packagingId);
            $rec->quantityInPack = is_object($packRec) ? $packRec->quantity : 1;
            
            // Показване на допустимото количество
            $availableQuantity = rack_Pallets::getAvailableQuantity($rec->palletId, $rec->productId, $rec->storeId);
            if ($defQuantity = rack_Pallets::getDefaultQuantity($rec->productId, $rec->storeId)) {
                $availableQuantity = min($availableQuantity, $defQuantity);
            }
            
            if ($availableQuantity >= 0) {
                $availableQuantity /= $rec->quantityInPack;
                $availableQuantityVerbal = core_Type::getByName('double(smartRound)')->toVerbal($availableQuantity);
                $availableQuantityVerbal = str_replace('&nbsp;', '', $availableQuantityVerbal);
                $form->setField('packQuantity', "placeholder={$availableQuantityVerbal}");
            }
            
            if (isset($rec->palletId)) {
                
                // На коя позиция е палета?
                $form->setField('position', 'input=hidden');
                if ($positionId = rack_Pallets::fetchField($rec->palletId, 'position')) {
                    $form->setDefault('position', $positionId);
                    $form->setField('positionTo', 'placeholder=Няма');
                }
            } else {
                $form->setField('positionTo', 'placeholder=Под||Floor');
            }
            
            // Добавяне на предложения за нова позиция
            if ($bestPos = rack_Pallets::getBestPos($rec->productId, $rec->storeId)) {
                $form->setSuggestions('positionTo', array(tr('Под||Floor') => tr('Под||Floor'), $bestPos => $bestPos));
            }
        }
        
        if ($movementType = Request::get('movementType')) {
            switch ($movementType) {
                case 'floor2rack':
                    $form->setField('zones', 'input=none');
                    $form->setField('palletId', 'input=none');
                    if (isset($bestPos)) {
                        $form->setDefault('positionTo', $bestPos);
                    }
                    break;
                case 'rack2floor':
                    $form->setField('zones', 'input=none');
                    $form->setReadOnly('palletId');
                    $form->setField('positionTo', 'input=hidden');
                    $form->setField('palletId', 'caption=Сваляне на пода->Палет');
                    $form->setField('note', 'caption=Сваляне на пода->Забележка');
                    $form->setDefault('positionTo', tr(rack_PositionType::FLOOR_NAME));
                    break;
                case 'rack2rack':
                    $form->setField('zones', 'input=none');
                    $form->setReadOnly('palletId');
                    $form->setField('palletId', 'caption=Преместване на нова позиция->Палет');
                    $form->setField('positionTo', 'caption=Преместване на нова позиция->Позиция');
                    $form->setField('note', 'caption=Преместване на нова позиция->Забележка');
                    
                    if (isset($bestPos)) {
                        $form->setDefault('positionTo', $bestPos);
                    }
                    break;
            }
        }
    }
    
    
    public static function validateZonesTable($tableData, $Type)
    {
        $tableData = (array) $tableData;
        if (empty($tableData)) {
            
            return;
        }
        
        $res = $zones = $error = $errorFields = array();
        
        foreach ($tableData['zone'] as $key => $zone) {
            if (!empty($zone) && empty($tableData['quantity'][$key])) {
                $error[] = 'Липсва количество при избрана зона';
                $errorFields['quantity'][$key] = 'Липсва количество при избрана зона';
            }
            
            if (array_key_exists($zone, $zones)) {
                $error[] = 'Повтаряща се зона';
                $errorFields['zone'][$key] = 'Повтаряща се зона';
            } else {
                $zones[$zone] = $zone;
            }
        }
        
        foreach ($tableData['quantity'] as $key => $quantity) {
            if (!empty($quantity) && empty($tableData['zone'][$key])) {
                $error[] = 'Зададено количество без зона';
                $errorFields['zone'][$key] = 'Зададено количество без зона';
            }
            
            if (empty($quantity)) {
                $error[] = 'Количеството не може да е 0';
                $errorFields['quantity'][$key] = 'Количеството не може да е 0';
            }
            
            $Double = core_Type::getByName('double');
            $q2 = $Double->fromVerbal($quantity);
            if (!$q2) {
                $error[] = 'Невалидно количество';
                $errorFields['quantity'][$key] = 'Невалидно количество';
            }
        }
        
        if (count($error)) {
            $error = implode('|*<li>|', $error);
            $res['error'] = $error;
        }
        
        if (count($errorFields)) {
            $res['errorFields'] = $errorFields;
        }
        
        return $res;
    }
    
    
    /**
     * След подготовката на заглавието на формата
     */
    protected static function on_AfterPrepareEditTitle($mvc, &$res, &$data)
    {
        // По-хубаво заглавие на формата
        $rec = $data->form->rec;
        
        switch ($rec->movementType) {
            case 'floor2rack':
                $title = core_Detail::getEditTitle('store_Stores', $rec->storeId, 'нов палет', $rec->id, tr('в'));
                break;
            case 'rack2floor':
                $title = 'Сваляне на палет на пода в склад|* ' . cls::get('store_Stores')->getFormTitleLink($rec->storeId);
                break;
            default:
                $title = core_Detail::getEditTitle('store_Stores', $rec->storeId, $mvc->singleTitle, $rec->id, tr('в'));
                break;
        }
        
        $data->form->title = $title;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        core_RowToolbar::createIfNotExists($row->_rowTools);
        
        if ($mvc->haveRightFor('toggle', $rec) && $rec->state != 'active') {
            $row->_rowTools->addLink('Започване', array($mvc, 'toggle', $rec->id, 'ret_url' => true), 'ef_icon=img/16/control_play.png,title=Започване на движението');
            $state .= ht::createBtn('Започни', array($mvc, 'toggle', $rec->id, 'ret_url' => true), false, false, 'ef_icon=img/16/control_play.png,title=Започване на движението');
        }
        if ($mvc->haveRightFor('done', $rec)) {
            $row->_rowTools->addLink('Приключване', array($mvc, 'done', $rec->id, 'ret_url' => true), 'ef_icon=img/16/gray-close.png,title=Приключване на движението');
            $state .= ht::createBtn('Приключи', array($mvc, 'done', $rec->id, 'ret_url' => true), false, false, 'ef_icon=img/16/gray-close.png,title=Приключване на движението');
        }
        
        if ($mvc->haveRightFor('toggle', $rec) && $rec->state != 'pending') {
            $row->_rowTools->addLink('Отказване', array($mvc, 'toggle', $rec->id, 'ret_url' => true), 'warning=Наистина ли искате да откажете движението|*?,ef_icon=img/16/reject.png,title=Отказ на движението');
        }
        
        if (!empty($state)) {
            $row->workerId .= ' ' . $state;
        }
        
        if (!empty($rec->note)) {
            $row->note = "<div style='font-size:0.8em;'>{$row->note}</div>";
        }
        
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        
        if (!isset($fields['-inline'])) {
            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
            
            $row->palletToId = isset($rec->palletToId) ? rack_Pallets::getTitleById($rec->palletToId) : ((isset($rec->positionTo) ? $mvc->getVerbal($rec, 'positionTo') : "<span class='quiet'>" . tr('Под||Floor') . '</span>'));
            
            if (isset($rec->palletToId)) {
                $row->palletToId = rack_Pallets::getTitleById($rec->palletToId);
            }
            
            // Ре-вербализиране на зоните, да се показват с номерата си
            if (!empty($rec->zones)) {
                $zones = rack_Zones::getFreeZones($rec->storeId);
                $Type = core_Type::getByName('table(columns=zone|quantity,captions=Зона|Количество,widths=10em|10em)');
                $Type->params['zone_opt'] = $zones;
                $row->zones = $Type->toVerbal($rec->zones);
            }
        } else {
            if (isset($rec->palletId)) {
                $row->palletId = rack_Pallets::getVerbal($rec->palletId, 'label');
            }
            
            $row->packQuantity = ht::styleIfNegative($row->packQuantity, $rec->packQuantity);
            $row->packQuantity = "<b>{$row->packQuantity}</b>";
            $row->packagingId = cat_UoM::getShortName($rec->packagingId);
        }
        
        $row->palletId = isset($rec->palletId) ? rack_Pallets::getTitleById($rec->palletId) : "<span class='quiet'>" . tr('Под||Floor') . '</span>';
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'toggle' && isset($rec->state)) {
            if (!in_array($rec->state, array('pending', 'active'))) {
                $requiredRoles = 'no_one';
            }
            
            if ($rec->state == 'active' && $rec->workerId != $userId) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'done' && $rec && $rec->state) {
            if ($rec->state != 'active' || $rec->workerId != $userId) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'delete' && isset($rec) && $rec->state != 'pending') {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Добавя филтър към перата
     *
     * @param acc_Items $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $storeId = store_Stores::getCurrent();
        $data->title = 'Движения на палети в склад |*<b style="color:green">' . store_Stores::getTitleById($storeId) . '</b>';
        $data->query->where("#storeId = {$storeId}");
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'pending' THEN 1 WHEN 'active' THEN 2 ELSE 3 END)");
        
        if ($palletId = Request::get('palletId', 'int')) {
            $data->query->where("#palletId = {$palletId} OR #palletToId = {$palletId}");
        }
        
        $data->listFilter->setFieldType('state', 'enum(current=Текущи,pending=Чакащи,active=Активни,closed=Приключени,all=Всички)');
        $data->listFilter->setField('state', 'silent,input');
        $data->listFilter->setDefault('state', 'current');
        $data->listFilter->input();
        
        $data->listFilter->showFields = 'search,state';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
    
        if($state = $data->listFilter->rec->state){
            if($state == 'current'){
                $data->query->where("#state = 'active' || #state = 'pending'");
            } elseif(in_array($state, array('active', 'closed', 'pending'))){
                $data->query->where("#state = '{$state}'");
            }
        }
        
        $data->query->orderBy('orderByState=ASC,createdOn=DESC');
    }
    
    
    /**
     * Екшън за започване на движението
     */
    public function act_Toggle()
    {
        $this->requireRightFor('toggle');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('toggle', $rec);
        $oldState = $rec->state;
        
        $rec->state = ($oldState == 'pending') ? 'active' : 'pending';
        if ($rec->state == 'pending') {
            $rec->workerId = null;
            $rec->_canceled = true;
        }
        
        $rec->workerId = core_Users::getCurrent();
        $this->save($rec, 'state,workerId');
        
        followretUrl();
    }
    
    
    /**
     * Екшън за приключване на движението
     */
    public function act_Done()
    {
        $this->requireRightFor('done');
        $id = Request::get('id', 'int');
        expect($rec = $this->fetch($id));
        $this->requireRightFor('done', $rec);
        
        $rec->state = 'closed';
        $this->save($rec, 'state');
        
        followretUrl(array($this));
    }
    
    
    /**
     * Връща масив с всички използвани палети
     */
    public static function getExpected($storeId = null)
    {
        $storeId = isset($storeId) ? $storeId : store_Stores::getCurrent();
        $res = array(0 => array(), 1 => array());
        
        $query = self::getQuery();
        $query->where("#storeId = {$storeId} AND #state != 'closed'");
        while ($rec = $query->fetch()) {
            if ($rec->position) {
                $res[0][$rec->position] = $rec->productId;
            }
            
            if ($rec->positionTo) {
                $res[1][$rec->positionTo] = $rec->productId;
            }
        }
        
        return $res;
    }
    
    
    public static function getByZone($zoneId, $productId = null)
    {
        $res = array();
        $zoneRec = rack_Zones::fetchRec($zoneId);
        $query = self::getQuery();
        $query->where("#storeId = {$zoneRec->storeId} AND #zones IS NOT NULL AND #state != 'closed'");
        if (isset($productId)) {
            $query->where("#productId = {$productId}");
        }
        
        while ($rec = $query->fetch()) {
            $zoneTable = type_Table::toArray($rec->zones);
            foreach ($zoneTable as $obj) {
                if ($obj->zone == $zoneId) {
                    $o = (object) array('productId' => $rec->productId, 'state' => $rec->state, 'palletId' => $rec->palletId, 'quantity' => $obj->quantity);
                    $res[] = $o;
                }
            }
        }
        
        return $res;
    }
    
    
    /**
     * След подготовка на тулбара на списъчния изглед
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        if ($data->toolbar->buttons['btnAdd'] && !haveRole('admin,ceo')) {
            $data->toolbar->removeBtn('btnAdd');
        }
        
        if (haveRole('debug')) {
            $data->toolbar->addBtn('Изчистване', array($mvc, 'truncate'), 'warning=Искатели да изчистите таблицата,ef_icon=img/16/sport_shuttlecock.png');
        }
    }
    
    
    /**
     * Изчиства записите в балансите
     */
    public function act_Truncate()
    {
        requireRole('debug');
        
        // Изчистваме записите от моделите
        self::truncate();
        
        // Записваме, че потребителя е разглеждал този списък
        $this->logWrite('Изтриване на движенията в палетния склад');
        
        return new Redirect(array($this, 'list'), '|Записите са изчистени успешно');
    }
}
