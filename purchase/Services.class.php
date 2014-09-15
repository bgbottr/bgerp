<?php
/**
 * Клас 'purchase_Services'
 *
 * Мениджър на Приемателен протокол
 *
 *
 * @category  bgerp
 * @package   purchase
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class purchase_Services extends acc_ServiceMaster
{
    /**
     * Заглавие
     */
    public $title = 'Приемателни протоколи';


    /**
     * Абревиатура
     */
    public $abbr = 'Pps';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'doc_DocumentIntf, email_DocumentIntf, doc_ContragentDataIntf, bgerp_DealIntf,acc_TransactionSourceIntf=purchase_transaction_Service';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, purchase_Wrapper, plg_Sorting, plg_Printing, acc_plg_Contable,
                    doc_DocumentPlg, plg_ExportCsv, acc_plg_DocumentSummary,
					doc_EmailCreatePlg, bgerp_plg_Blank, doc_plg_TplManager, doc_plg_HidePrices,
                    plg_LastUsedKeys, plg_Search';

    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, purchase';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	public $canList = 'ceo, purchase';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	public $canSingle = 'ceo, purchase';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, purchase';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, purchase';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canConto = 'ceo, purchase';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, valior, folderId, amountDelivered, amountDeliveredVat, createdOn, createdBy';


    /**
     * Детайла, на модела
     */
    public $details = 'purchase_ServicesDetails';
    

    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Приемателен протокол';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'purchase/tpl/SingleLayoutServices.shtml';

   
    /**
     * Групиране на документите
     */
    public $newBtnGroup = "4.5|Логистика";
   
    
    /**
     * Полета свързани с цени
     */
    public $priceFields = 'amountDelivered';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/shipment.png';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'valior, contragentClassId, contragentId, locationId, deliveryTime, folderId, id';
    
    
    /**
     * Главен детайл на модела
     */
    public $mainDetail = 'purchase_ServicesDetails';
    
    
    /**
     * Основна операция
     */
    protected static $defOperationSysId = 'buyServices';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        parent::setServiceFields($this);
    }
     
     
	/**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    static function getRecTitle($rec, $escaped = TRUE)
    {
        return tr("|Приемателен протокол за услуги|* №") . $rec->id;
    }
    
    
	/**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
    	$mvc->setTemplates($res);
    }
    
    
	/**
     * Зарежда шаблоните на продажбата в doc_TplManager
     */
    private function setTemplates(&$res)
    {
    	$tplArr[] = array('name' => 'Приемателен протокол за услуги', 
    					  'content' => 'purchase/tpl/SingleLayoutServices.shtml', 'lang' => 'bg', 
    					  'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,weight,volume'));
    	$tplArr[] = array('name' => 'Приемателен протокол за услуги с цени', 
    					  'content' => 'purchase/tpl/SingleLayoutServicesPrices.shtml', 'lang' => 'bg',
    					  'toggleFields' => array('masterFld' => NULL, 'purchase_ServicesDetails' => 'packagingId,packQuantity,packPrice,discount,amount'));
    	
        $res .= doc_TplManager::addOnce($this, $tplArr);
    }
    
    
    /**
     * След изпращане на формата
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	if ($form->isSubmitted()) {
    		$rec = &$form->rec;
    		$dealInfo = static::getOrigin($rec)->getAggregateDealInfo();
    		$operations = $dealInfo->get('allowedShipmentOperations');
    		$operation = $operations[$mvc::$defOperationSysId];
    		$rec->accountId = $operation['credit'];
    		$rec->isReverse = (isset($operation['reverse'])) ? 'yes' : 'no';
    	}
    }
}