<?php


/**
 * Документ за наследяване от касовите ордери
 *
 *
 * @category  bgerp
 * @package   cash
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
abstract class cash_Document extends deals_PaymentDocument
{
    /**
     * Флаг, който указва, че документа е партньорски
     */
    public $visibleForPartners = true;
    
    
    /**
     * Дали сумата е във валута (различна от основната)
     *
     * @see acc_plg_DocumentSummary
     */
    public $amountIsInNotInBaseCurrency = true;
    
    
    /**
     * Неща, подлежащи на начално зареждане
     */
    public $loadList = 'plg_RowTools2, cash_Wrapper, plg_Sorting,deals_plg_SaveValiorOnActivation, acc_plg_Contable,
                     plg_Clone,doc_DocumentPlg, plg_Printing,deals_plg_SelectInvoice,acc_plg_DocumentSummary,
                     plg_Search,doc_plg_MultiPrint, bgerp_plg_Blank, doc_plg_HidePrices,
                     doc_EmailCreatePlg, cond_plg_DefaultValues, doc_SharablePlg,deals_plg_SetTermDate';
    
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amount,amountVerbal';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'termDate=Очаквано,valior=Вальор, title=Документ, reason, fromContainerId, folderId, currencyId=Валута, amount,state, createdOn, createdBy';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, cash';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo, cash';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'cash, ceo';
    
    
    /**
     * Кой може да създава?
     */
    public $canAdd = 'cash, ceo, purchase, sales';
    
    
    /**
     * Кой може да избира ф-ра по документа?
     */
    public $canSelectinvoice = 'cash, ceo, purchase, sales, acc';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'cash, ceo, purchase, sales';
    
    
    /**
     * Кой може да редактира?
     */
    public $canEdit = 'cash, ceo, purchase, sales';
    
    
    /**
     * Дата на очакване
     */
    public $termDateFld = 'termDate';
    
    
    /**
     * Кой може да го контира?
     */
    public $canConto = 'cash, ceo';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, contragentName, reason';
    
    
    /**
     * Параметри за принтиране
     */
    public $printParams = array(array('Оригинал'), array('Копие'));
    
    
    /**
     * Стратегии за дефолт стойностти
     */
    public static $defaultStrategies = array('depositor' => 'lastDocUser|lastDoc',);
    
    
    /**
     * Основна сч. сметка
     */
    public static $baseAccountSysId = '501';
    
    
    /**
     * Кое поле отговаря на броилия парите
     */
    protected $personDocumentField;
    
    
    /**
     * До потребители с кои роли може да се споделя документа
     *
     * @var string
     *
     * @see doc_SharablePlg
     */
    public $shareUserRoles = 'ceo, cash';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, termDate, valior, modifiedOn';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'termDate,valior';
    
    
    /**
     * Добавяне на дефолтни полета
     *
     * @param core_Mvc $mvc
     *
     * @return void
     */
    protected function getFields(core_Mvc &$mvc)
    {
        $mvc->FLD('operationSysId', 'varchar', 'caption=Операция,mandatory');
        $mvc->FLD('amountDeal', 'double(decimals=2,max=2000000000,min=0)', 'caption=Платени,mandatory,silent');
        $mvc->FLD('dealCurrencyId', 'key(mvc=currency_Currencies, select=code)', 'input=hidden');
        $mvc->FLD('reason', 'richtext(rows=2, bucket=Notes)', 'caption=Основание');
        $mvc->FLD('termDate', 'date(format=d.m.Y)', 'caption=Очаквано на,silent');
        $mvc->FLD('peroCase', 'key(mvc=cash_Cases, select=name,allowEmpty)', 'caption=Каса,removeAndRefreshForm=currencyId|amount,silent');
        $mvc->FLD('contragentName', 'varchar(255)', 'caption=Контрагент->Вносител,mandatory');
        $mvc->FLD('contragentId', 'int', 'input=hidden,notNull');
        $mvc->FLD('contragentClassId', 'key(mvc=core_Classes,select=name)', 'input=hidden,notNull');
        $mvc->FLD('contragentAdress', 'varchar(255)', 'input=hidden');
        $mvc->FLD('contragentPlace', 'varchar(255)', 'input=hidden');
        $mvc->FLD('contragentPcode', 'varchar(255)', 'input=hidden');
        $mvc->FLD('contragentCountry', 'varchar(255)', 'input=hidden');
        $mvc->FLD('creditAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
        $mvc->FLD('debitAccount', 'customKey(mvc=acc_Accounts,key=systemId,select=systemId)', 'input=none');
        $mvc->FLD('currencyId', 'key(mvc=currency_Currencies, select=code)', 'caption=Валута (и сума) на плащането->Валута,silent,removeAndRefreshForm=rate|amount');
        $mvc->FLD('amount', 'double(decimals=2,max=2000000000,min=0)', 'caption=Сума,summary=amount,input=hidden');
        $mvc->FLD('rate', 'double(decimals=5)', 'caption=Валута (и сума) на плащането->Курс,input=none');
        $mvc->FLD('valior', 'date(format=d.m.Y)', 'caption=Допълнително->Вальор,autohide');
        $mvc->FLD('state', 'enum(draft=Чернова, active=Контиран, rejected=Оттеглен,stopped=Спряно, pending=Заявка)', 'caption=Статус, input=none');
        $mvc->FLD('isReverse', 'enum(no,yes)', 'input=none,notNull,value=no');
    }
    
    
    /**
     *  Обработка на формата за редакция и добавяне
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $folderId = $data->form->rec->folderId;
        $form = &$data->form;
        
        $contragentId = doc_Folders::fetchCoverId($folderId);
        $contragentClassId = doc_Folders::fetchField($folderId, 'coverClass');
        $form->setDefault('contragentId', $contragentId);
        $form->setDefault('contragentClassId', $contragentClassId);
        
        expect($origin = $mvc->getOrigin($form->rec));
        $dealInfo = $origin->getAggregateDealInfo();
        $pOperations = $dealInfo->get('allowedPaymentOperations');
        
        $options = $mvc->getOperations($pOperations);
        expect(count($options));
        
        $cId = currency_Currencies::getIdByCode($dealInfo->get('currency'));
        $form->setDefault('dealCurrencyId', $cId);
        $form->setDefault('currencyId', $cId);
        
        if ($expectedPayment = $dealInfo->get('expectedPayment')) {
            if (isset($form->rec->originId, $form->rec->amountDeal)) {
                $expectedPayment = $form->rec->amountDeal * $dealInfo->get('rate');
            }
            
            $amount = core_Math::roundNumber($expectedPayment / $dealInfo->get('rate'));
            
            if ($form->rec->currencyId == $form->rec->dealCurrencyId) {
                $form->setDefault('amount', $amount);
            }
        }
        
        // Ако потребителя има права, логва се тихо
        if ($caseId = $dealInfo->get('caseId')) {
            cash_Cases::selectCurrent($caseId);
        }
        
        $form->setOptions('operationSysId', $options);
        $defaultOperation = $dealInfo->get('defaultCaseOperation');
        
        if (isset($defaultOperation) && array_key_exists($defaultOperation, $options)) {
            $form->setDefault('operationSysId', $defaultOperation);
            
            $dAmount = currency_Currencies::round($amount, $dealInfo->get('currency'));
            if ($dAmount != 0) {
                $form->setDefault('amountDeal', $dAmount);
            }
        }
        
        // Поставяме стойности по подразбиране
        if (empty($form->rec->id) && $form->cmd != 'refresh') {
            $form->setDefault('peroCase', cash_Cases::getCurrent('id', false));
            $form->setDefault('peroCase', $caseId);
        }
        
        $cData = cls::get($contragentClassId)->getContragentData($contragentId);
        $form->setReadOnly('contragentName', ($cData->person) ? $cData->person : $cData->company);
        
        $form->setField('amountDeal', array('unit' => "|*{$dealInfo->get('currency')} |по сделката|*"));
        
        if ($contragentClassId == crm_Companies::getClassId()) {
            $form->setSuggestions($mvc->personDocumentField, crm_Companies::getPersonOptions($contragentId, false));
        }
    }
    
    
    /**
     * Проверка и валидиране на формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = &$form->rec;
        
        if ($form->rec->currencyId != $form->rec->dealCurrencyId) {
            $form->setField('amount', 'input');
        }
        
        if (!isset($form->rec->peroCase)) {
            $form->setField('currencyId', 'input=hidden');
        }
        
        if ($form->isSubmitted()) {
            if (!isset($rec->amount) && $rec->currencyId != $rec->dealCurrencyId) {
                $form->setField('amount', 'input');
                $form->setError('amount', 'Когато плащането е във валута - различна от тази на сделката, сумата трябва да е попълнена');
                
                return;
            }
            
            $origin = $mvc->getOrigin($form->rec);
            $dealInfo = $origin->getAggregateDealInfo();
            
            $operation = $dealInfo->allowedPaymentOperations[$rec->operationSysId];
            $debitAcc = empty($operation['reverse']) ? $operation['debit'] : $operation['credit'];
            $creditAcc = empty($operation['reverse']) ? $operation['credit'] : $operation['debit'];
            
            $rec->debitAccount = $debitAcc;
            $rec->creditAccount = $creditAcc;
            $rec->isReverse = empty($operation['reverse']) ? 'no' : 'yes';
            
            $contragentData = doc_Folders::getContragentData($rec->folderId);
            $rec->contragentCountry = $contragentData->country;
            $rec->contragentPcode = $contragentData->pCode;
            $rec->contragentPlace = $contragentData->place;
            $rec->contragentAdress = $contragentData->address;
            
            $currencyCode = currency_Currencies::getCodeById($rec->currencyId);
            $rec->rate = currency_CurrencyRates::getRate($rec->valior, $currencyCode, null);
            
            if ($rec->currencyId == $rec->dealCurrencyId) {
                $rec->amount = $rec->amountDeal;
            }
            
            $dealCurrencyCode = currency_Currencies::getCodeById($rec->dealCurrencyId);
            
            if ($msg = currency_CurrencyRates::checkAmounts($rec->amount, $rec->amountDeal, $rec->valior, $currencyCode, $dealCurrencyCode)) {
                $form->setError('amountDeal', $msg);
            }
            
            $mvc->invoke('AfterSubmitInputEditForm', array($form));
        }
    }
    
    
    /**
     *  Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме към формата за търсене търсене по Каса
        cash_Cases::prepareCaseFilter($data, array('peroCase'));
    }
    
    
    /**
     * Вкарваме css файл за единичния изглед
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        $tpl->push('cash/tpl/styles.css', 'CSS');
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        // Ако не е избрана каса, показваме бутона за контиране но с грешка
        if (($rec->state == 'draft' || $rec->state == 'pending') && !isset($rec->peroCase) && $mvc->haveRightFor('conto')) {
            $data->toolbar->addBtn('Контиране', array(), array('id' => 'btnConto', 'error' => 'Документът не може да бъде контиран, докато няма посочена каса|*!'), 'ef_icon = img/16/tick-circle-frame.png,title=Контиране на документа');
        }
    }
    
    
    /**
     * Подготовка на бутоните на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditToolbar($mvc, &$res, $data)
    {
        // Документа не може да се създава  в нова нишка, ако е възоснова на друг
        if (!empty($data->form->toolbar->buttons['btnNewThread'])) {
            $data->form->toolbar->removeBtn('btnNewThread');
        }
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return false;
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
        $docState = $firstDoc->fetchField('state');
        
        if (!empty($firstDoc) && $firstDoc->haveInterface('bgerp_DealAggregatorIntf') && $docState == 'active') {
            
            // Ако няма позволени операции за документа не може да се създава
            $operations = $firstDoc->getPaymentOperations();
            $options = static::getOperations($operations);
            
            return count($options) ? true : false;
        }
        
        return false;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @see email_DocumentIntf
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string - тялото на имейла
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $handle = $this->getHandle($id);
        $title = mb_strtolower($this->singleTitle);
        $tpl = new ET(tr("Моля запознайте се с нашия {$title}") . ': #[#handle#]');
        $tpl->append($handle, 'handle');
        
        return $tpl->getContent();
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
        $rec = self::fetchRec($id);
        $aggregator->setIfNot('caseId', $rec->peroCase);
    }
    
    
    /**
     *  Обработки по вербалното представяне на данните
     */
    protected static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->title = $mvc->getLink($rec->id, 0);
        
        if ($fields['-single']) {
            if ($rec->dealCurrencyId != $rec->currencyId) {
                $baseCurrencyId = acc_Periods::getBaseCurrencyId($rec->valior);
                
                if ($rec->dealCurrencyId == $baseCurrencyId) {
                    $rate = $rec->amountDeal / $rec->amount;
                    $rateFromCurrencyId = $rec->dealCurrencyId;
                    $rateToCurrencyId = $rec->currencyId;
                } else {
                    @$rate = $rec->amount / $rec->amountDeal;
                    $rateFromCurrencyId = $rec->currencyId;
                    $rateToCurrencyId = $rec->dealCurrencyId;
                }
                $row->rate = cls::get('type_Double', array('params' => array('decimals' => 5)))->toVerbal($rate);
                $row->rateFromCurrencyId = currency_Currencies::getCodeById($rateFromCurrencyId);
                $row->rateToCurrencyId = currency_Currencies::getCodeById($rateToCurrencyId);
            } else {
                unset($row->dealCurrencyId);
                unset($row->amountDeal);
                unset($row->rate);
            }
            
            $SpellNumber = cls::get('core_SpellNumber');
            $currecyCode = currency_Currencies::getCodeById($rec->currencyId);
            $amountVerbal = $SpellNumber->asCurrency($rec->amount, 'bg', false, $currecyCode);
            $row->amountVerbal = str::mbUcfirst($amountVerbal);
            
            // Вземаме данните за нашата фирма
            $headerInfo = deals_Helper::getDocumentHeaderInfo($rec->contragentClassId, $rec->contragentId, $row->contragentName);
            foreach (array('MyCompany', 'MyAddress', 'contragentName', 'contragentAddress') as $fld) {
                $row->{$fld} = $headerInfo[$fld];
            }
            
            // Кой е съставителя на документа
            $row->issuer = deals_Helper::getIssuer($rec->createdBy, $rec->activatedBy);
            
            if (isset($rec->peroCase)) {
                $row->peroCase = cash_Cases::getHyperlink($rec->peroCase);
            } else {
                $row->peroCase = tr('Предстои да бъде уточнена');
                $row->peroCase = "<span class='red'><small><i>{$row->peroCase}</i></small></span>";
            }
            
            if ($origin = $mvc->getOrigin($rec)) {
                $options = $origin->allowedPaymentOperations;
                $row->operationSysId = $options[$rec->operationSysId]['title'];
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($requiredRoles == 'no_one') {
            
            return;
        }
        if (!deals_Helper::canSelectObjectInDocument($action, $rec, 'cash_Cases', 'peroCase')) {
            $requiredRoles = 'no_one';
        }
    }
}
