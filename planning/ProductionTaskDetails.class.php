<?php


/**
 * Клас 'planning_ProductionTaskDetails'
 *
 * Мениджър за Прогрес на производствените операции
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_ProductionTaskDetails extends core_Detail
{
    /**
     * Заглавие
     */
    public $title = 'Прогрес на производствените операции';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Прогрес';
    
    
    /**
     * Интерфейси
     */
    public $interfaces = 'hr_IndicatorsSourceIntf';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'taskId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, plg_AlignDecimals2, plg_SaveAndNew, plg_Rejected, plg_Modified, plg_Created, plg_LastUsedKeys, plg_Sorting, planning_Wrapper, plg_Search, planning_Wrapper';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'fixedAsset';
    
    
    /**
     * Кой има право да оттегля?
     */
    public $canReject = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да възстановява?
     */
    public $canRestore = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да редактира?
     */
    public $canEdit = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да листва?
     */
    public $canList = 'taskWorker,ceo';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'type=Действие,serial,productId,taskId,quantity,shortUoM=Мярка,weight=Тегло (кг),employees,fixedAsset,modified=Модифициране,modifiedOn,modifiedBy';
    
    
    /**
     * Кои колони да скриваме ако янма данни в тях
     */
    public $hideListFieldsIfEmpty = 'serial,weight,employees,fixedAsset,scrappedQuantity';
    
    
    /**
     * Активен таб на менюто
     */
    public $currentTab = 'Операции->Прогрес';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,type,fixedAsset,employees,notes';
    
    
    /**
     * Брой записи на страница
     *
     * @var int
     */
    public $listItemsPerPage = 30;
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('taskId', 'key(mvc=planning_Tasks)', 'input=hidden,silent,mandatory,caption=Операция');
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'silent,caption=Артикул,removeAndRefreshForm=serial|quantity');
        $this->FLD('type', 'enum(input=Влагане,production=Произв.,waste=Отпадък)', 'input=hidden,silent,tdClass=small-field nowrap');
        $this->FLD('serial', 'varchar(32)', 'caption=Сер. №,smartCenter,focus,autocomplete=off');
        $this->FLD('serialType', 'enum(existing=Съществуващ,generated=Генериран,printed=Отпечатан,unknown=Непознат)', 'caption=Тип на серийния номер,input=none');
        $this->FLD('quantity', 'double(Min=0)', 'caption=Количество');
        $this->FLD('scrappedQuantity', 'double(Min=0)', 'caption=Брак,input=none');
        $this->FLD('weight', 'double', 'caption=Тегло,smartCenter,unit=кг');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id)', 'caption=Работници,tdClass=nowrap');
        $this->FLD('fixedAsset', 'key(mvc=planning_AssetResources,select=id)', 'caption=Оборудване,input=none,tdClass=nowrap');
        $this->FLD('notes', 'richtext(rows=2,bucket=Notes)', 'caption=Забележки');
        $this->FLD('state', 'enum(active=Активирано,rejected=Оттеглен)', 'caption=Състояние,input=none,notNull');
        $this->FLD('norm', 'time', 'caption=Време,input=none');
        
        $this->setDbIndex('type');
        $this->setDbIndex('serial');
        $this->setDbIndex('taskId,productId');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$data->form->rec;
        $masterRec = planning_Tasks::fetch($rec->taskId);
        
        // Добавяме последните данни за дефолтни
        $query = $mvc->getQuery();
        $query->where("#taskId = {$rec->taskId}");
        $query->orderBy('id', 'DESC');
        
        // Задаваме последно въведените данни
        if ($lastRec = $query->fetch()) {
            $form->setDefault('employees', $lastRec->employees);
            $form->setDefault('fixedAsset', $lastRec->fixedAsset);
        }
        
        // Ако в мастъра са посочени машини, задават се като опции
        if (isset($masterRec->fixedAssets)) {
            $keylist = $masterRec->fixedAssets;
            $arr = keylist::toArray($keylist);
            foreach ($arr as $key => &$value) {
                $value = planning_AssetResources::getTitleById($key, false);
            }
            $form->setOptions('fixedAsset', array('' => '') + $arr);
            $form->setField('fixedAsset', 'input');
        }
        
        $productOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
        $form->setOptions('productId', array('' => '') + $productOptions);
        
        if ($rec->type == 'production') {
            $form->setDefault('productId', $masterRec->productId);
            
            // При редакция на производството само брака може да се променя
            if (isset($rec->id)) {
                $form->setReadOnly('productId');
                $form->setReadOnly('serial');
                $form->setReadOnly('quantity');
                $form->setField('scrappedQuantity', 'input');
                $form->setFieldTypeParams('scrappedQuantity', array('max' => $rec->quantity, 'min' => 0));
                $form->setField('employees', 'input=none');
                $form->setField('fixedAsset', 'input=none');
                $form->setField('notes', 'input=none');
            }
        }
        
        // Ако наличната опция е само една, по дефолт е избрана
        if (count($productOptions) == 1 && $form->cmd != 'refresh') {
            $form->setDefault('productId', key($productOptions));
            $form->setReadOnly('productId');
        }
        
        // Ако е избран артикул
        if (isset($rec->productId)) {
            $pRec = cat_Products::fetch($rec->productId, 'measureId,canStore');
            if ($pRec->canStore != 'yes') {
                $form->setField('serial', 'input=none');
                if ($rest = $masterRec->plannedQuantity - $masterRec->totalQuantity) {
                    $form->setDefault('quantity', $rest);
                }
            }
            
            // Показване на очакваните к-ва
            if ($foundRec = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset)) {
                $packagingId = $foundRec->packagingId;
                $unit = cat_UoM::getShortName($foundRec->packagingId);
                if (!empty($foundRec->plannedQuantity) || !empty($foundRec->totalQuantity)) {
                    $totalQuantity = (!empty($foundRec->totalQuantity)) ? $foundRec->totalQuantity : 0;
                    $planned = tr('Планирано|*: <b>') . core_Type::getByName('double(smartRound)')->toVerbal($foundRec->plannedQuantity) . '</b>';
                    $real = tr('Изпълнено|*: <b>') . core_Type::getByName('double(smartRound)')->toVerbal($totalQuantity) . '</b>';
                    $form->info = "{$planned}<br>{$real}";
                }
                
                $form->setDefault('quantity', $foundRec->quantityInPack);
                $form->setField('quantity', "unit={$unit}");
            }
            
            $shortMeasure = cat_UoM::getShortName($pRec->measureId);
            if ($pRec->measureId != $packagingId) {
                $packName = $unit = cat_UoM::getShortName($packagingId);
                $unit = $shortMeasure . ' ' . tr('в') . ' ' . $packName;
                $form->setField('quantity', "unit={$unit}");
            } else {
                $form->setField('quantity', "unit={$shortMeasure}");
            }
        }
        
        // Връща избрани служители от операцията, или ако няма всички от центъра
        $employees = !empty($masterRec->employees) ? planning_Hr::getPersonsCodesArr($masterRec->employees) : planning_Hr::getByFolderId($masterRec->folderId);
        if (count($employees)) {
            $form->setSuggestions('employees', $employees);
            if (count($employees) == 1) {
                $form->setDefault('employees', keylist::addKey(key($employees), ''));
            }
        } else {
            $form->setField('employees', 'input=none');
        }
        
        // Показване на допълнителна мярка при нужда
        if ($masterRec->showadditionalUom == 'no') {
            $form->setField('weight', 'input=none');
        } elseif($masterRec->showadditionalUom == 'mandatory'){
            $form->setField('weight', 'mandatory');
        }
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if(!empty($rec->serial)){
                $rec->serial = plg_Search::normalizeText($rec->serial);
                $rec->serial = str::removeWhitespaces($rec->serial);
                if ($rec->productId && ($Driver = cat_Products::getDriver($rec->productId))) {
                    $rec->serial = $Driver->canonizeSerial($rec->productId, $rec->serial);
                }
            }
            
            $masterRec = planning_Tasks::fetch($rec->taskId);
            if (empty($rec->serial) && empty($rec->productId)) {
                $form->setError('serial,productId', 'Трябва да е въведен артикул или сериен номер');
            }
            
            $canStore = cat_Products::fetchField($rec->productId, 'canStore');
            if ($canStore == 'yes') {
                if ($rec->type == 'production') {
                    if (self::fetchField("#taskId = {$rec->taskId} AND #serial = '{$rec->serial}' AND #id != '{$rec->id}'")) {
                        $form->setError('serial', 'Сер. № при произвеждане трябва да е уникален');
                    }
                }
                
                if (!empty($rec->serial)) {
                    $serialInfo = self::fetchSerialInfo($rec->serial, $rec->productId, $rec->packagingId, $rec->id);
                    $rec->serialType = $serialInfo['type'];
                    
                    if (isset($serialInfo['error'])) {
                        $form->setError('serial', $serialInfo['error']);
                    }
                }
            } elseif ($rec->type == 'input') {
                
                // Ако артикула е действие към оборудването
                $inTp = planning_ProductionTaskProducts::fetchField("#taskId = {$rec->taskId} AND #type = 'input' AND #productId = {$rec->productId}");
                $inInputTask = planning_Tasks::fetchField("#originId = {$masterRec->originId} AND #inputInTask = {$rec->taskId} AND #state != 'draft' AND #state != 'rejected' AND #state != 'pending' AND #productId = {$rec->productId}");
                
                // Подсигуряване че трябва да има норма
                if (empty($inTp) && empty($inInputTask)) {
                    if (!planning_AssetResources::getNormRec($rec->fixedAsset, $rec->productId)) {
                        $form->setError('productId,fixedAsset', 'Изберете оборудване, което има норма за действието');
                    }
                }
            }
            
            if (!$form->gotErrors()) {
                $rec->quantity = (empty($rec->quantity)) ? 1 : $rec->quantity;
                
                $limit = '';
                if (isset($rec->productId) && $rec->type !== 'production') {
                    if (!$mvc->checkLimit($rec, $limit)) {
                        $limit = core_Type::getByName('double(smartRound)')->toVerbal($limit);
                        $form->setError('quantity', "Надвишаване на допустимото максимално количество|* <b>{$limit}</b>");
                    }
                }
            }
            
            $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
            if (isset($info->indTime)) {
                $rec->norm = $info->indTime;
            }
        }
    }
    
    
    /**
     * Преди запис на документ, изчислява стойността на полето `isContable`
     *
     * @param core_Manager $mvc
     * @param stdClass     $rec
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if ($canStore == 'yes') {
            if ($rec->type == 'production' && empty($rec->serial)) {
                if ($Driver = cat_Products::getDriver($rec->productId)) {
                    $rec->serial = $Driver->generateSerial($rec->productId, 'planning_Tasks', $rec->taskId);
                    $rec->serialType = 'generated';
                }
            }
            
            if (!empty($rec->serial)) {
                if ($Driver = cat_Products::getDriver($rec->productId)) {
                    $rec->serial = $Driver->canonizeSerial($rec->productId, $rec->serial);
                }
                $rec->searchKeywords .= ' ' . plg_Search::normalizeText($rec->serial);
            }
        }
    }
    
    
    /**
     * Информация за серийния номер
     *
     * @param string   $serial
     * @param int      $productId
     * @param int      $packagingId
     * @param int|NULL $id
     *
     * @return array $res
     */
    private static function fetchSerialInfo($serial, $productId, $packagingId, $id)
    {
        if (!$Driver = cat_Products::getDriver($productId)) {
            
            return;
        }
        $res = array('serial' => $serial, 'productId' => $productId, 'type' => 'unknown');
        
        $canonizedSerial = $Driver->canonizeSerial($productId, $serial);
        $exRec = self::fetch(array("#serial = '[#1#]' AND #id != '[#2#]'", $canonizedSerial, $productId));
        if (!empty($exRec)) {
            $res['type'] = 'existing';
            $res['productId'] = $exRec->productId;
        } else {
            if ($pRec = $Driver->getRecBySerial($serial)) {
                $res['type'] = 'existing';
                $res['productId'] = $pRec->id;
            }
        }
        
        $error = '';
        if ($res['productId'] != $productId) {
            $res['error'] = 'Серийния номер е към друг артикул|*: <b>' . cat_Products::getHyperlink($res['productId'], true) . '</b>';
        } elseif (!$Driver->checkSerial($productId, $serial, $error)) {
            $res['error'] = $error;
        }
        
        return $res;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        if (isset($rec->fixedAsset)) {
            $row->fixedAsset = planning_AssetResources::getHyperlink($rec->fixedAsset);
        }
        
        $row->taskId = planning_Tasks::getLink($rec->taskId, 0);
        $row->modified = "<div class='nowrap'>" . $mvc->getFieldType('modifiedOn')->toVerbal($rec->modifiedOn);
        $row->modified .= ' ' . tr('от||by') . ' ' . crm_Profiles::createLink($rec->modifiedBy) . '</div>';
        
        if (isset($rec->serial)) {
            $row->serial = "<b>{$row->serial}</b>";
        }
        
        $row->ROW_ATTR['class'] = ($rec->state == 'rejected') ? 'state-rejected' : (($rec->type == 'input') ? 'row-added' : (($rec->type == 'production') ? 'state-active' : 'row-removed'));
        if ($rec->state == 'rejected') {
            $row->ROW_ATTR['title'] = tr('Оттеглено от') . ' ' . core_Users::getVerbal($rec->modifiedBy, 'nick');
        }
        
        $pRec = cat_Products::fetch($rec->productId, 'measureId,code,isPublic,nameEn,name');
        $code = cat_Products::getVerbal($pRec, 'code');
        $code = ht::createHint($code, cat_Products::getVerbal($pRec, 'name'), 'notice', false);
        $row->productId = ht::createLinkRef($code, cat_Products::getSingleUrlArray($rec->productId));
        $shortUom = cat_UoM::getShortName($pRec->measureId);
        $packagingId = $pRec->measureId;
        
        $foundRec = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
        if (!empty($foundRec)) {
            $packagingId = $foundRec->packagingId;
        }
        
        if ($pRec->measureId != $packagingId) {
            $packagingId = cat_UoM::getShortName($packagingId);
            $row->type .= ' ' . tr($packagingId);
        } elseif ($rec->type == 'production') {
            $row->type = tr('Произвеждане');
        }
        
        if (!empty($rec->scrappedQuantity)) {
            $rec->quantity = $rec->quantity - $rec->scrappedQuantity;
            $row->scrappedQuantity = $mvc->getFieldType('scrappedQuantity')->toVerbal($rec->scrappedQuantity);
        }
        
        $row->shortUoM = tr($shortUom);
        
        if (!empty($rec->notes)) {
            $notes = $mvc->getFieldType('notes')->toVerbal($rec->notes);
            $row->productId .= "<small>{$notes}</small>";
        }
        
        if (!empty($rec->serial)) {
            $row->serial = self::getLink($rec->taskId, $rec->serial);
        }
        
        if (isset($rec->employees)) {
            $row->employees = self::getVerbalEmployees($rec->employees);
        }
    }
    
    
    /**
     * Връща серийния номер като линк, ако е от друга операция
     *
     * @param int    $taskId - в коя операция ще се показва
     * @param string $serial - серийния номер
     *
     * @return core_ET|string $serialVerbal  - серийния номер като линк, или вербалното му представяне
     */
    public static function getLink($taskId, $serial)
    {
        $serialVerbal = core_Type::getByName('varchar(32)')->toVerbal($serial);
        if (Mode::isReadOnly()) {
            
            return $serialVerbal;
        }
        
        // Линк към прогреса филтриран по сериен номер
        if (planning_ProductionTaskDetails::haveRightFor('list')) {
            $serialVerbal = ht::createLink($serialVerbal, array('planning_ProductionTaskDetails', 'list', 'search' => $serialVerbal), false, 'title=Към историята на серийния номер');
        }
        
        return $serialVerbal;
    }
    
    
    /**
     * Преди рендиране на таблицата
     */
    protected static function on_BeforeRenderListTable($mvc, &$tpl, $data)
    {
        if (isset($data->masterMvc)) {
            $data->listTableMvc->FNC('shortUoM', 'varchar', 'tdClass=nowrap');
            $data->listTableMvc->setField('productId', 'tdClass=nowrap');
        }
        
        $rows = &$data->rows;
        if (!count($rows)) {
            
            return;
        }
        
        foreach ($rows as $id => $row) {
            $rec = $data->recs[$id];
            
            if (!empty($row->shortUoM)) {
                $row->quantity = "<b>{$row->quantity}</b>";
                
                if (!empty($row->scrappedQuantity)) {
                    $hint = "Брак|* {$row->scrappedQuantity} {$row->shortUoM}";
                    $row->quantity = ht::createHint($row->quantity, $hint, 'warning', false, 'width=14px;height=14px');
                }
            }
            
            
            if(!empty($rec->weight)){
                $transportWeight = cat_Products::getTransportWeight($rec->productId, $rec->quantity);
                
                
                // Проверка има ли отклонение спрямо очакваното транспортно тегло
                if(!empty($transportWeight)){
                    $deviation = abs(round(($transportWeight - $rec->weight) / ($transportWeight + $rec->weight) / 2, 2));
                    $weightTolerancePercent = planning_Setup::get('ALLOWED_WEIGHT_TOLERANCE');
                    
                    if($deviation > $weightTolerancePercent){
                        $row->weight = ht::createHint($row->weight, 'Разминаване спрямо очакваното транспортно тегло', 'warning', false);
                    }
                }
            }
        }
    }
    
    
    /**
     * Показва вербалното име на служителите
     *
     * @param string $employees - кейлист от служители
     *
     * @return string $verbalEmployees
     */
    public static function getVerbalEmployees($employees)
    {
        $verbalEmployees = array();
        $employees = keylist::toArray($employees);
        foreach ($employees as $eId) {
            $el = planning_Hr::getCodeLink($eId);
            $verbalEmployees[$eId] = $el;
        }
        
        return implode(', ', $verbalEmployees);
    }
    
    
    /**
     * Извиква се след успешен запис в модела
     *
     * @param core_Mvc $mvc
     * @param int      $id  първичния ключ на направения запис
     * @param stdClass $rec всички полета, които току-що са били записани
     */
    protected static function on_AfterSave(core_Mvc $mvc, &$id, $rec)
    {
        // Ъпдейт на общото к-во в детайла
        planning_ProductionTaskProducts::updateTotalQuantity($rec->taskId, $rec->productId, $rec->type);
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    protected static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        // Документа не може да се създава  в нова нишка, ако е възоснова на друг
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('btnAdd');
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'production'))) {
                $data->toolbar->addBtn('Произвеждане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'production', 'ret_url' => true), false, 'ef_icon = img/16/package.png,title=Добавяне на произведен артикул');
            }
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'input'))) {
                $data->toolbar->addBtn('Влагане', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'input', 'ret_url' => true), false, 'ef_icon = img/16/wooden-box.png,title=Добавяне на вложен артикул');
            }
            
            if ($mvc->haveRightFor('add', (object) array('taskId' => $data->masterId, 'type' => 'waste'))) {
                $data->toolbar->addBtn('Отпадък', array($mvc, 'add', 'taskId' => $data->masterId, 'type' => 'waste', 'ret_url' => true), false, 'ef_icon = img/16/recycle.png,title=Добавяне на отпаден артикул');
            }
        }
    }
    
    
    /**
     * Подготвя детайла
     */
    public function prepareDetail_($data)
    {
        $data->TabCaption = 'Прогрес';
        $data->Tab = 'top';
        
        parent::prepareDetail_($data);
    }
    
    
    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        if(Mode::is('getLinkedFiles')) return;
        
        $data->listFilter->setField('type', 'input=none');
        unset($data->listFields['modified']);
        $data->listFilter->class = 'simpleForm';
        if (isset($data->masterMvc)) {
            $data->listFilter->FLD('threadId', 'int', 'silent,input=hidden');
            $data->listFilter->view = 'horizontal';
            $data->listFilter->input(null, 'silent');
        }
        
        $data->listFilter->showFields = 'search,fixedAsset,employees';
        $data->listFilter->setOptions('fixedAsset', array('' => '') + planning_AssetResources::getByFolderId());
        $data->listFilter->setOptions('employees', array('' => '') + crm_Persons::getEmployeesOptions(false, true));
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        $data->listFilter->input();
        if ($filter = $data->listFilter->rec) {
            if (isset($filter->fixedAsset)) {
                $data->query->where("#fixedAsset = '{$filter->fixedAsset}'");
            }
            if (isset($filter->employees)) {
                $data->query->where("LOCATE('|{$filter->employees}|', #employees)");
            }
        }
    }
    
    
    /**
     * Преди извличане на записите от БД
     */
    protected static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orWhere("#state = 'rejected'");
        $data->query->orderBy('createdOn', 'DESC');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (($action == 'add' || $action == 'reject' || $action == 'restore' || $action == 'edit' || $action == 'delete') && isset($rec->taskId)) {
            $state = $mvc->Master->fetchField($rec->taskId, 'state');
            
            if ($state != 'active' && $state != 'waiting' && $state != 'wakeup') {
                $requiredRoles = 'no_one';
            }
        }
        
        // Трябва да има поне един артикул възможен за добавяне
        if ($action == 'add' && isset($rec->type) && $rec->type != 'product' && $rec->type != 'start') {
            if ($requiredRoles != 'no_one') {
                $pOptions = planning_ProductionTaskProducts::getOptionsByType($rec->taskId, $rec->type);
                if (!count($pOptions)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'edit' && isset($rec)) {
            if ($rec->type != 'production' || $rec->state == 'rejected') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Преди подготовка на заглавието на формата
     */
    protected static function on_BeforePrepareEditTitle($mvc, &$res, $data)
    {
        $rec = &$data->form->rec;
        $data->singleTitle = ($rec->type == 'input') ? 'влагане' : (($rec->type == 'waste') ? 'отпадък' : 'произвеждане');
    }
    
    
    /**
     * Метод за вземане на резултатност на хората. За определена дата се изчислява
     * успеваемостта на човека спрямо ресурса, които е изпозлвал
     *
     * @param datetime $timeline - Времето, след което да се вземат всички модифицирани/създадени записи
     *
     * @return array $result  - масив с обекти
     *
     * 			o date        - дата на стайноста
     * 		    o personId    - ид на лицето
     *          o docId       - ид на документа
     *          o docClass    - клас ид на документа
     *          o indicatorId - ид на индикатора
     *          o value       - стойноста на индикатора
     *          o isRejected  - оттеглена или не. Ако е оттеглена се изтрива от индикаторите
     */
    public static function getIndicatorValues($timeline)
    {
        $result = array();
        $query = self::getQuery();
        $query->where("#modifiedOn >= '{$timeline}' AND #norm IS NOT NULL");
        $query->EXT('indTimeAllocation', 'planning_Tasks', 'externalName=indTimeAllocation,externalKey=taskId');
        
        $iRec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $classId = planning_Tasks::getClassId();
        $indicatorId = $iRec->id;
        
        while ($rec = $query->fetch()) {
            
            // Ако няма служители, пропуска се
            $persons = keylist::toArray($rec->employees);
            if (!count($persons)) {
                continue;
            }
            
            // Количеството ако е прозвеждано е винаги 1-ца от производствената опаковка, ако е влагане или отпадък е колкото е количеството
            $quantity = ($rec->type == 'production') ? 1 : $rec->quantity;
            
            // Колко е заработката за 1 човек
            $timePerson = ($rec->indTimeAllocation == 'individual') ? $quantity * $rec->norm : (($quantity * $rec->norm) / count($persons));
            
            $date = dt::verbal2mysql($rec->createdOn, false);
            foreach ($persons as $personId) {
                $key = "{$personId}|{$classId}|{$rec->taskId}|{$rec->state}|{$date}|{$indicatorId}";
                if (!array_key_exists($key, $result)) {
                    $result[$key] = (object) array('date' => $date,
                        'personId' => $personId,
                        'docId' => $rec->taskId,
                        'docClass' => $classId,
                        'indicatorId' => $indicatorId,
                        'value' => 0,
                        'isRejected' => ($rec->state == 'rejected'));
                }
                
                $result[$key]->value += $timePerson;
            }
        }
        
        return $result;
    }
    
    
    /**
     * Интерфейсен метод на hr_IndicatorsSourceIntf
     *
     * @param datetime $date
     *
     * @return array $result
     */
    public static function getIndicatorNames()
    {
        $result = array();
        $rec = hr_IndicatorNames::force('Време', __CLASS__, 1);
        $result[$rec->id] = $rec->name;
        
        return $result;
    }
    
    
    /**
     * Проверка дали лимита е надвишен
     *
     * @param stdClass $rec
     * @param float    $limit
     *
     * @return bool
     */
    private function checkLimit($rec, &$limit = null)
    {
        $info = planning_ProductionTaskProducts::getInfo($rec->taskId, $rec->productId, $rec->type, $rec->fixedAsset);
        if (empty($info->limit)) {
            
            return true;
        }
        
        $query = self::getQuery();
        $query->XPR('sum', 'double', 'SUM(#quantity)');
        $query->where("#taskId = {$rec->taskId} AND #productId = {$rec->productId} AND #fixedAsset = '{$rec->fixedAsset}' AND #id != '{$rec->id}' AND #state = 'active'");
        $query->show('sum');
        $sum = $query->fetch()->sum;
        
        $sum += $rec->quantity;
        
        if ($sum > $info->limit) {
            $limit = $info->limit;
            
            return false;
        }
        
        return true;
    }
    
    
    /**
     * Изпълнява се преди възстановяването на документа
     */
    protected static function on_BeforeRestore(core_Mvc $mvc, &$res, $id)
    {
        $rec = $mvc->fetchRec($id);
        
        $limit = '';
        if (!$mvc->checkLimit($rec, $limit)) {
            $limit = core_Type::getByName('double(smartRound)')->toVerbal($limit);
            core_Statuses::newStatus("Не може да се възстанови, защото ще се надвиши максималното количество от|*: <b>{$limit}</b>", 'error');
            
            return false;
        }
    }
}
