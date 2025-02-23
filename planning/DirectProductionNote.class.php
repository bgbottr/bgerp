<?php


/**
 * Клас 'planning_DirectProductionNote' - Документ за производство
 *
 *
 *
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
class planning_DirectProductionNote extends planning_ProductionDocument
{
    /**
     * Заглавие
     */
    public $title = 'Протоколи за производство';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Mpn';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_DirectProductionNote,acc_AllowArticlesCostCorrectionDocsIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, bgerp_plg_Blank,doc_plg_HidePrices, deals_plg_SetTermDate, plg_Sorting,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'debitAmount';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'productId,storeId,inputStoreId,expenseItemId,note';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,planning,store,production';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,planning,store,production';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning,store,production';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning,store,production';
    
    
    /**
     * Кой има право да контира?
     */
    public $canConto = 'ceo,planning,store,production';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,planning,store,production';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за производство';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutDirectProductionNote.shtml';
    
    
    /**
     * Детайл
     */
    public $details = 'planning_DirectProductNoteDetails';
    
    
    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'planning_DirectProductNoteDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_DirectProductNoteDetails';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/page_paste.png';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'valior, title=Документ, productId, packQuantity=К-во, packagingId=Мярка,storeId=В склад,expenseItemId=Разход за, folderId, deadline, createdOn, createdBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'deadline,expenseItemId,storeId';
    
    
    /**
     * Какво движение на партида поражда документа в склада
     *
     * @param out|in|stay - тип движение (излиза, влиза, стои)
     */
    public $batchMovementDocument = 'in';
    
    
    /**
     * Нужно ли е да има детайл, за да стане на 'Заявка'
     */
    public $requireDetailForPending = false;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setDocumentFields($this);
        $this->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул,mandatory,before=storeId');
        $this->FLD('jobQuantity', 'double(smartRound)', 'caption=Задание,input=hidden,mandatory,after=productId');
        
        $this->FLD('packagingId', 'key(mvc=cat_UoM, select=shortName, select2MinItems=0)', 'caption=Мярка', 'mandatory,input=hidden,before=packQuantity');
        $this->FNC('packQuantity', 'double(Min=0,smartRound)', 'caption=Количество,input,mandatory,after=jobQuantity');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none,notNull,value=1');
        $this->FLD('quantity', 'double(smartRound,Min=0)', 'caption=Количество,input=none');
        
        $this->FLD('expenses', 'percent(Min=0)', 'caption=Реж. разходи,after=quantity');
        $this->setField('storeId', 'caption=Складове->Засклаждане в,after=expenses,silent,removeAndRefreshForm');
        $this->FLD('inputStoreId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Складове->Влагане от,after=storeId,input');
        $this->FLD('debitAmount', 'double(smartRound)', 'input=none');
        $this->FLD('expenseItemId', 'acc_type_Item(select=titleNum,allowEmpty,lists=600,allowEmpty)', 'input=none,after=expenses,caption=Разходен обект / Продажба->Избор');
        
        $this->setDbIndex('productId');
    }
    
    
    /**
     * Изчисляване на количеството на реда в брой опаковки
     */
    protected static function on_CalcPackQuantity(core_Mvc $mvc, $rec)
    {
        if (empty($rec->quantity) || empty($rec->quantityInPack)) {
            
            return;
        }
        
        $rec->packQuantity = $rec->quantity / $rec->quantityInPack;
    }
    
    
    /**
     * Подготвя формата за редактиране
     */
    public function prepareEditForm_($data)
    {
        parent::prepareEditForm_($data);
        $form = &$data->form;
        $rec = $form->rec;
        
        $originRec = doc_Containers::getDocument($form->rec->originId)->rec();
        $form->setDefault('storeId', $originRec->storeId);
        $form->setDefault('productId', $originRec->productId);
        $form->setReadOnly('productId');
        
        $packs = cat_Products::getPacks($rec->productId);
        $form->setOptions('packagingId', $packs);
        $form->setDefault('packagingId', $originRec->packagingId);
        
        // Ако артикула не е складируем, скриваме полето за мярка
        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if ($canStore == 'no') {
            $measureShort = cat_UoM::getShortName($rec->packagingId);
            $form->setField('packQuantity', "unit={$measureShort}");
        } else {
            $form->setField('packagingId', 'input');
        }
        
        $form->setDefault('jobQuantity', $originRec->quantity);
        $quantityFromTasks = planning_Tasks::getProducedQuantityForJob($originRec->id);
        $quantityToStore = $quantityFromTasks - $originRec->quantityProduced;
        
        if ($quantityToStore > 0) {
            $form->setDefault('packQuantity', $quantityToStore / $originRec->quantityInPack);
        }
        
        $bomRec = cat_Products::getLastActiveBom($originRec->productId, 'production');
        if (!$bomRec) {
            $bomRec = cat_Products::getLastActiveBom($originRec->productId, 'sales');
        }
        
        if (isset($bomRec->expenses)) {
            $form->setDefault('expenses', $bomRec->expenses);
        }
        
        $productInfo = cat_Products::getProductInfo($form->rec->productId);
        
        if (!isset($productInfo->meta['canStore'])) {
            
            // Ако артикула е нескладируем и не е вложим и не е ДА, показваме полето за избор на разходно перо
            if (!isset($productInfo->meta['canConvert']) && !isset($productInfo->meta['fixedAsset'])) {
                $form->setField('expenseItemId', 'input');
            }
            
            // Ако заданието, към което е протокола е към продажба, избираме я по дефолт
            if (empty($form->rec->id) && isset($originRec->saleId)) {
                $saleItem = acc_Items::fetchItem('sales_Sales', $originRec->saleId);
                $form->setDefault('expenseItemId', $saleItem->id);
            }
            
            $form->setField('storeId', 'input=none');
            $form->setField('inputStoreId', array('caption' => 'Допълнително->Влагане от'));
        }
        
        $nQuery = self::getQuery();
        $nQuery->where("#originId = {$rec->originId} AND (#state = 'active' || #state = 'pending')");
        $nQuery->where("#id != '{$rec->id}'");
        $nQuery->orderBy('id', 'DESC');
        $nQuery->limit(1);
        
        if ($lastRec = $nQuery->fetch()) {
            $form->setDefault('storeId', $lastRec->storeId);
            $form->setDefault('inputStoreId', $lastRec->inputStoreId);
        }
        
        $form->setDefault('storeId', store_Stores::getCurrent('id', false));
        
        return $data;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     *
     * @param core_Mvc  $mvc
     * @param core_Form $form
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            $productInfo = cat_Products::getProductInfo($form->rec->productId);
            if (!isset($productInfo->meta['canStore'])) {
                $rec->storeId = null;
            } else {
                $rec->dealId = null;
            }
            
            $rec->quantityInPack = ($productInfo->packagings[$rec->packagingId]) ? $productInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->quantity = $rec->packQuantity * $rec->quantityInPack;
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->productId = cat_Products::getShortHyperlink($rec->productId);
        $shortUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
        $row->quantity .= " {$shortUom}";
        
        if (isset($rec->debitAmount)) {
            $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
            $row->debitAmount .= " <span class='cCode'>{$baseCurrencyCode}</span>, " . tr('без ДДС');
        }
        
        if (isset($rec->expenseItemId)) {
            $row->expenseItemId = acc_Items::getVerbal($rec->expenseItemId, 'titleLink');
        }
        
        $row->subTitle = (isset($rec->storeId)) ? 'Засклаждане на продукт' : 'Производство на услуга';
        $row->subTitle = tr($row->subTitle);
        
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        
        if (isset($rec->inputStoreId)) {
            $row->inputStoreId = store_Stores::getHyperlink($rec->inputStoreId, true);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add') {
            if (isset($rec)) {
                
                // Трябва да има ориджин
                if (empty($rec->originId)) {
                    $requiredRoles = 'no_one';
                } else {
                    
                    // Ориджина трябва да е задание за производство
                    $originDoc = doc_Containers::getDocument($rec->originId);
                    
                    if (!$originDoc->isInstanceOf('planning_Jobs')) {
                        $requiredRoles = 'no_one';
                    } else {
                        
                        // Което не е чернова или оттеглено
                        $state = $originDoc->fetchField('state');
                        if ($state == 'rejected' || $state == 'draft' || $state == 'closed') {
                            $requiredRoles = 'no_one';
                        } else {
                            
                            // Ако артикула от заданието не е производим не можем да добавяме документ
                            $productId = $originDoc->fetchField('productId');
                            $canManifacture = cat_Products::fetchField($productId, 'canManifacture');
                            if ($canManifacture != 'yes') {
                                $requiredRoles = 'no_one';
                            }
                        }
                    }
                }
            }
        }
        
        // Ако екшъна е за задаване на дебитна сума
        if ($action == 'adddebitamount') {
            $requiredRoles = $mvc->getRequiredRoles('conto', $rec, $userId);
            if ($requiredRoles != 'no_one') {
                if (isset($rec)) {
                    if (planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id}", 'id')) {
                        $requiredRoles = 'no_one';
                    }
                }
            }
        }
        
        // При опит за форсиране на документа, като разходен обект
        if ($action == 'forceexpenseitem' && isset($rec->id)) {
            if ($requiredRoles != 'no_one') {
                $pRec = cat_Products::fetch($rec->productId, 'canStore,canConvert,fixedAsset');
                if ($pRec->canStore == 'no') {
                    if ($pRec->canConvert == 'yes' || $pRec->fixedAsset == 'yes') {
                        $requiredRoles = 'no_one';
                    } else {
                        $expenseItemId = acc_Items::forceSystemItem('Неразпределени разходи', 'unallocated', 'costObjects')->id;
                        if (isset($rec->expenseItemId) && $rec->expenseItemId != $expenseItemId) {
                            $requiredRoles = 'no_one';
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * Намира количествата за влагане от задачите
     *
     * @param stdClass $rec
     *
     * @return array $res
     */
    protected function getDefaultDetails($rec)
    {
        $res = array();
        
        // Намираме детайлите от задачите и рецеоптите
        $bomId = null;
        $bomDetails = $this->getDefaultDetailsFromBom($rec, $bomId);
        $taskDetails = $this->getDefaultDetailsFromTasks($rec);
        
        // Ако има рецепта
        if ($bomId) {
            
            // И тя има етапи
            $bomQuery = cat_BomDetails::getQuery();
            $bomQuery->where("#bomId = {$bomId}");
            $bomQuery->where("#type = 'stage'");
            $stages = array();
            while ($bRec = $bomQuery->fetch()) {
                $stages[$bRec->resourceId] = $bRec->resourceId;
            }
            
            // Махаме от артикулите от задачите, тези които са етапи в рецептата, защото
            // реално те няма да се влагат от склада а се произвеждат на място
            if (count($stages)) {
                foreach ($taskDetails as $i => $det) {
                    if (in_array($det->productId, $stages)) {
                        unset($taskDetails[$i]);
                    }
                }
            }
        }
        
        // За всеки артикул от рецептата добавяме го
        foreach ($bomDetails as $index => $bRec) {
            $obj = clone $bRec;
            $obj->quantityFromTasks = $taskDetails[$index]->quantityFromTasks;
            
            $res[$index] = $obj;
        }
        
        // За всеки артикул от задачата добавяме го
        foreach ($taskDetails as $index => $tRec) {
            $obj = clone $tRec;
            if (!isset($res[$index])) {
                $res[$index] = $obj;
            }
            $res[$index]->quantityFromBom = $bomDetails[$index]->quantityFromBom;
        }
        
        // За всеки детайл намираме дефолтното к-во ако има такова от рецепта, взимаме него иначе от задачите
        foreach ($res as &$detail) {
            $detail->quantity = (isset($detail->quantityFromBom)) ? $detail->quantityFromBom : $detail->quantityFromTasks;
        }
        
        // Връщаме намерените дефолтни детайли
        return $res;
    }
    
    
    /**
     * Намира количествата за влагане от задачите
     *
     * @param stdClass $rec
     *
     * @return array $details
     */
    protected function getDefaultDetailsFromTasks($rec)
    {
        $details = array();
        
        // Намираме всички непроизводствени действия от задачи
        $aQuery = planning_ProductionTaskDetails::getQuery();
        $aQuery->EXT('taskState', 'planning_Tasks', 'externalName=state,externalKey=taskId');
        $aQuery->EXT('originId', 'planning_Tasks', 'externalName=originId,externalKey=taskId');
        $aQuery->where("#originId = {$rec->originId} AND #type != 'production' AND (#taskState = 'active' || #taskState = 'stopped' || #taskState = 'wakeup')");
        $aQuery->XPR('sumQuantity', 'double', 'SUM(#quantity)');
        $aQuery->groupBy('productId,type');
        
        // Събираме ги в масив
        while ($aRec = $aQuery->fetch()) {
            $obj = new stdClass();
            $obj->productId = $aRec->productId;
            $obj->type = ($aRec->type == 'input') ? 'input' : 'pop';
            $obj->quantityInPack = 1;
            $obj->quantityFromTasks = $aRec->sumQuantity;
            $obj->packagingId = cat_Products::fetchField($obj->productId, 'measureId');
            $obj->measureId = $obj->packagingId;
            
            $index = $obj->productId . '|' . $obj->type;
            $details[$index] = $obj;
        }
        
        // Връщаме намерените детайли
        return $details;
    }
    
    
    /**
     * Връща дефолт детайлите на документа, които съотвестват на ресурсите
     * в последната активна рецепта за артикула
     *
     * @param stdClass $rec - запис
     *
     * @return array $details - масив с дефолтните детайли
     */
    protected function getDefaultDetailsFromBom($rec, &$bomId)
    {
        $details = array();
        $originRec = doc_Containers::getDocument($rec->originId)->rec();
        
        // Ако артикула има активна рецепта
        $bomId = cat_Products::getLastActiveBom($rec->productId, 'production')->id;
        if (!$bomId) {
            $bomId = cat_Products::getLastActiveBom($rec->productId, 'sales')->id;
        }
        
        // Ако ням рецепта, не могат да се определят дефолт детайли за влагане
        if (!$bomId) {
            
            return $details;
        }
        
        // К-ко е произведено до сега и колко ще произвеждаме
        $quantityProduced = $originRec->quantityProduced;
        $quantityToProduce = $rec->quantity + $quantityProduced;
        
        // Извличаме информацията за ресурсите в рецептата за двете количества
        $bomInfo1 = cat_Boms::getResourceInfo($bomId, $quantityProduced, dt::now());
        $bomInfo2 = cat_Boms::getResourceInfo($bomId, $quantityToProduce, dt::now());
        
        // За всеки ресурс
        foreach ($bomInfo2['resources'] as $index => $resource) {
            
            // Задаваме данните на ресурса
            $dRec = new stdClass();
            $dRec->productId = $resource->productId;
            $dRec->type = $resource->type;
            $dRec->packagingId = $resource->packagingId;
            $dRec->quantityInPack = $resource->quantityInPack;
            
            // Дефолтното к-вво ще е разликата между к-та за произведеното до сега и за произведеното в момента
            $dRec->quantityFromBom = $resource->propQuantity - $bomInfo1['resources'][$index]->propQuantity;
            
            $pInfo = cat_Products::getProductInfo($resource->productId);
            $dRec->measureId = $pInfo->productRec->measureId;
            $index = $dRec->productId . '|' . $dRec->type;
            $details[$index] = $dRec;
        }
        
        // Връщаме генерираните детайли
        return $details;
    }
    
    
    /**
     * Изпълнява се след създаване на нов запис
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        // Ако записа е клониран не правим нищо
        if ($rec->_isClone === true) {
            
            return;
        }
        
        // Ако могат да се генерират детайли от артикула да се
        $details = $mvc->getDefaultDetails($rec);
        
        if ($details !== false) {
            
            // Ако могат да бъдат определени дефолт детайли според артикула, записваме ги
            if (count($details)) {
                foreach ($details as $dRec) {
                    $dRec->noteId = $rec->id;
                    
                    // Склада за влагане се добавя само към складируемите артикули, които не са отпадъци
                    if (isset($rec->inputStoreId)) {
                        if (cat_Products::fetchField($dRec->productId, 'canStore') == 'yes' && $dRec->type != 'pop') {
                            $dRec->storeId = $rec->inputStoreId;
                        }
                    }
                    
                    planning_DirectProductNoteDetails::save($dRec);
                }
            }
        }
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
        // При активиране/оттегляне
        if ($rec->state == 'active' || $rec->state == 'rejected') {
            if (isset($rec->originId)) {
                $origin = doc_Containers::getDocument($rec->originId);
                
                planning_Jobs::updateProducedQuantity($origin->that);
                doc_DocumentCache::threadCacheInvalidation($rec->threadId);
            }
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        if ($rec->state == 'active') {
            if (planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id}")) {
                if (cat_Boms::haveRightFor('add', (object) array('productId' => $rec->productId, 'originId' => $rec->originId))) {
                    $bomUrl = array($mvc, 'createBom', $data->rec->id);
                    $data->toolbar->addBtn('Рецепта', $bomUrl, null, 'ef_icon = img/16/add.png,title=Създаване на нова рецепта по протокола');
                }
            }
        }
        
        if ($data->toolbar->haveButton('btnConto')) {
            if ($mvc->haveRightFor('adddebitamount', $rec)) {
                $data->toolbar->removeBtn('btnConto');
                $attr = (!haveRole('seePrice') && !self::getDefaultDebitPrice($rec)) ? array('error' => 'Документът не може да бъде контиран, защото артикула няма себестойност') : ((!haveRole('seePrice') ? array('warning' => 'Наистина ли желаете документът да бъде контиран') : array()));
                $data->toolbar->addBtn('Контиране', array($mvc, 'addDebitAmount', $rec->id, 'ret_url' => array($mvc, 'single', $rec->id)), 'id=btnConto,ef_icon = img/16/tick-circle-frame.png,title=Контиране на протокола за производство', $attr);
            }
        }
    }
    
    
    /**
     * Връща дефолтната себестойност за артикула
     *
     * @param mixed stdClass $rec
     *
     * @return mixed $price
     */
    private static function getDefaultDebitPrice($rec)
    {
        return cat_Products::getPrimeCost($rec->productId, $rec->packagingId, $rec->jobQuantity, $rec->valior);
    }
    
    
    /**
     * Екшън изискващ подаване на себестойност, когато се опитваме да произведем артикул, без да сме специфицирали неговите материали
     */
    public function act_addDebitAmount()
    {
        // Проверка на параметрите
        $this->requireRightFor('adddebitamount');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        $this->requireRightFor('adddebitamount', $rec);
        
        $form = cls::get('core_Form');
        $url = $this->getSingleUrlArray($id);
        $docTitle = ht::createLink($this->getTitleById($id), $url, false, "ef_icon={$this->singleIcon},class=linkInTitle");
        
        // Подготовка на формата
        $form->title = "Въвеждане на себестойност за|* <b style='color:#ffffcc;'>{$docTitle}</b>";
        $form->info = tr('Не може да се определи себестойността, защото няма посочени материали');
        $form->FLD('debitPrice', 'double(Min=0)', 'caption=Ед. Себест-ст,mandatory');
        
        // Ако драйвера може да върне себестойност тя е избрана по дефолт
        $defPrice = self::getDefaultDebitPrice($rec);
        if (isset($defPrice)) {
            $form->setDefault('debitPrice', $defPrice);
        }
        
        $baseCurrencyCode = acc_Periods::getBaseCurrencyCode($rec->valior);
        $form->setField('debitPrice', "unit=|*{$baseCurrencyCode} |без ДДС|*");
        $form->input();
        
        if (!haveRole('seePrice')) {
            if (isset($defPrice)) {
                $form->method = 'GET';
                $form->cmd = 'save';
            } else {
                followRetUrl(null, 'Документът не може да бъде контиран, защото няма себестойност', 'error');
            }
        }
        
        if ($form->isSubmitted()) {
            $amount = $form->rec->debitPrice * $rec->quantity;
            
            // Ъпдейъваме подадената себестойност
            $rec->debitAmount = $amount;
            $this->save($rec, 'debitAmount');
            $this->logWrite('Задаване на себестойност', $rec->id);
            
            // Редирект към екшъна за контиране
            redirect($this->getContoUrl($id));
        }
        
        $form->toolbar->addSbBtn('Контиране', 'save', 'ef_icon = img/16/tick-circle-frame.png, title = Контиране на документа');
        $form->toolbar->addBtn('Отказ', getRetUrl(), 'ef_icon = img/16/close-red.png, title=Прекратяване на действията');
        
        $tpl = $this->renderWrapping($form->renderHtml());
        core_Form::preventDoubleSubmission($tpl, $form);
        
        return $tpl;
    }
    
    
    /**
     * Екшън създаващ нова рецепта по протокола
     */
    public function act_CreateBom()
    {
        cat_Boms::requireRightFor('add');
        expect($id = Request::get('id', 'int'));
        expect($rec = $this->fetch($id));
        cat_Boms::requireRightFor('add', (object) array('productId' => $rec->productId, 'originId' => $rec->originId));
        
        // Подготвяме детайлите на рецептата
        $dQuery = planning_DirectProductNoteDetails::getQuery();
        $dQuery->where("#noteId = {$id}");
        
        $recsToSave = array();
        
        while ($dRec = $dQuery->fetch()) {
            $index = "{$dRec->productId}|{$dRec->type}";
            if (!array_key_exists($index, $recsToSave)) {
                $recsToSave[$index] = (object) array('resourceId' => $dRec->productId,
                    'type' => $dRec->type,
                    'propQuantity' => 0,
                    'packagingId' => $dRec->packagingId,
                    'quantityInPack' => $dRec->quantityInPack);
            }
            
            $recsToSave[$index]->propQuantity += $dRec->quantity;
            if ($dRec->quantityInPack < $recsToSave[$index]->quantityInPack) {
                $recsToSave[$index]->quantityInPack = $dRec->quantityInPack;
                $recsToSave[$index]->packagingId = $dRec->packagingId;
            }
        }
        
        foreach ($recsToSave as &$pRec) {
            $pRec->propQuantity /= $pRec->quantityInPack;
        }
        
        // Създаваме новата рецепта
        $newId = cat_Boms::createNewDraft($rec->productId, $rec->quantity, $rec->originId, $recsToSave, null, $rec->expenses);
        
        // Записваме, че потребителя е разглеждал този списък
        cat_Boms::logWrite('Създаване на рецепта от протокол за производство', $newId);
        
        // Редирект
        return new Redirect(array('cat_Boms', 'single', $newId), '|Успешно е създадена нова рецепта');
    }
    
    
    /**
     * Документа винаги може да се активира, дори и да няма детайли
     */
    public static function canActivate($rec)
    {
        $rec = static::fetchRec($rec);
        
        if (isset($rec->id)) {
            $input = planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id} AND #type = 'input'", 'id');
            $pop = planning_DirectProductNoteDetails::fetchField("#noteId = {$rec->id} AND #type = 'pop'", 'id');
            if ($pop && !$input) {
                
                return false;
            }
        }
        
        return true;
    }
    
    
    /**
     * Извиква се след като документа стане разходен обект
     */
    protected static function on_AfterForceCostObject($mvc, $rec)
    {
        // Реконтиране на документа
        acc_Journal::reconto($rec->containerId);
    }
    
    
    /**
     * Списък с артикули върху, на които може да им се коригират стойностите
     *
     * @see acc_AllowArticlesCostCorrectionDocsIntf
     *
     * @param mixed $id - ид или запис
     *
     * @return array $products        - масив с информация за артикули
     *               o productId       - ид на артикул
     *               o name            - име на артикула
     *               o quantity        - к-во
     *               o amount          - сума на артикула
     *               o inStores        - масив с ид-то и к-то във всеки склад в който се намира
     *               o transportWeight - транспортно тегло на артикула
     *               o transportVolume - транспортен обем на артикула
     */
    public function getCorrectableProducts($id)
    {
        $products = array();
        $rec = $this->fetchRec($id);
        
        $products[$rec->productId] = (object) array('productId' => $rec->productId,
            'quantity' => $rec->quantity,
            'name' => cat_Products::getTitleById($rec->productId, false),
            'amount' => $rec->quantity);
        
        if ($transportWeight = cat_Products::getTransportWeight($rec->productId, 1)) {
            $products[$rec->productId]->transportWeight = $transportWeight;
        }
        
        if ($transportVolume = cat_Products::getTransportVolume($rec->productId, 1)) {
            $products[$rec->productId]->transportVolume = $transportVolume;
        }
        
        if (isset($rec->storeId)) {
            $products[$rec->productId]->inStores[$rec->storeId] = $rec->quantity;
        }
        
        return $products;
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        
        // или към нишка на продажба/артикул/задание
        return $firstDoc->isInstanceOf('sales_Sales') || $firstDoc->isInstanceOf('cat_Products') || $firstDoc->isInstanceOf('planning_Jobs');
    }
    
    
    /**
     * Създаване на протокол за производство на артикул
     * Ако може след създаването ще зареди артикулите от активната рецепта и/или задачите
     *
     * @param int       $jobId     - ид на задание
     * @param int       $productId - ид на артикул
     * @param float     $quantity  - к-во за произвеждане
     * @param datetime  $valior    - вальор
     * @param array $fields    - допълнителни параметри
     *                         ['storeId']       - ид на склад за засклаждане
     *                         ['expenseItemId'] - ид на перо на разходен обект
     *                         ['expenses']      - режийни разходи
     *                         ['batch']         - партиден номер
     *                         ['inputStoreId']  - дефолтен склад за влагане
     */
    public static function createDraft($jobId, $productId, $quantity, $valior = null, $fields = array())
    {
        $rec = new stdClass();
        expect($jRec = planning_Jobs::fetch($jobId), 'Няма такова задание');
        expect($jRec->state != 'rejected' && $jRec->state != 'draft', 'Заданието не е активно');
        expect($productRec = cat_Products::fetch($productId, 'canManifacture,canStore,fixedAsset,canConvert'));
        $rec->valior = ($valior) ? $valior : dt::today();
        $rec->valior = dt::verbal2mysql($rec->valior);
        $rec->originId = $jRec->containerId;
        $rec->threadId = $jRec->threadId;
        $rec->productId = $productId;
        expect($productRec->canManifacture = 'yes', 'Артикулът не е производим');
        
        $Double = cls::get('type_Double');
        expect($rec->quantity = $Double->fromVerbal($quantity));
        if ($productRec->canStore == 'yes') {
            expect($fields['storeId'], 'За складируем артикул е нужен склад');
            expect(store_Stores::fetch($fields['storeId']), "Несъществуващ склад {$fields['storeId']}");
            $rec->storeId = $fields['storeId'];
        } else {
            if ($rec->canConvert == 'yes') {
                $rec->expenseItemId = acc_CostAllocations::getUnallocatedItemId();
            } else {
                expect($fields['expenseItemId'], 'Няма разходен обект');
                expect(acc_Items::fetch($fields['expenseItemId']), 'Няма такова перо');
                $rec->expenseItemId = $fields['expenseItemId'];
            }
        }
        
        if (isset($fields['inputStoreId'])) {
            expect(store_Stores::fetch($fields['inputStoreId']), "Несъществуващ склад за влагане {$fields['inputStoreId']}");
            $rec->inputStoreId = $fields['inputStoreId'];
        }
        
        if (isset($fields['expenses'])) {
            expect($fields['expenses']);
            expect($fields['expenses'] >= 0 && $fields['expenses'] <= 1);
            $rec->expenses = $fields['expenses'];
        }
        
        if (isset($fields['batch'])) {
            if (core_Packs::isInstalled('batch')) {
                expect($Def = batch_Defs::getBatchDef($productId), 'Опит за задаване на партида на артикул без партида');
                $msg = null;
                if (!$Def->isValid($fields['batch'], $quantity, $msg)) {
                    expect(false, tr($msg));
                }
                
                $rec->batch = $Def->normalize($fields['batch']);
                $rec->isEdited = true;
            }
        }
        
        // Създаване на запис
        self::route($rec);
        
        return self::save($rec);
    }
    
    
    /**
     * АПИ метод за добавяне на детайл към протокол за производство
     *
     * @param int      $id             - ид на артикул
     * @param int      $productId      - ид на продукт
     * @param int      $packagingId    - ид на опаковка
     * @param float    $packQuantity   - к-во опаковка
     * @param float    $quantityInPack - к-во в опаковка
     * @param bool     $isWaste        - дали е отпадък или не
     * @param int|NULL $storeId        - ид на склад, или NULL ако е от незавършеното производство
     */
    public static function addRow($id, $productId, $packagingId, $packQuantity, $quantityInPack, $isWaste = false, $storeId = null)
    {
        // Проверки на параметрите
        expect($noteRec = self::fetch($id), "Няма протокол с ид {$id}");
        expect($noteRec->state == 'draft', 'Протокола трябва да е чернова');
        expect($productRec = cat_Products::fetch($productId, 'canConvert,canStore'), "Няма артикул с ид {$productId}");
        if ($isWaste) {
            expect($productRec->canConvert == 'yes', 'Артикулът трябва да е вложим');
            expect($productRec->canStore == 'yes', 'Артикулът трябва да е складируем');
        } else {
            expect($productRec->canConvert == 'yes', 'Артикулът трябва да е вложим');
        }
        
        expect($packagingId, 'Няма мярка/опаковка');
        expect(cat_UoM::fetch($packagingId), "Няма опаковка/мярка с ид {$packagingId}");
        
        if ($productRec->canStore != 'yes') {
            expect(empty($storeId), 'За нескладируем артикул не може да се подаде склад');
        }
        
        if (isset($storeId)) {
            expect(store_Stores::fetch($storeId), 'Невалиден склад');
        }
        
        $packs = cat_Products::getPacks($productId);
        expect(isset($packs[$packagingId]), "Артикулът не поддържа мярка/опаковка с ид {$packagingId}");
        
        $Double = cls::get('type_Double');
        expect($quantityInPack = $Double->fromVerbal($quantityInPack), "Невалидно к-во {$quantityInPack}");
        expect($packQuantity = $Double->fromVerbal($packQuantity), "Невалидно к-во {$packQuantity}");
        $quantity = $quantityInPack * $packQuantity;
        
        // Подготовка на записа
        $rec = (object) array('noteId' => $id,
            'type' => ($isWaste) ? 'pop' : 'input',
            'productId' => $productId,
            'packagingId' => $packagingId,
            'quantityInPack' => $quantityInPack,
            'quantity' => $quantity,
        );
        
        if (isset($storeId)) {
            $rec->storeId = $storeId;
        }
        
        planning_DirectProductNoteDetails::save($rec);
    }
    
    
    /**
     * Какво да е предупреждението на бутона за контиране
     *
     * @param int    $id         - ид
     * @param string $isContable - какво е действието
     *
     * @return NULL|string - текста на предупреждението или NULL ако няма
     */
    public function getContoWarning_($id, $isContable)
    {
        $warning = null;
        $dQuery = planning_DirectProductNoteDetails::getQuery();
        $dQuery->where("#noteId = {$id} AND #storeId IS NOT NULL");
        
        $productsWithNegativeQuantity = array();
        while ($dRec = $dQuery->fetch()) {
            $available = deals_Helper::getAvailableQuantityAfter($dRec->productId, $dRec->storeId, $dRec->quantity);
            if ($available < 0) {
                $productsWithNegativeQuantity[$dRec->storeId][] = cat_Products::getTitleById($dRec->productId, false);
            }
        }
        
        if (count($productsWithNegativeQuantity)) {
            $warning = 'Контирането на документа ще доведе до отрицателни количества по|*: ';
            foreach ($productsWithNegativeQuantity as $storeId => $products) {
                $warning .= implode(', ', $products) . ', |в склад|* ' . store_Stores::getTitleById($storeId) . ' |и|* ';
            }
            
            $warning = rtrim($warning, ' |и|* ');
        }
        
        return $warning;
    }
}
