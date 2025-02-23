<?php


/**
 * Мениджър за детайл на артикулите в е-магазина
 *
 *
 * @category  bgerp
 * @package   eshop
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class eshop_ProductDetails extends core_Detail
{
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'eshopProductId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'eshop_Wrapper, plg_Created, plg_Modified, plg_SaveAndNew, plg_RowTools2, plg_Select, plg_AlignDecimals2';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'опция';
    
    
    /**
     * Заглавие
     */
    public $title = 'Опции на артикулите в е-магазина';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'eshopProductId=Е-артикул,productId,title,packagings=Опаковки/Мерки,state=Състояние,modifiedOn,modifiedBy';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'title';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'eshop,ceo';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'eshop,ceo';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'eshop,ceo';
    
    
    /**
     * Кой може да изтрива?
     */
    public $canDelete = 'eshop,ceo';
    
    
    /**
     * Поле за артикула
     */
    public $productFld = 'productId';
    
    
    /**
     * Поле за забележки
     */
    public $notesFld = 'title';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('eshopProductId', 'key(mvc=eshop_Products,select=name)', 'caption=Е-артикул,mandatory,silent');
        $this->FLD('productId', 'key2(mvc=cat_Products,select=name,allowEmpty,selectSourceArr=price_ListRules::getSellableProducts)', 'caption=Артикул,silent,removeAndRefreshForm=packagings,mandatory');
        $this->FLD('packagings', 'keylist(mvc=cat_UoM,select=name)', 'caption=Опаковки/Мерки,mandatory');
        $this->FLD('title', 'varchar(nullIfEmpty)', 'caption=Заглавие');
        $this->EXT('state', 'cat_Products', 'externalName=state,externalKey=productId');
        
        $this->setDbUnique('eshopProductId,title');
    }
    
    
    /**
     * Преди подготовката на полетата за листовия изглед
     */
    protected static function on_AfterPrepareListFields($mvc, &$res, &$data)
    {
    	if (isset($data->masterMvc)){
    		unset($data->listFields['eshopProductId']);
    	}
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        if (isset($rec->productId)) {
            $productRec = cat_Products::fetch($rec->productId, 'canStore,measureId');
            if ($productRec->canStore == 'yes') {
                $packs = cat_Products::getPacks($rec->productId);
                $form->setSuggestions('packagings', $packs);
                $form->setDefault('packagings', keylist::addKey('', key($packs)));
            } else {
                $form->setDefault('packagings', keylist::addKey('', $productRec->measureId));
                $form->setReadOnly('packagings');
            }
        } else {
            $form->setField('packagings', 'input=none');
        }
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
            $thisDomainId = eshop_Products::getDomainId($rec->eshopProductId);
            if (self::isTheProductAlreadyInTheSameDomain($rec->productId, $thisDomainId, $rec->id)) {
                $form->setError('productId', 'Артикулът е вече добавен в същия домейн');
            }
        }
    }
    
    
    /**
     * Артикулът наличен ли е в подадения домейн
     *
     * @param int      $productId - артикул
     * @param int      $domainId  - домейн
     * @param int|NULL $id        - запис който да се игнорира
     *
     * @return bool - среща ли се артикулът в същия домейн?
     */
    public static function isTheProductAlreadyInTheSameDomain($productId, $domainId, $id = null)
    {
        $domainIds = array();
        $query = self::getQuery();
        $query->where("#productId = {$productId} AND #id != '{$id}'");
        while ($eRec = $query->fetch()) {
            $eproductDomainId = eshop_Products::getDomainId($eRec->eshopProductId);
            $domainIds[$eproductDomainId] = $eproductDomainId;
        }
        
        return array_key_exists($domainId, $domainIds);
    }
    
    
    /**
     * Каква е цената във външната част
     *
     * @param int      $productId
     * @param int      $packagingId
     * @param float    $quantityInPack
     * @param int|NULL $domainId
     *
     * @return NULL|float
     */
    public static function getPublicDisplayPrice($productId, $packagingId = null, $quantityInPack = 1, $domainId = null)
    {
        $res = (object) array('price' => null, 'discount' => null);
        $domainId = (isset($domainId)) ? $domainId : cms_Domains::getPublicDomain()->id;
        $settings = cms_Domains::getSettings($domainId);
        
        // Ценовата политика е от активната папка
        $listId = $settings->listId;
        if ($lastActiveFolder = core_Mode::get('lastActiveContragentFolder')) {
            $Cover = doc_Folders::getCover($lastActiveFolder);
            $listId = price_ListToCustomers::getListForCustomer($Cover->getClassId(), $Cover->that);
        }
        
        // Ако има ценоразпис
        if (isset($listId)) {
            if ($price = price_ListRules::getPrice($listId, $productId, $packagingId)) {
                $priceObject = cls::get('price_ListToCustomers')->getPriceByList($listId, $productId, $packagingId, $quantityInPack);
                
                $price *= $quantityInPack;
                if ($settings->chargeVat == 'yes') {
                    $price *= 1 + cat_Products::getVat($productId);
                }
                $price = currency_CurrencyRates::convertAmount($price, null, null, $settings->currencyId);
                
                $res->price = round($price, 5);
                if (!empty($priceObject->discount)) {
                    $res->discount = $priceObject->discount;
                }
                
                return $res;
            }
        }
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
        if (isset($fields['-list'])) {
        	$row->ROW_ATTR['class'] = "state-{$rec->state}";
        	$row->eshopProductId = eshop_Products::getHyperlink($rec->eshopProductId, TRUE);
        	$row->productId = cat_Products::getHyperlink($rec->productId, TRUE);
        	
            if (!self::getPublicDisplayPrice($rec->productId)) {
                $row->productId = ht::createHint($row->productId, 'Артикулът няма цена и няма да се показва във външната част', 'warning');
            }
        }
    }
    
    
    /**
     * Подготовка на опциите във външната част
     *
     * @param stdClass $data
     *
     * @return void
     */
    public static function prepareExternal(&$data)
    {
        $data->rows = $data->recs = $data->paramListFields = array();
        
        // Добавяне към колонките по една за всеки параметър
        $displayParams = eshop_Products::getParamsToDisplay($data->rec->id);
        foreach ($displayParams as $paramId) {
            $data->paramListFields["param{$paramId}"] = cat_Params::getVerbal($paramId, 'typeExt');
        }
        
        $data->listFields = $data->paramListFields + arr::make('code=Код,productId=Опция,packagingId=Опаковка,quantity=Количество,catalogPrice=Цена,btn=|*&nbsp;');
        $fields = cls::get(get_called_class())->selectFields();
        $fields['-external'] = $fields;
        
        $query = self::getQuery();
        $query->where("#eshopProductId = {$data->rec->id} AND #state = 'active'");
        $query->orderBy('productId');
        $data->optionsProductsCount = $query->count();
        $data->commonParams = eshop_Products::getCommonParams($data->rec->id);
        
        while ($rec = $query->fetch()) {
            $newRec = (object) array('eshopProductId' => $rec->eshopProductId, 'productId' => $rec->productId, 'title' => $rec->title);
            if (!self::getPublicDisplayPrice($rec->productId) || $data->rec->state == 'closed' || $rec->state != 'active') {
                continue;
            }
            $packagins = keylist::toArray($rec->packagings);
            
            // Кои параметри ще се показват
            $params = cat_Products::getParams($rec->productId, null, true);
            $intersect = array_intersect_key($params, $displayParams);
            
            // Всяка от посочените опаковки се разбива във отделни редове
            $i = 1;
            foreach ($packagins as $packagingId) {
                $clone = clone $newRec;
                $clone->first = ($i == 1) ? true : false;
                $clone->packagingId = $packagingId;
                $packRec = cat_products_Packagings::getPack($rec->productId, $packagingId);
                $clone->quantityInPack = (is_object($packRec)) ? $packRec->quantity : 1;
                
                $row = self::getExternalRow($clone);
                foreach ($intersect as $pId => $pVal) {
                    $clone->{"param{$pId}"} = $pVal;
                    $row->{"param{$pId}"} = $pVal;
                }
                
                $data->recs[] = $clone;
                $data->rows[] = $row;
                $i++;
            }
        }
        
        if (count($data->rows)) {
            uasort($data->rows, function ($obj1, $obj2) {
                if ($obj1->orderCode == $obj2->orderCode) {
                    
                    return $obj1->orderPrice > $obj2->orderPrice;
                }
                
                return strnatcmp($obj1->orderCode, $obj2->orderCode);
            });
            
            $prev = null;
            foreach ($data->rows as &$row1) {
                if (isset($prev) && $prev == $row1->productId) {
                    $row1->productId = "<span class='quiet'>{$row1->productId}</span>";
                }
                $prev = strip_tags($row1->productId);
            }
        }
    }
    
    
    /**
     * Външното представяне на артикула
     *
     * @param stdClass $rec
     *
     * @return stdClass $row
     */
    public static function getExternalRow($rec)
    {
        $settings = cms_Domains::getSettings();
        $row = new stdClass();
        $row->productId = (empty($rec->title)) ? cat_Products::getVerbal($rec->productId, 'name') : core_Type::getByName('varchar')->toVerbal($rec->title);
        $fullCode = cat_products::getVerbal($rec->productId, 'code');
        $row->code = substr($fullCode, 0, 10);
        $row->code = "<span title={$fullCode}>{$row->code}</span>";
        
        $row->packagingId = tr(cat_UoM::getShortName($rec->packagingId));
        $minus = ht::createElement('span', array('class' => 'btnDown', 'title' => 'Намаляване на количеството'), '-');
        $plus = ht::createElement('span', array('class' => 'btnUp', 'title' => 'Увеличаване на количеството'), '+');
        $row->quantity = '<span>' . $minus . ht::createTextInput("product{$rec->productId}-{$rec->packagingId}", 1, "class=eshop-product-option option-quantity-input") . $plus . '</span>';

        $catalogPriceInfo = self::getPublicDisplayPrice($rec->productId, $rec->packagingId, $rec->quantityInPack);
        
        $row->catalogPrice = core_Type::getByName('double(smartRound,minDecimals=2)')->toVerbal($catalogPriceInfo->price);
        $row->catalogPrice = "<b>{$row->catalogPrice}</b>";
        $row->orderPrice = $catalogPriceInfo->price;
        $row->orderCode = $fullCode;
        $addUrl = toUrl(array('eshop_Carts', 'addtocart'), 'local');
        
        $row->btn = ht::createFnBtn($settings->addToCartBtn, null, false, array('title' => 'Добавяне в|* ' . mb_strtolower(eshop_Carts::getCartDisplayName()), 'ef_icon' => 'img/16/cart_go.png', 'data-url' => $addUrl, 'data-productid' => $rec->productId, 'data-packagingid' => $rec->packagingId, 'data-eshopproductpd' => $rec->eshopProductId, 'class' => 'eshop-btn', 'rel' => 'nofollow'));
        if($rec->_listView !== true){
            deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        }
        $class = ($rec->_listView === true) ? 'group-row' : '';
        
        $canStore = cat_Products::fetchField($rec->productId, 'canStore');
        if (isset($settings->storeId) && $canStore == 'yes') {
            $quantity = store_Products::getQuantity($rec->productId, $settings->storeId, true);
            if ($quantity < $rec->quantityInPack) {
                $notInStock = !empty($settings->notInStockText) ? $settings->notInStockText : tr(eshop_Setup::get('NOT_IN_STOCK_TEXT'));
                $row->btn = "<span class='{$class} option-not-in-stock'>" . $notInStock . ' </span>';
                $row->quantity = 1;
            }
        }
        
        if (!empty($catalogPriceInfo->discount)) {
            $style = ($rec->_listView === true) ? 'style="display:inline-block;font-weight:normal"' : '';
            
            $row->catalogPrice = "<b class='{$class} eshop-discounted-price'>{$row->catalogPrice}</b>";
            $discountType = type_Set::toArray($settings->discountType);
            $row->catalogPrice .= "<div class='{$class} external-discount' {$style}>";
            if (isset($discountType['amount'])) {
                $amountWithoutDiscount = $catalogPriceInfo->price / (1 - $catalogPriceInfo->discount);
                $discountAmount = core_Type::getByName('double(decimals=2)')->toVerbal($amountWithoutDiscount);
                $row->catalogPrice .= "<div class='{$class} external-discount-amount' {$style}> {$discountAmount}</div>";
            }
            
            if (isset($discountType['amount']) && isset($discountType['percent'])) {
                $row->catalogPrice .= ' / ';
            }
            
            if (isset($discountType['percent'])) {
                $discountPercent = core_Type::getByName('percent(decimals=0)')->toVerbal($catalogPriceInfo->discount);
                $discountPercent = str_replace('&nbsp;', '', $discountPercent);
                $row->catalogPrice .= "<div class='{$class} external-discount-percent' {$style}> (-{$discountPercent})</div>";
            }
            
            $row->catalogPrice .= '</div>';
        }
        
        return $row;
    }
    
    
    /**
     * Рендиране на опциите във външната част
     *
     * @param stdClass $data
     *
     * @return core_ET $tpl
     */
    public static function renderExternal($data)
    {
        $tpl = new core_ET('');
        
        $fieldset = cls::get(get_called_class());
        $fieldset->FNC('code', 'varchar');
        $fieldset->FNC('catalogPrice', 'double');
        $fieldset->FNC('btn', 'varchar', 'tdClass=small-field');
        $fieldset->FNC('packagingId', 'varchar', 'tdClass=centered');
        $fieldset->FLD('quantity', 'varchar', 'tdClass=quantity-input-column small-field');
        
        $table = cls::get('core_TableView', array('mvc' => $fieldset, 'tableClass' => 'optionsTable'));
        
        if ($data->optionsProductsCount == 1) {
            unset($data->listFields['code']);
            unset($data->listFields['productId']);
        }
        
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $data->listFields, $data->paramListFields);
        
        $listFields = &$data->listFields;
        array_walk(array_keys($data->commonParams), function($paramId) use (&$listFields){unset($listFields["param{$paramId}"]);});
        
        $settings = cms_Domains::getSettings();
        if (empty($settings)) {
            unset($data->listFields['btn']);
        }
        
        $tpl->append($table->get($data->rows, $data->listFields));
        
        $colspan = count($data->listFields);
        $cartInfo = tr('Всички цени са в') . " {$settings->currencyId}, " . (($settings->chargeVat == 'yes') ? tr('с ДДС') : tr('без ДДС'));
        $cartInfo = "<tr><td colspan='{$colspan}' class='option-table-info'>{$cartInfo}</td></tr>";
        $tpl->append($cartInfo, 'ROW_AFTER');
        
        $tpl->append(eshop_Products::renderParams($data->commonParams), 'ROW_AFTER');
        
        return $tpl;
    }
    
    
    /**
     * Връща достъпните артикули за избор от домейна
     *
     * @param int|NULL $domainId - домейн или текущия ако не е подаден
     *
     * @return array $options    - възможните артикули
     */
    public static function getAvailableProducts($domainId = null)
    {
        $options = array();
        $groups = eshop_Groups::getByDomain($domainId);
        $groups = array_keys($groups);
        
        $query = self::getQuery();
        $query->EXT('groupId', 'eshop_Products', 'externalName=groupId,externalKey=eshopProductId');
        $query->where("#state = 'active'");
        $query->in('groupId', $groups);
        $query->show('productId,state');
        
        while ($rec = $query->fetch()) {
            
            // Трябва да имат цени по избраната политика
            if (self::getPublicDisplayPrice($rec->productId, $rec->packagingId)) {
                $options[$rec->productId] = cat_Products::getTitleById($rec->productId, false);
            }
        }
        
        return $options;
    }
}
