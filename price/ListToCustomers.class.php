<?php



/**
 * Правилата за ценоразписите за продуктите от каталога
 *
 *
 * @category  bgerp
 * @package   price
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Правила за ценоразписи
 */
class price_ListToCustomers extends core_Detail
{
    
    /**
     * Заглавие
     */
    var $title = 'Ценови политики';
    
    
    /**
     * Заглавие
     */
    var $singleTitle = 'Ценова политика';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, price_Wrapper';
                    
    
    /**
     * Интерфейс за ценова политика
     */
    var $interfaces = 'price_PolicyIntf';


    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'listId, cClass, cId, validFrom, createdBy, createdOn';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'id';
    
    
    /**
     * Кой може да го прочете?
     */
    var $canRead = 'user';
    
    
    /**
     * Кой може да го промени?
     */
    var $canEdit = 'user';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'user';
    
        
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'user';
    

    /**
     * Поле - ключ към мастера
     */
    var $masterKey = 'cId';
    

    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('listId', 'key(mvc=price_Lists,select=title)', 'caption=Политика');
        $this->FLD('cClass', 'class(select=title)', 'caption=Клиент->Клас,input=hidden,silent');
        $this->FLD('cId', 'int', 'caption=Клиент->Обект');
        $this->FLD('validFrom', 'datetime', 'caption=В сила от');
    }

    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        if($form->isSubmitted()) {
            
            $rec = $form->rec;

            $now = dt::verbal2mysql();

            if(!$rec->validFrom) {
                $rec->validFrom = $now;
            }

            if($rec->validFrom < $now) {
                $form->setError('validFrom', 'Ценоразписа не може да се задава с минала дата');
            }

            if($rec->validFrom && !$form->gotErrors() && $rec->validFrom > $now) {
                Mode::setPermanent('PRICE_VALID_FROM', $rec->validFrom);
            }
        }
    }


    /**
     * Подготвя формата за въвеждане на ценови правила за клиент
     */
    public static function on_AfterPrepareEditForm($mvc, $res, $data)
    {
        $rec = $data->form->rec;

        if(!$rec->id) {
            $rec->validFrom = Mode::get('PRICE_VALID_FROM');
        }

        $rec->listId = self::getListForCustomer($rec->cClass, $rec->cId);
  
        $data->form->toolbar->addBtn('Нови правила', array('price_Lists', 'add', 'cClass' => $rec->cClass , 'cId' => $rec->cId), 
            NULL, 'order=10.00015,ef_icon=img/16/page_white_star.png');
    }


    public static function on_AfterPrepareDetailQuery($mvc, $data)
    {
        $cClassId = core_Classes::getId($mvc->Master);
        
        $data->query->where("#cClass = {$cClassId}");

        $data->query->orderBy("#validFrom,#id", "DESC");
    }

    
    public function getMasterMvc_($rec)
    {
        $masterMvc = cls::get($rec->cClass);
 
        return $masterMvc;      
    }
    
    
    public function getMasterKey_($rec)
    {
        return 'cId';      
    }
    
    
    public static function on_AfterGetMasters($mvc, &$masters, $rec)
    {
        if (empty($masters)) {
            $masters = array();
        }
        
        $masters['cId']    = cls::get($rec->cClass);
        $masters['listId'] = cls::get('price_Lists');
    }
    

    /**
     * След подготовка на лентата с инструменти за табличния изглед
     */
    function on_AfterPrepareListToolbar($mvc, $data)
    {
        if (!empty($data->toolbar->buttons['btnAdd'])) {
            $data->toolbar->removeBtn('*');
            $masterClassId = core_Classes::getId($this->Master);
            $data->addUrl = array($mvc, 'add', 'cClass' => $masterClassId, 'cId' => $data->masterId, 'ret_url' => TRUE);
        }
    }


    public static function on_AfterRenderDetail($mvc, &$tpl, $data)
    {
        $wrapTpl = new ET(getFileContent('crm/tpl/ContragentDetail.shtml'));
        $wrapTpl->append($mvc->title, 'title');
        $wrapTpl->append($tpl, 'content');
        $wrapTpl->replace(get_class($mvc), 'DetailName');
    
        $tpl = $wrapTpl;
        
        if ($data->addUrl) {
            $addBtn = ht::createLink("<img src=" . sbf('img/16/add.png') . " valign=bottom style='margin-left:5px;'>", $data->addUrl);
            $tpl->append($addBtn, 'title');
        }
    }


    /**
     * Връща актуалния към посочената дата набор от ценови правила за посочения клиент
     */
    static function getValidRec($customerClassId, $customerId, $datetime = NULL)
    { 
        $now = dt::verbal2mysql();

        if(!$datetime) {
            $datetime = $now;
        }

        $query = self::getQuery();
        $query->where("#cClass = {$customerClassId} AND #cId = {$customerId}");
        $query->where("#validFrom <= '{$datetime}'");
        $query->limit(1);
        $query->orderBy("#validFrom,#id", 'DESC');
        $lRec = $query->fetch();
 
        return $lRec;
    }


    /**
     * Задава ценова политика за определен клиент
     */
    static function setPolicyTocustomer($policyId, $cClass, $cId, $datetime = NULL)
    {
        if(!$datetime) {
            $datetime = dt::verbal2mysql();
        }

        $rec = new stdClass();
        $rec->cClass = $cClass;
        $rec->cId   = $cId;
        $rec->validFrom = $datetime;
        $rec->listId = $policyId;
 
        self::save($rec);
    }


    
    
    public static function preparePricelists($data)
    { 
        static::prepareDetail($data);

        $now = dt::verbal2mysql();

        $cClassId = core_Classes::getId($data->masterMvc );
        
        $validRec = self::getValidRec($cClassId, $data->masterId, $now);
       
        if(count($data->rows)) {
            foreach($data->rows as $id => &$row) {
                $rec = $data->recs[$id];
                if($rec->validFrom > $now) {
                    $state = 'draft';
                } elseif($validRec->id == $rec->id) {
                    $state = 'active';
                } else {
                    $state = 'closed';
                }
                $data->rows[$id]->ROW_ATTR['class'] = "state-{$state}";

                if(price_Lists::haveRightFor('single', $rec)) {
                    $row->listId = ht::createLink($row->listId, array('price_Lists', 'single', $rec->listId));
                }
            }
        }

        $data->TabCaption = 'Цени';

    }


    /**
     *
     */
    public function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec)
    {
        if($rec->validFrom && ($action == 'edit' || $action == 'delete')) {
            if($rec->validFrom <= dt::verbal2mysql()) {
                $requiredRoles = 'no_one';
            }
        }
    }


    
    /**
     *
     */
    public function renderPricelists($data)
    {
        // Премахваме контрагента - в случая той е фиксиран и вече е показан 
        unset($data->listFields[$this->masterKey]);
        unset($data->listFields['cClass']);
        
        return static::renderDetail($data);
    }

    
    /**
     * Премахва кеша за интервалите от време
     */
    public static function on_AfterSave($mvc, &$id, &$rec, $fields = NULL)
    {
        price_History::removeTimeline();
    }



    /****************************************************************************************************
     *                                                                                                  *
     *    И Н Т Е Р Ф Е Й С   `price_PolicyIntf`                                                        *
     *                                                                                                  *
     ***************************************************************************************************/
    
    /**
     * Връща продуктие, които могат да се продават на посочения клиент, 
     * съгласно имплементиращата този интерфейс ценова политика
     *
     * @return array() - масив с опции, подходящ за setOptions на форма
     */
    public function getProducts($customerClass, $customerId, $datetime = NULL)
    {
        $products = price_GroupOfProducts::getAllProducts($datetime);

        if(count($products)) {
            foreach($products as $productId => $groupId) {
                 $price = self::getPriceInfo($customerClass, $customerId, $productId, NULL, NULL, $datetime);
                 if(!$price) {
                     unset($products[$productId]);
                 }
            }
            
            return $products;
        }
    }


    /**
     * Връща валидните ценови правила за посочения клиент
     */
    static function getListForCustomer($customerClass, $customerId, $datetime = NULL)
    {
        static::canonizeTime($datetime);
    	
    	$validRec = self::getValidRec($customerClass, $customerId, $datetime);

        if($validRec) {
            $listId   = $validRec->listId;
        } else {
            $conf = core_Packs::getConfig('price');
            $listId = $conf->PRICE_LIST_CATALOG;
        }
        
        return $listId;
    }
    
    
    /**
     * Връща цената за посочения продукт към посочения клиент на посочената дата
     * 
     * @return object
     * $rec->price  - цена
     * $rec->discount - отстъпка
     */
    public function getPriceInfo($customerClass, $customerId, $productId, $packagingId = NULL, $quantity = NULL, $datetime = NULL)
    {
        $listId = self::getListForCustomer($customerClass, $customerId, $datetime);
		
        $rec = new stdClass();

        $rec->price = price_ListRules::getPrice($listId, $productId, $packagingId, $datetime);

        return $rec;
    }
    
	
    /**
     * Помощна функция, добавяща 23:59:59 ако е зададена дата без час
     */
	public static function canonizeTime(&$datetime)
	{
		if(!$datetime) {
	            $datetime = dt::verbal2mysql();
	        } else { 
	            if(strlen($datetime) == 10) {
	                list($d, $t) = explode(' ', dt::verbal2mysql());
	                if($datetime == $d) {
	                    $datetime = dt::verbal2mysql();
	                } else {
	                    $datetime .= ' 23:59:59';
	                }
	            }
	  		}
	}
    
    
    /**
     * Заглавие на ценоразписа за конкретен клиент
     *
     * @see price_PolicyIntf
     * @param mixed $customerClass
     * @param int $customerId
     * @return string
     */
    public function getPolicyTitle($customerClass, $customerId)
    { 
        $listId = self::getListForCustomer($customerClass, $customerId, $datetime);

        if($listId) {
            $lRec = price_Lists::fetch($listId); 
            $title = price_Lists::getVerbal($lRec, 'title');

            return $title;
        }
    }
    
    
	/**
     * Връща мениджъра на продуктите (@see cat_Products)
     * @return core_Classes $class - инстанция на мениджъра
     */
    public function getProductMan()
    {
        return cls::get('cat_Products');
    }
}