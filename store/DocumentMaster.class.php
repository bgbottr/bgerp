<?php


/**
 * Абстрактен клас за наследяване на складови документи
 *
 *
 * @category  bgerp
 * @package   store
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class store_DocumentMaster extends core_Master
{
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId, locationId, deliveryTime, lineId, contragentClassId, contragentId, weight, volume, folderId, id';
    
    
    /**
     * Поле в което се замества шаблона от doc_TplManager
     */
    public $templateFld = 'SINGLE_CONTENT';
    
    
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * На кой ред в тулбара да се показва бутона за принтиране
     */
    public $printBtnToolbarRow = 1;
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('template' => 'lastDocUser|lastDoc|LastDocSameCuntry');
    
    
    /**
     * Флаг, който указва дали документа да се кешира в треда
     */
    public $cacheInThread = true;
    
    
    /**
     * Дата на очакване
     */
    public $termDateFld = 'deliveryTime';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Нужно ли е да има детайл, за да стане на 'Заявка'
     */
    public $requireDetailForPending = false;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,deliveryTime,modifiedOn';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'valior, amountDelivered, amountDeliveredVat, amountDiscount, deliveryTime,weight,volume,weightInput,volumeInput,lineId';
    
    
    /**
     * След описанието на полетата
     */
    protected static function setDocFields(core_Master &$mvc)
    {
        $mvc->FLD('valior', 'date', 'caption=Дата');
        $mvc->FLD('currencyId', 'customKey(mvc=currency_Currencies,key=code,select=code,allowEmpty)', 'input=none,caption=Плащане->Валута,smartCenter');
        $mvc->FLD('currencyRate', 'double(decimals=5)', 'caption=Валута->Курс,input=hidden');
        $mvc->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=От склад, mandatory');
        $mvc->FLD('chargeVat', 'enum(yes=Включено ДДС в цените, separate=Отделен ред за ДДС, exempt=Освободено от ДДС, no=Без начисляване на ДДС)', 'caption=ДДС,input=hidden');
        
        $mvc->FLD('amountDelivered', 'double(decimals=2)', 'caption=Доставено->Сума,input=none,summary=amount,smartCenter'); // Сумата на доставената стока
        $mvc->FLD('amountDeliveredVat', 'double(decimals=2)', 'caption=Доставено->ДДС,input=none,summary=amount,smartCenter');
        $mvc->FLD('amountDiscount', 'double(decimals=2)', 'input=none');
        
        // Контрагент
        $mvc->FLD('contragentClassId', 'class(interface=crm_ContragentAccRegIntf)', 'input=hidden,caption=Клиент');
        $mvc->FLD('contragentId', 'int', 'input=hidden');
        
        // Доставка
        $mvc->FLD('locationId', 'key(mvc=crm_Locations, select=title,allowEmpty)', 'caption=Обект до,silent');
        $mvc->FLD('deliveryTime', 'datetime');
        $mvc->FLD('lineId', 'key(mvc=trans_Lines,select=title,allowEmpty)', 'caption=Транспорт');
        
        // Допълнително
        $mvc->FLD('weight', 'cat_type_Weight', 'input=none,caption=Тегло');
        $mvc->FLD('volume', 'cat_type_Volume', 'input=none,caption=Обем');
        
        $mvc->FLD('note', 'richtext(bucket=Notes,rows=6)', 'caption=Допълнително->Бележки');
        $mvc->FLD(
            'state',
                'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)',
                'caption=Статус, input=none'
        );
        $mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
        $mvc->FLD('accountId', 'customKey(mvc=acc_Accounts,key=systemId,select=id)', 'input=none,notNull,value=411');
        
        $mvc->setDbIndex('valior');
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'store_Stores', 'storeId')) {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = &$form->rec;
        
        $form->setDefault('storeId', store_Stores::getCurrent('id', false));
        $rec->contragentClassId = doc_Folders::fetchCoverClassId($rec->folderId);
        $rec->contragentId = doc_Folders::fetchCoverId($rec->folderId);
        if (!trans_Lines::count("#state = 'active'")) {
            $form->setField('lineId', 'input=none');
        }
        
        // Поле за избор на локация - само локациите на контрагента по продажбата
        $form->getField('locationId')->type->options =
        array('' => '') + crm_Locations::getContragentOptions($rec->contragentClassId, $rec->contragentId);
        
        expect($origin = ($form->rec->originId) ? doc_Containers::getDocument($form->rec->originId) : doc_Threads::getFirstDocument($form->rec->threadId));
        expect($origin->haveInterface('bgerp_DealAggregatorIntf'));
        $dealInfo = $origin->getAggregateDealInfo();
        $form->dealInfo = $dealInfo;
        
        $form->setDefault('currencyId', $dealInfo->get('currency'));
        $form->setDefault('currencyRate', $dealInfo->get('rate'));
        $form->setDefault('locationId', $dealInfo->get('deliveryLocation'));
        $form->setDefault('deliveryTime', $dealInfo->get('deliveryTime'));
        $form->setDefault('chargeVat', $dealInfo->get('vatType'));
        $form->setDefault('storeId', $dealInfo->get('storeId'));
    }
    
    
    /**
     * След изпращане на формата
     */
    protected static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
        if ($form->isSubmitted()) {
            $rec = &$form->rec;
            
            // Ако има локация и тя е различна от договорената, слагаме предупреждение
            if (!empty($rec->locationId) && $form->dealInfo->get('deliveryLocation') && $rec->locationId != $form->dealInfo->get('deliveryLocation')) {
                $agreedLocation = crm_Locations::getTitleById($form->dealInfo->get('deliveryLocation'));
                $form->setWarning('locationId', "Избраната локация е различна от договорената \"{$agreedLocation}\"");
            }
        }
    }
    
    
    /**
     * Обновява данни в мастъра
     *
     * @param int $id първичен ключ на статия
     *
     * @return int $id ид-то на обновения запис
     */
    public function updateMaster_($id)
    {
        $rec = $this->fetchRec($id);
        
        $Detail = $this->mainDetail;
        $query = $this->{$Detail}->getQuery();
        $query->where("#{$this->{$Detail}->masterKey} = '{$rec->id}'");
        
        $recs = $query->fetchAll();
        
        deals_Helper::fillRecs($this, $recs, $rec);
        
        // ДДС-т е отделно amountDeal  е сумата без ддс + ддс-то, иначе самата сума си е с включено ддс
        $amount = ($rec->chargeVat == 'separate') ? $this->_total->amount + $this->_total->vat : $this->_total->amount;
        $amount -= $this->_total->discount;
        $rec->amountDelivered = $amount * $rec->currencyRate;
        $rec->amountDeliveredVat = $this->_total->vat * $rec->currencyRate;
        $rec->amountDiscount = $this->_total->discount * $rec->currencyRate;
        
        return $this->save($rec);
    }
    
    
    /**
     * След създаване на запис в модела
     */
    protected static function on_AfterCreate($mvc, $rec)
    {
        $origin = $mvc::getOrigin($rec);
        
        // Ако документа е клониран пропуска се
        if ($rec->_isClone === true) {
            
            return;
        }
        
        // Ако новосъздадения документ има origin, който поддържа bgerp_AggregateDealIntf,
        // използваме го за автоматично попълване на детайлите на документа
        if ($origin->haveInterface('bgerp_DealAggregatorIntf')) {
            
            // Ако документа е обратен не слагаме продукти по дефолт
            if ($rec->isReverse == 'yes') {
                
                return;
            }
            
            $copyBatches = false;
            $Detail = $mvc->mainDetail;
            $aggregatedDealInfo = $origin->getAggregateDealInfo();
            if ($rec->importProducts != 'all') {
                $agreedProducts = $aggregatedDealInfo->get('products');
                $shippedProducts = $aggregatedDealInfo->get('shippedProducts');
                
                if (count($shippedProducts)) {
                    $normalizedProducts = deals_Helper::normalizeProducts(array($agreedProducts), array($shippedProducts));
                } else {
                    $copyBatches = true;
                    $agreedProducts = $aggregatedDealInfo->get('dealProducts');
                }
                
                if ($rec->importProducts == 'stocked') {
                    foreach ($agreedProducts as $i1 => $p1) {
                        $inStock = store_Products::fetchField("#storeId = {$rec->storeId} AND #productId = {$p1->productId}", 'quantity');
                        if ($p1->quantity > $inStock) {
                            unset($agreedProducts[$i1]);
                        }
                    }
                }
            } else {
                $agreedProducts = $aggregatedDealInfo->get('dealProducts');
                $normalizedProducts = $aggregatedDealInfo->get('dealProducts');
            }
            
            if (count($agreedProducts)) {
                foreach ($agreedProducts as $index => $product) {
                    $info = cat_Products::getProductInfo($product->productId);
                    
                    if (isset($normalizedProducts[$index])) {
                        $toShip = $normalizedProducts[$index]->quantity;
                    } else {
                        $toShip = $product->quantity;
                    }
                    
                    $price = (isset($agreedProducts[$index]->price)) ? $agreedProducts[$index]->price : $normalizedProducts[$index]->price;
                    $discount = ($agreedProducts[$index]->discount) ? $agreedProducts[$index]->discount : $normalizedProducts[$index]->discount;
                    
                    // Пропускат се експедираните продукти
                    if ($toShip <= 0) {
                        continue;
                    }
                    
                    $shipProduct = new stdClass();
                    $shipProduct->{$mvc->{$Detail}->masterKey} = $rec->id;
                    $shipProduct->productId = $product->productId;
                    $shipProduct->packagingId = $product->packagingId;
                    $shipProduct->quantity = $toShip;
                    $shipProduct->price = $price;
                    $shipProduct->discount = $discount;
                    $shipProduct->notes = $product->notes;
                    $shipProduct->quantityInPack = $product->quantityInPack;
                    
                    if (core_Packs::isInstalled('batch') && $copyBatches === true) {
                        $shipProduct->isEdited = false;
                        $shipProduct->_clonedWithBatches = true;
                    }
                    
                    $Detail::save($shipProduct);
                    
                    // Копира партидата ако артикулите идат 1 към 1 от договора
                    if (core_Packs::isInstalled('batch') && $copyBatches === true) {
                        if (is_array($product->batches)) {
                            foreach ($product->batches as $bRec) {
                                unset($bRec->id);
                                $bRec->detailClassId = $mvc->{$Detail}->getClassId();
                                $bRec->detailRecId = $shipProduct->id;
                                $bRec->containerId = $rec->containerId;
                                $bRec->date = $rec->valior;
                                $bRec->storeId = $rec->storeId;
                                
                                batch_BatchesInDocuments::save($bRec);
                            }
                        }
                    }
                }
            }
        }
    }
    
    
    /**
     * След рендиране на сингъла
     */
    protected static function on_AfterRenderSingle($mvc, $tpl, $data)
    {
        if (Mode::is('printing') || Mode::is('text', 'xhtml')) {
            $tpl->removeBlock('header');
        }
        
        if (store_DocumentPackagingDetail::haveRightFor('add', (object) array('documentClassId' => $mvc->getClassId(), 'documentId' => $data->rec->id))) {
            $btnIn = ht::createBtn('Отг.пазене: ПРЕДАВАНЕ', array('store_DocumentPackagingDetail', 'add', 'documentClassId' => $mvc->getClassId(), 'documentId' => $data->rec->id, 'type' => 'out','ret_url' => true), false, false, 'title=Отговорно пазене: предаване КЪМ Контрагент,ef_icon=img/16/lorry_add.png');
            $btnOut = ht::createBtn('Отг.пазене: ПРИЕМАНЕ', array('store_DocumentPackagingDetail', 'add', 'documentClassId' => $mvc->getClassId(), 'documentId' => $data->rec->id, 'type' => 'in','ret_url' => true), false, false, 'title=Отговорно пазене: приемане ОТ Контрагент,ef_icon=img/16/lorry_add.png');
            $tpl->append($btnIn, 'PACKAGING_BTNS');
            $tpl->append($btnOut, 'PACKAGING_BTNS');
        }
    }
    
    
    /**
     * Подготвя данните (в обекта $data) необходими за единичния изглед
     */
    public function prepareSingle_($data)
    {
        parent::prepareSingle_($data);
        
        $rec = &$data->rec;
        if (empty($data->noTotal)) {
            $data->summary = deals_Helper::prepareSummary($this->_total, $rec->valior, $rec->currencyRate, $rec->currencyId, $rec->chargeVat, false, $rec->tplLang);
            $data->row = (object) ((array) $data->row + (array) $data->summary);
        }
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        if (!empty($rec->currencyRate)) {
            $amountDelivered = $rec->amountDelivered / $rec->currencyRate;
        } else {
            $amountDelivered = $rec->amountDelivered;
        }
        
        $row->amountDelivered = $mvc->getFieldType('amountDelivered')->toVerbal($amountDelivered);
        
        if (isset($fields['-list'])) {
            if ($rec->amountDelivered) {
                $row->amountDelivered = "{$row->amountDelivered}";
            } else {
                $row->amountDelivered = "<span class='quiet'>0.00</span>";
            }
            
            $row->title = $mvc->getLink($rec->id, 0);
        }
        
        if (isset($fields['-single'])) {
            core_Lg::push($rec->tplLang);
            
            $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId);
            $row = (object) ((array) $row + (array) $headerInfo);
            
            if ($rec->locationId) {
                $row->locationId = crm_Locations::getHyperlink($rec->locationId);
                if ($ourLocation = store_Stores::fetchField($rec->storeId, 'locationId')) {
                    $row->ourLocation = crm_Locations::getTitleById($ourLocation);
                    $ourLocationAddress = crm_Locations::getAddress($ourLocation);
                    if ($ourLocationAddress != '') {
                        $row->ourLocationAddress = $ourLocationAddress;
                    }
                }
                
                $contLocationAddress = crm_Locations::getAddress($rec->locationId);
                if ($contLocationAddress != '') {
                    $row->deliveryLocationAddress = core_Lg::transliterate($contLocationAddress);
                }
                
                if ($gln = crm_Locations::fetchField($rec->locationId, 'gln')) {
                    $row->deliveryLocationAddress = $gln . ', ' . $row->deliveryLocationAddress;
                    $row->deliveryLocationAddress = trim($row->deliveryLocationAddress, ', ');
                }
                
                if ($locTel = crm_Locations::fetchField($rec->locationId, 'tel')) {
                    $locTel = core_Type::getByName('varchar')->toVerbal($locTel);
                    $row->deliveryLocationAddress .= ", {$locTel}";
                }
                
                if ($locMol = crm_Locations::fetchField($rec->locationId, 'mol')) {
                    $locMol = core_Type::getByName('varchar')->toVerbal($locMol);
                    $row->deliveryLocationAddress .= ", {$locMol}";
                }
            }
            
            $row->storeId = store_Stores::getHyperlink($rec->storeId);
            core_Lg::pop();
            
            if ($rec->isReverse == 'yes') {
                $row->operationSysId = tr('Връщане на стока');
            }
        } else if (isset($fields['-list'])) {
            if (doc_Setup::get('LIST_FIELDS_EXTRA_LINE') != 'no') {
                $row->title = "<b>" . $row->title . "</b>";
                $row->title .= "  «  " . $row->folderId;
                $row->createdBy = crm_Profiles::createLink($rec->createdBy);
                $row->title .= "<span class='fright'>" . $row->createdOn . " " . tr('от') . " " .   $row->createdBy . "</span>";
            }
        }
    }
    
    
    /**
     * Документа не може да бъде начало на нишка; може да се създава само в съществуващи нишки
     */
    public static function canAddToFolder($folderId)
    {
        return false;
    }
    
    
    /**
     * Може ли документа да се добави в посочената нишка?
     *
     * @param int $threadId key(mvc=doc_Threads)
     *
     * @return bool
     */
    public static function canAddToThread($threadId)
    {
        $firstDoc = doc_Threads::getFirstDocument($threadId);
        $docState = $firstDoc->fetchField('state');
        
        // Може да се добавя само към активиран документ
        if ($docState == 'active') {
            if ($firstDoc->haveInterface('bgerp_DealAggregatorIntf')) {
                $operations = $firstDoc->getShipmentOperations();
                
                return (isset($operations[static::$defOperationSysId])) ? true : false;
            }
        }
        
        return false;
    }
    
    
    /**
     * Връща масив от използваните нестандартни артикули в документа
     *
     * @param int $id - ид на документа
     *
     * @return array $res - масив с използваните документи
     *               ['class'] - инстанция на документа
     *               ['id'] - ид на документа
     */
    public function getUsedDocs_($id)
    {
        return deals_Helper::getUsedDocs($this, $id);
    }
    
    
    /**
     * @see doc_DocumentIntf::getDocumentRow()
     */
    public function getDocumentRow($id)
    {
        expect($rec = $this->fetch($id));
        $title = $this->getRecTitle($rec);
        
        $row = (object) array(
            'title' => $title,
            'authorId' => $rec->createdBy,
            'author' => $this->getVerbal($rec, 'createdBy'),
            'state' => $rec->state,
            'recTitle' => $title
        );
        
        return $row;
    }
    
    
    /**
     * Променяме шаблона в зависимост от мода
     */
    protected static function on_BeforeRenderSingleLayout($mvc, &$tpl, $data)
    {
        if (Mode::is('renderHtmlInLine') && isset($mvc->layoutFileInLine)) {
            $data->singleLayout = getTplFromFile($mvc->layoutFileInLine);
            unset($data->_selectTplForm);
        }
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public function loadSetupData()
    {
        $res = '';
        $this->setTemplates($res);
        
        return $res;
    }
    
    
    /**
     * Имплементация на @link bgerp_DealIntf::getDealInfo()
     *
     * @param int|object $id
     *
     * @return bgerp_iface_DealAggregator
     *
     * @see bgerp_DealIntf::getDealInfo()
     */
    public function pushDealInfo($id, &$aggregator)
    {
        $rec = $this->fetchRec($id);
        
        // Конвертираме данъчната основа към валутата идваща от продажбата
        $aggregator->setIfNot('deliveryLocation', $rec->locationId);
        $aggregator->setIfNot('deliveryTime', $rec->deliveryTime);
        $aggregator->setIfNot('storeId', $rec->storeId);
        $aggregator->setIfNot('shippedValior', $rec->valior);
        
        $Detail = $this->mainDetail;
        $dQuery = $this->{$Detail}->getQuery();
        $dQuery->where("#{$this->{$Detail}->masterKey} = {$rec->id}");
        
        // Подаваме на интерфейса най-малката опаковка с която е експедиран продукта
        while ($dRec = $dQuery->fetch()) {
            
            // Подаваме най-малката опаковка в която е експедиран продукта
            $push = true;
            $index = $dRec->productId;
            $shipped = $aggregator->get('shippedPacks');
            if ($shipped && isset($shipped[$index])) {
                if ($shipped[$index]->inPack < $dRec->quantityInPack) {
                    $push = false;
                }
            }
            
            // Ако ще обновяваме информацията за опаковката
            if ($push) {
                $arr = (object) array('packagingId' => $dRec->packagingId, 'inPack' => $dRec->quantityInPack);
                $aggregator->push('shippedPacks', $arr, $index);
            }
            
            $vat = cat_Products::getVat($dRec->productId);
            if ($rec->chargeVat == 'yes' || $rec->chargeVat == 'separate') {
                $dRec->packPrice += $dRec->packPrice * $vat;
            }
            
            $aggregator->pushToArray('productVatPrices', $dRec->packPrice, $index);
        }
    }
    
    
    /**
     * Преди запис на документ
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        if (empty($rec->originId)) {
            $rec->originId = doc_Threads::getFirstContainerId($rec->threadId);
        }
    }
    
    
    /**
     * Информация за логистичните данни
     *
     * @param mixed $rec - ид или запис на документ
     *
     * @return array $data - логистичните данни
     *
     *		string(2)     ['fromCountry']  - международното име на английски на държавата за натоварване
     * 		string|NULL   ['fromPCode']    - пощенски код на мястото за натоварване
     * 		string|NULL   ['fromPlace']    - град за натоварване
     * 		string|NULL   ['fromAddress']  - адрес за натоварване
     *  	string|NULL   ['fromCompany']  - фирма
     *   	string|NULL   ['fromPerson']   - лице
     * 		datetime|NULL ['loadingTime']  - дата на натоварване
     * 		string(2)     ['toCountry']    - международното име на английски на държавата за разтоварване
     * 		string|NULL   ['toPCode']      - пощенски код на мястото за разтоварване
     * 		string|NULL   ['toPlace']      - град за разтоварване
     *  	string|NULL   ['toAddress']    - адрес за разтоварване
     *   	string|NULL   ['toCompany']    - фирма
     *   	string|NULL   ['toPerson']     - лице
     * 		datetime|NULL ['deliveryTime'] - дата на разтоварване
     * 		text|NULL 	  ['conditions']   - други условия
     * 		varchar|NULL  ['ourReff']      - наш реф
     * 		double|NULL   ['totalWeight']  - общо тегло
     * 		double|NULL   ['totalVolume']  - общ обем
     */
    public function getLogisticData($rec)
    {
        $rec = $this->fetchRec($rec);
        $ownCompany = crm_Companies::fetchOurCompany();
        $ownCountryId = $ownCompany->country;
        
        if ($locationId = store_Stores::fetchField($rec->storeId, 'locationId')) {
            $storeLocation = crm_Locations::fetch($locationId);
            $ownCountryId = $storeLocation->countryId;
        }
        
        $contragentData = doc_Folders::getContragentData($rec->folderId);
        $contragentCountryId = $contragentData->countryId;
        
        if (isset($rec->locationId)) {
            $contragentLocation = crm_Locations::fetch($rec->locationId);
            $contragentCountryId = $contragentLocation->countryId;
        }
        
        $ownPart = ($this instanceof store_ShipmentOrders) ? 'from' : 'to';
        $contrPart = ($this instanceof store_ShipmentOrders) ? 'to' : 'from';
        
        // Подготвяне на данните за разтоварване
        $res = array();
        $res["{$ownPart}Country"] = drdata_Countries::fetchField($ownCountryId, 'commonName');
        
        if (isset($storeLocation)) {
            $res["{$ownPart}PCode"] = !empty($storeLocation->pCode) ? $storeLocation->pCode : null;
            $res["{$ownPart}Place"] = !empty($storeLocation->place) ? $storeLocation->place : null;
            $res["{$ownPart}Address"] = !empty($storeLocation->address) ? $storeLocation->address : null;
            $res["{$ownPart}Person"] = !empty($storeLocation->mol) ? $storeLocation->mol : null;
        } else {
            $res["{$ownPart}PCode"] = !empty($ownCompany->pCode) ? $ownCompany->pCode : null;
            $res["{$ownPart}Place"] = !empty($ownCompany->place) ? $ownCompany->place : null;
            $res["{$ownPart}Address"] = !empty($ownCompany->address) ? $ownCompany->address : null;
        }
        
        $res["{$ownPart}Company"] = $ownCompany->name;
        $toPersonId = ($rec->activatedBy) ? $rec->activatedBy : $rec->createdBy;
        $res["{$ownPart}Person"] = ($res["{$ownPart}Person"]) ? $res["{$ownPart}Person"] : core_Users::fetchField($toPersonId, 'names');
        
        // Подготвяне на данните за натоварване
        $res["{$contrPart}Country"] = drdata_Countries::fetchField($contragentCountryId, 'commonName');
        $res["{$contrPart}Company"] = $contragentData->company;
        if (isset($rec->locationId)) {
            $res["{$contrPart}PCode"] = !empty($contragentLocation->pCode) ? $contragentLocation->pCode : null;
            $res["{$contrPart}Place"] = !empty($contragentLocation->place) ? $contragentLocation->place : null;
            $res["{$contrPart}Address"] = !empty($contragentLocation->address) ? $contragentLocation->address : null;
            $res["{$contrPart}Person"] = !empty($contragentLocation->mol) ? $contragentLocation->mol : null;
        } else {
            $res["{$contrPart}PCode"] = !empty($contragentData->pCode) ? $contragentData->pCode : null;
            $res["{$contrPart}Place"] = !empty($contragentData->place) ? $contragentData->place : null;
            $res["{$contrPart}Address"] = !empty($contragentData->address) ? $contragentData->address : null;
            $res["{$contrPart}Person"] = !empty($contragentData->person) ? $contragentData->person : null;
        }
        
        $res['deliveryTime'] = (!empty($rec->deliveryTime)) ? $rec->deliveryTime : $rec->valior . ' ' . bgerp_Setup::get('START_OF_WORKING_DAY');
        $res['ourReff'] = '#' . $this->getHandle($rec);
        
        $res['totalWeight'] = isset($rec->weightInput) ? $rec->weightInput : $rec->weight;
        $res['totalVolume'] = isset($rec->volumeInput) ? $rec->volumeInput : $rec->volume;
        
        return $res;
    }
    
    
    /**
     * Артикули които да се заредят във фактурата/проформата, когато е създадена от
     * определен документ
     *
     * @param mixed               $id     - ид или запис на документа
     * @param deals_InvoiceMaster $forMvc - клас наследник на deals_InvoiceMaster в който ще наливаме детайлите
     *
     * @return array $details - масив с артикули готови за запис
     *               o productId      - ид на артикул
     *               o packagingId    - ид на опаковка/основна мярка
     *               o quantity       - количество опаковка
     *               o quantityInPack - количество в опаковката
     *               o discount       - отстъпка
     *               o price          - цена за единица от основната мярка
     */
    public function getDetailsFromSource($id, deals_InvoiceMaster $forMvc)
    {
        $details = array();
        $rec = static::fetchRec($id);
        
        $Detail = cls::get($this->mainDetail);
        $query = $Detail->getQuery();
        $query->where("#{$Detail->masterKey} = {$rec->id}");
        
        while ($dRec = $query->fetch()) {
            $dRec->quantity /= $dRec->quantityInPack;
            if (!($forMvc instanceof sales_Proformas)) {
                $dRec->price -= $dRec->price * $dRec->discount;
                unset($dRec->discount);
            }
            unset($dRec->id);
            unset($dRec->shipmentId);
            unset($dRec->createdOn);
            unset($dRec->createdBy);
            $details[] = $dRec;
        }
        
        return $details;
    }
    
    
    /**
     * Информацията на документа, за показване в транспортната линия
     *
     * @param mixed $id
     *
     * @return array
     *               ['baseAmount'] double|NULL - сумата за инкасиране във базова валута
     *               ['amount']     double|NULL - сумата за инкасиране във валутата на документа
     *               ['currencyId'] string|NULL - валутата на документа
     *               ['notes']      string|NULL - забележки за транспортната линия
     *               ['stores']     array       - склад(ове) в документа
     *               ['weight']     double|NULL - общо тегло на стоките в документа
     *               ['volume']     double|NULL - общ обем на стоките в документа
     *               ['transportUnits'] array   - използваните ЛЕ в документа, в формата ле -> к-во
     *               [transUnitId] => quantity
     */
    public function getTransportLineInfo_($rec)
    {
        $rec = static::fetchRec($rec);
        $res = array('baseAmount' => null, 'amount' => null, 'currencyId' => null, 'notes' => $rec->lineNotes);
        $res['stores'] = array($rec->storeId);
        
        $contragentClass = cls::get($rec->contragentClassId);
        $contragentRec = $contragentClass->fetch($rec->contragentId);
        $contragentTitle = $contragentClass->getVerbal($contragentRec, 'name');
        
        $oldRow = $this->recToVerbal($rec, 'contragentAddress,-list');
        
        $contragentClass = cls::get($rec->contragentClassId);
        $contragentRec = $contragentClass->fetch($rec->contragentId);
        $contragentTitle = $contragentClass->getVerbal($contragentRec, 'name');
        
        $address = ($rec->locationId) ? crm_Locations::getAddress($rec->locationId) : $oldRow->contragentAddress;
        $address = str_replace('<br>', ',', $address);
        $address = "{$contragentTitle}, {$address}";
        $res['address'] = $address;
        
        $amount = null;
        $firstDoc = doc_Threads::getFirstDocument($rec->threadId);
        if ($firstDoc->getInstance()->getField('#paymentMethodId', false)) {
            $paymentMethodId = $firstDoc->fetchField('paymentMethodId');
            if (cond_PaymentMethods::isCOD($paymentMethodId)) {
                $amount = currency_Currencies::round($rec->amountDelivered / $rec->currencyRate, $rec->currencyId);
            }
        }
        
        if ($amount) {
            $res['baseAmount'] = currency_Currencies::round($rec->amountDelivered, $rec->currencyId);
            $res['amount'] = $amount;
            $res['currencyId'] = $rec->currencyId;
        }
        
        return $res;
    }
}
