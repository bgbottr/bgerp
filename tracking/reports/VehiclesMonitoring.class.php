<?php


/**
 * Мениджър на отчети за продадени артикули продукти по групи и търговци
 *
 *
 * @category  bgerp
 * @package   sales
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Продажби » Продадени артикули
 */
class tracking_reports_SoldProductsRep extends frame2_driver_TableData
{
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectDriver = 'ceo, acc, repAll, repAllGlobal, sales';
    
    
    /**
     * Полета за хеширане на таговете
     *
     * @see uiext_Labels
     *
     * @var string
     */
    protected $hashField;
    
    
    /**
     * Кое поле от $data->recs да се следи, ако има нов във новата версия
     *
     * @var string
     */
    protected $newFieldToCheck;
    
    
    /**
     * По-кое поле да се групират листовите данни
     */
    protected $groupByField = 'group';
    
    
    /**
     * Кои полета може да се променят от потребител споделен към справката, но нямащ права за нея
     */
    protected $changeableFields = 'from,to,compare,group,dealers,contragent,crmGroup,articleType';
    
    
    /**
     * Добавя полетата на драйвера към Fieldset
     *
     * @param core_Fieldset $fieldset
     */
    public function addFields(core_Fieldset &$fieldset)
    {
        $fieldset->FLD('compare', 'enum(no=Без, previous=Предходен,month=По месеци, year=Миналогодишен)', 'caption=Сравнение,after=title,refreshForm,single=none,silent');
        $fieldset->FLD('from', 'date', 'caption=От,after=compare,single=none,mandatory');
        $fieldset->FLD('to', 'date', 'caption=До,after=from,single=none,mandatory');
        $fieldset->FLD('firstMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 1,after=compare,single=none,input=none');
        $fieldset->FLD('secondMonth', 'key(mvc=acc_Periods,select=title)', 'caption=Месец 2,after=firstMonth,single=none,input=none');
        $fieldset->FLD('dealers', 'users(rolesForAll=ceo|repAllGlobal, rolesForTeams=ceo|manager|repAll|repAllGlobal)', 'caption=Търговци,single=none,after=to,mandatory');
        $fieldset->FLD('contragent', 'keylist(mvc=doc_Folders,select=title,allowEmpty)', 'caption=Контрагенти->Контрагент,single=none,after=dealers');
        $fieldset->FLD('crmGroup', 'keylist(mvc=crm_Groups,select=name)', 'caption=Контрагенти->Група контрагенти,after=contragent,single=none');
        $fieldset->FLD('group', 'keylist(mvc=cat_Groups,select=name)', 'caption=Артикули->Група артикули,after=crmGroup,single=none');
        $fieldset->FLD('articleType', 'enum(yes=Стандартни,no=Нестандартни,all=Всички)', 'caption=Артикули->Тип артикули,maxRadio=3,columns=3,after=group,single=none');
        
        // $fieldset->FLD('contragent', 'key2(mvc=doc_Folders,select=title,allowEmpty, restrictViewAccess=yes,coverInterface=crm_ContragentAccRegIntf)', 'caption=Контрагент,single=none,after=dealers');
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_Form         $form
     * @param stdClass          $data
     */
    protected static function on_AfterInputEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$form)
    {
        if ($form->isSubmitted()) {
            
            // Проверка на периоди
            if (isset($form->rec->from, $form->rec->to) && ($form->rec->from > $form->rec->to)) {
                $form->setError('from,to', 'Началната дата на периода не може да бъде по-голяма от крайната.');
            }
            
            if (isset($form->rec->compare) && $form->rec->compare == 'year') {
                $toLastYear = dt::addDays(-365, $form->rec->to);
                if ($form->rec->from < $toLastYear) {
                    $form->setError('compare', 'Периода трябва да е по-малък от 365 дни за да сравнявате с "миналогодишен" период.
                                                  За да сравнявате периоди по-големи от 1 година, използвайте сравнение с "предходен" период');
                }
            }
        }
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param stdClass            $data
     */
    protected static function on_AfterPrepareEditForm(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$data)
    {
        $form = $data->form;
        $rec = $form->rec;
        
        if ($rec->compare == 'month') {
            $form->setField('from', 'input=hidden');
            $form->setField('to', 'input=hidden');
            $form->setField('firstMonth', 'input');
            $form->setField('secondMonth', 'input');
        }
        
        $monthSugg = (acc_Periods::fetchByDate(dt::today())->id);
        
        $form->setDefault('firstMonth', $monthSugg);
        
        $form->setDefault('secondMonth', $monthSugg);
        
        $form->setDefault('articleType', 'all');
        
        $form->setDefault('compare', 'no');
        
        $salesQuery = sales_Sales::getQuery();
        
        $salesQuery->EXT('folderTitle', 'doc_Folders', 'externalName=title,externalKey=folderId');
        
        $salesQuery->groupBy('folderId');
        
        $salesQuery->show('folderId, contragentId, folderTitle');
        
        while ($contragent = $salesQuery->fetch()) {
            if (!is_null($contragent->contragentId)) {
                $suggestions[$contragent->folderId] = $contragent->folderTitle;
            }
        }
        
        asort($suggestions);
        
        $form->setSuggestions('contragent', $suggestions);
    }
    
    
    /**
     * Кои записи ще се показват в таблицата
     *
     * @param stdClass $rec
     * @param stdClass $data
     *
     * @return array
     */
    protected function prepareRecs($rec, &$data = null)
    {
        file_put_contents('debug.txt', serialize($rec));
        
        $recs = array();
        
        $query = sales_PrimeCostByDocument::getQuery();
        
        $query->EXT('groupMat', 'cat_Products', 'externalName=groups,externalKey=productId');
        
        $query->EXT('isPublic', 'cat_Products', 'externalName=isPublic,externalKey=productId');
        
        $query->EXT('code', 'cat_Products', 'externalName=code,externalKey=productId');
        
        $query->where("#state != 'rejected'");
        
        if (($rec->compare) == 'no') {
            $query->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        }
        
        // Last период && By months
        if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
            if (($rec->compare == 'previous')) {
                $daysInPeriod = dt::daysBetween($rec->to, $rec->from) + 1;
                
                $fromPreviuos = dt::addDays(-$daysInPeriod, $rec->from, false);
                
                $toPreviuos = dt::addDays(-$daysInPeriod, $rec->to, false);
            }
            
            if (($rec->compare == 'month')) {
                $rec->from = (acc_Periods::fetch($rec->firstMonth)->start);
                
                $rec->to = (acc_Periods::fetch($rec->firstMonth)->end);
                
                $fromPreviuos = (acc_Periods::fetch($rec->secondMonth)->start);
                
                $toPreviuos = (acc_Periods::fetch($rec->secondMonth)->end);
            }
            
            $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
        }
        
        // LastYear период
        if (($rec->compare) == 'year') {
            $fromLastYear = dt::addDays(-365, $rec->from);
            $toLastYear = dt::addDays(-365, $rec->to);
            
            $query->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }
        
        $query->where("#state != 'rejected'");
        
        if (isset($rec->dealers)) {
            if ((min(array_keys(keylist::toArray($rec->dealers))) >= 1)) {
                $dealers = keylist::toArray($rec->dealers);
                
                $query->in('dealerId', $dealers);
            }
        }
        
        if ($rec->contragent || $rec->crmGroup) {
            $contragentsArr = array();
            $contragentsId = array();
            
            $query->EXT('folderId', 'doc_Containers', 'externalKey=containerId');
            $query->EXT('coverId', 'doc_Folders', 'externalKey=folderId');
            $query->EXT('groupList', 'crm_Companies', 'externalFieldName=folderId, externalKey=folderId');
            
            if (!$rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $query->in('coverId', $contragentsId);
            }
            
            if ($rec->crmGroup && !$rec->contragent) {
                $query->likeKeylist('groupList', $rec->crmGroup);
            }
            
            if ($rec->crmGroup && $rec->contragent) {
                $contragentsArr = keylist::toArray($rec->contragent);
                
                foreach ($contragentsArr as $val) {
                    $contragentsId[doc_Folders::fetch($val)->coverId] = doc_Folders::fetch($val)->coverId;
                }
                
                $query->in('coverId', $contragentsId);
                
                $query->likeKeylist('groupList', $rec->crmGroup);
            }
        }
        
        if (isset($rec->group)) {
            $query->likeKeylist('groupMat', $rec->group);
        }
        
        if ($rec->articleType != 'all') {
            $query->where("#isPublic = '{$rec->articleType}'");
        }
        
        // Масив бързи продажби //
        $sQuery = sales_Sales::getQuery();
        
        if (($rec->compare) == 'no') {
            $sQuery->where("#valior >= '{$rec->from}' AND #valior <= '{$rec->to}'");
        }
        
        // Last период
        if (($rec->compare) == 'previous') {
            $sQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromPreviuos}' AND #valior <= '{$toPreviuos}')");
        }
        
        // LastYear период
        if (($rec->compare) == 'year') {
            $sQuery->where("(#valior >= '{$rec->from}' AND #valior <= '{$rec->to}') OR (#valior >= '{$fromLastYear}' AND #valior <= '{$toLastYear}')");
        }
        
        $sQuery->like('contoActions', 'ship', false);
        
        $sQuery->EXT('detailId', 'sales_SalesDetails', 'externalName=id,remoteKey=saleId');
        
        while ($sale = $sQuery->fetch()) {
            $salesWithShipArr[$sale->detailId] = $sale->detailId;
        }
        
        // Синхронизира таймлимита с броя записи //
        $rec->count = $query->count();
        
        $timeLimit = $query->count() * 0.05;
        
        if ($timeLimit >= 30) {
            core_App::setTimeLimit($timeLimit);
        }
        
        $num = 1;
        $quantity = 0;
        $flag = false;
        
        while ($recPrime = $query->fetch()) {
            $quantity = $primeCost = $delta = 0;
            $quantityPrevious = $primeCostPrevious = $deltaPrevious = 0;
            $quantityLastYear = $primeCostLastYear = $deltaLastYear = 0;
            
            $DetClass = cls::get($recPrime->detailClassId);
            
            if ($DetClass instanceof sales_SalesDetails) {
                if (is_array($salesWithShipArr)) {
                    if (in_array($recPrime->detailRecId, $salesWithShipArr)) {
                        continue;
                    }
                }
            }
            $id = $recPrime->productId;
            
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                if ($recPrime->valior >= $fromPreviuos && $recPrime->valior <= $toPreviuos) {
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $quantityPrevious = (-1) * $recPrime->quantity;
                        $primeCostPrevious = (-1) * $recPrime->sellCost * $recPrime->quantity;
                        $deltaPrevious = (-1) * $recPrime->delta;
                    } else {
                        $quantityPrevious = $recPrime->quantity;
                        $primeCostPrevious = $recPrime->sellCost * $recPrime->quantity;
                        $deltaPrevious = $recPrime->delta;
                    }
                }
            }
            
            if ($rec->compare == 'year') {
                if ($recPrime->valior >= $fromLastYear && $recPrime->valior <= $toLastYear) {
                    if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                        $quantityLastYear = (-1) * $recPrime->quantity;
                        $primeCostLastYear = (-1) * $recPrime->sellCost * $recPrime->quantity;
                        $deltaLastYear = (-1) * $recPrime->delta;
                    } else {
                        $quantityLastYear = $recPrime->quantity;
                        $primeCostLastYear = $recPrime->sellCost * $recPrime->quantity;
                        $deltaLastYear = $recPrime->delta;
                    }
                }
            }
            
            if ($recPrime->valior >= $rec->from && $recPrime->valior <= $rec->to) {
                if ($DetClass instanceof store_ReceiptDetails || $DetClass instanceof purchase_ServicesDetails) {
                    $quantity = (-1) * $recPrime->quantity;
                    
                    $primeCost = (-1) * $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = (-1) * $recPrime->delta;
                } else {
                    $quantity = $recPrime->quantity;
                    
                    $primeCost = $recPrime->sellCost * $recPrime->quantity;
                    
                    $delta = $recPrime->delta;
                }
            }
            
            // добавяме в масива събитието
            if (!array_key_exists($id, $recs)) {
                $recs[$id] = (object) array(
                    
                    'code' => $recPrime->code ? $recPrime->code : "Art{$recPrime->productId}",
                    'measure' => cat_Products::getProductInfo($recPrime->productId)->productRec->measureId,
                    'productId' => $recPrime->productId,
                    'quantity' => $quantity,
                    'quantityPrevious' => $quantityPrevious,
                    'primeCostPrevious' => $primeCostPrevious,
                    'deltaPrevious' => $deltaPrevious,
                    'quantityLastYear' => $quantityLastYear,
                    'primeCostLastYear' => $primeCostLastYear,
                    'deltaLastYear' => $deltaLastYear,
                    'primeCost' => $primeCost,
                    'group' => $recPrime->groupMat,
                    'groupList' => $recPrime->groupList,
                    'change' => '',
                    'totalValue' => '',
                    'totalDelta' => '',
                    'groupValues' => '',
                    'groupDeltas' => '',
                    'delta' => $delta
                    
                );
            } else {
                $obj = &$recs[$id];
                $obj->quantity += $quantity;
                $obj->quantityPrevious += $quantityPrevious;
                $obj->quantityLastYear += $quantityLastYear;
                $obj->primeCost += $primeCost;
                $obj->delta += $delta;
            }
        }
        $groupValues = array();
        $groupDeltas = array();
        $tempArr = array();
        $totalArr = array();
        $totalValue = $totalDelta = 0;
        foreach ($recs as $v) {
            if (!$rec->group) {
                list($firstGroup) = explode('|', trim($v->group, '|'));
                
                if (!$v->group) {
                    $firstGroup = 'Без група';
                }
                
                $tempArr[$v->productId] = $v;
                $tempArr[$v->productId]->group = $firstGroup;
                $totalValue += $v->primeCost;
                $totalDelta += $v->delta;
                $totalPrimeCostPrevious += $v->primeCostPrevious;
                $totalDeltaPrevious += $v->deltaPrevious;
                $totalPrimeCostLastYear += $v->primeCostLastYear;
                $totalDeltaLastYear += $v->deltaLastYear;
                $groupValues[$firstGroup] += $v->primeCost;
                $groupDeltas[$firstGroup] += $v->delta;
            } else {
                foreach (explode('|', trim($rec->group, '|')) as $gr) {
                    $tempArr[$v->productId] = $v;
                    
                    if (keylist::isIn($gr, $v->group)) {
                        $tempArr[$v->productId]->group = $gr;
                        $totalValue += $v->primeCost;
                        $totalDelta += $v->delta;
                        $totalPrimeCostPrevious += $v->primeCostPrevious;
                        $totalDeltaPrevious += $v->deltaPrevious;
                        $totalPrimeCostLastYear += $v->primeCostLastYear;
                        $totalDeltaLastYear += $v->deltaLastYear;
                        $groupValues[$gr] += $v->primeCost;
                        $groupDeltas[$gr] += $v->delta;
                        
                        break;
                    }
                }
            }
        }
        
        $recs = $tempArr;
        
        foreach ($recs as $v) {
            $v->groupValues = $groupValues[$v->group];
            $v->groupDeltas = $groupDeltas[$v->group];
        }
        
        if (!is_null($recs)) {
            arr::sortObjects($recs, 'code', 'asc', 'stri');
        }
        
        $totalArr['total'] = (object) array(
            'totalValue' => $totalValue,
            'totalDelta' => $totalDelta,
            'totalPrimeCostPrevious' => $totalPrimeCostPrevious,
            'totalDeltaPrevious' => $totalDeltaPrevious,
            'totalPrimeCostLastYear' => $totalPrimeCostLastYear,
            'totalDeltaLastYear' => $totalDeltaLastYear,
        );
        
        array_unshift($recs, $totalArr['total']);
        
        return $recs;
    }
    
    
    /**
     * Връща фийлдсета на таблицата, която ще се рендира
     *
     * @param stdClass $rec
     *                         - записа
     * @param bool     $export
     *                         - таблицата за експорт ли е
     *
     * @return core_FieldSet - полетата
     */
    protected function getTableFieldSet($rec, $export = false)
    {
        $fld = cls::get('core_FieldSet');
        
        if ($rec->compare == 'month') {
            $name1 = acc_Periods::fetch($rec->firstMonth)->title;
            $name2 = acc_Periods::fetch($rec->secondMonth)->title;
        } else {
            $name1 = 'За периода';
            $name2 = 'За сравнение';
        }
        
        if ($export === false) {
            $fld->FLD('group', 'keylist(mvc=cat_groups,select=name)', 'caption=Група');
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            if ($rec->compare != 'no') {
                $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Продажби");
                $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Стойност");
                $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1}->Делта");
                $fld->FLD('quantityCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Продажби,tdClass=newCol");
                $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Стойност,tdClass=newCol");
                $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2}->Делта,tdClass=newCol");
                $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Продажби');
                $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна->Делти');
            } else {
                $fld->FLD('quantity', 'double(smartRound,decimals=2)', 'smartCenter,caption=Продажби');
                $fld->FLD('primeCost', 'double(smartRound,decimals=2)', 'smartCenter,caption=Стойност');
                $fld->FLD('delta', 'double(smartRound,decimals=2)', 'smartCenter,caption=Делта');
            }
        } else {
            $fld->FLD('group', 'varchar', 'caption=Група');
            $fld->FLD('code', 'varchar', 'caption=Код');
            $fld->FLD('productId', 'key(mvc=cat_Products,select=name)', 'caption=Артикул');
            $fld->FLD('measure', 'key(mvc=cat_UoM,select=name)', 'caption=Мярка,tdClass=centered');
            $fld->FLD('quantity', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Продажби");
            $fld->FLD('primeCost', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Стойност");
            $fld->FLD('delta', 'double(smartRound,decimals=2)', "smartCenter,caption={$name1} Делта");
            if ($rec->compare != 'no') {
                $fld->FLD('quantityCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Продажби,tdClass=newCol");
                $fld->FLD('primeCostCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Стойност,tdClass=newCol");
                $fld->FLD('deltaCompare', 'double(smartRound,decimals=2)', "smartCenter,caption={$name2} Делта,tdClass=newCol");
                $fld->FLD('changeSales', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна Продажби');
                $fld->FLD('changeDeltas', 'double(smartRound,decimals=2)', 'smartCenter,caption=Промяна Делти');
            }
        }
        
        return $fld;
    }
    
    
    /**
     * Връща групите
     *
     * @param stdClass $dRec
     * @param bool     $verbal
     *
     * @return mixed $dueDate
     */
    private static function getGroups($dRec, $verbal = true, $rec)
    {
        if ($verbal === true) {
            if (is_numeric($dRec->group)) {
                $group = cat_Groups::getVerbal($dRec->group, 'name') . "<span class= 'fright'><span class= ''>" .
                    'Общо за групата ( стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupValues) .
                    ', делта: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupDeltas) . ' )' . '</span>';
            } else {
                $group = $dRec->group . "<span class= 'fright'>" .
                    'Общо за групата ( стойност: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupValues) .
                    ', делта: ' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->groupDeltas) . ' )' . '</span>';
            }
        } else {
            if (!is_numeric($dRec->group)) {
                $group = 'Без група';
            } else {
                $group = cat_Groups::getVerbal($dRec->group, 'name');
            }
        }
        
        return $group;
    }
    
    
    /**
     * Вербализиране на редовете, които ще се показват на текущата страница в отчета
     *
     * @param stdClass $rec
     *                       - записа
     * @param stdClass $dRec
     *                       - чистия запис
     *
     * @return stdClass $row - вербалния запис
     */
    protected function detailRecToVerbal($rec, &$dRec)
    {
        $Int = cls::get('type_Int');
        $Date = cls::get('type_Date');
        $Double = cls::get('type_Double');
        $Double->params['decimals'] = 2;
        $groArr = array();
        $row = new stdClass();
        
        if ($dRec->totalValue) {
            $row->productId = '<b>' . 'ОБЩО ЗА ПЕРИОДА:' . '</b>';
            $row->primeCost = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValue) . '</b>';
            $row->delta = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDelta) . '</b>';
            
            if ($rec->compare != 'no') {
                if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                    if (($dRec->totalValue - $dRec->totalPrimeCostPrevious) > 0 && $dRec->totalPrimeCostPrevious != 0) {
                        $color = 'green';
                        $marker = '+';
                    } elseif (($dRec->totalValue - $dRec->totalPrimeCostPrevious) < 0.1) {
                        $color = 'red';
                        $marker = '';
                    } else {
                        $color = 'black';
                        $marker = '';
                    }
                    
                    $row->primeCostCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalPrimeCostPrevious) . '</b>';
                    $marker = '';
                    $color = 'black';
                }
                $row->deltaCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDeltaPrevious) . '</b>';
                $row->changeSales = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValue - $dRec->totalPrimeCostPrevious) . '</b>' . '</span>';
                $row->changeDeltas = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDelta - $dRec->totalDeltaPrevious) . '</b>' . '</span>';
                
                
                if ($rec->compare == 'year') {
                    if (($dRec->totalValue - $dRec->totalValueLastYear) > 0 && $dRec->totalValueLastYear != 0) {
                        $color = 'green';
                        $marker = '+';
                    } elseif (($dRec->totalValue - $dRec->totalValueLastYear) < 0) {
                        $color = 'red';
                        $marker = '';
                    } else {
                        $color = 'black';
                        $marker = '';
                    }
                    
                    $row->primeCostCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalPrimeCostLastYear). '</b>';
                    $row->deltaCompare = '<b>' . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDeltaLastYear) . '</b>';
                    
                    if ($dRec->totalValueLastYear != 0) {
                        $compare = ($dRec->totalValue - $dRec->totalValueLastYear) / $dRec->totalValueLastYear;
                    }
                    $row->changeSales = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalValue - $dRec->totalPrimeCostLastYear) . '</b>' . '</span>';
                    $row->changeDeltas = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->totalDelta - $dRec->totalDeltaLastYear) . '</b>' . '</span>';
                }
            }
            
            return $row;
        }
        
        if (isset($dRec->code)) {
            $row->code = $dRec->code;
        }
        if (isset($dRec->productId)) {
            $row->productId = cat_Products::getLinkToSingle_($dRec->productId, 'name');
        }
        if (isset($dRec->measure)) {
            $row->measure = cat_UoM::fetchField($dRec->measure, 'shortName');
        }
        
        foreach (array(
            'quantity',
            'primeCost',
            'delta'
        ) as $fld) {
            $row->{$fld} = core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld});
            if ($dRec->{$fld} < 0) {
                $row->{$fld} = "<span class='red'>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->{$fld}) . '</span>';
            }
        }
        
        $row->group = self::getGroups($dRec, true, $rec);
        
        if ($rec->compare != 'no') {
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                if (($dRec->quantity - $dRec->quantityPrevious) > 0 && $dRec->quantityPrevious != 0) {
                    $color = 'green';
                    $marker = '+';
                } elseif (($dRec->quantity - $dRec->quantityPrevious) < 0.1) {
                    $color = 'red';
                    $marker = '';
                } else {
                    $color = 'black';
                    $marker = '';
                }
                
                $row->quantityCompare = "<span class= ''>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantityPrevious) . '</span>';
                $row->primeCostCompare = "<span class= ''>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->primeCostPrevious) . '</span>';
                $row->deltaCompare = "<span class= ''>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->deltaPrevious) . '</span>';
                $row->changeSales = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->primeCost - $dRec->primeCostPrevious) . '</b>' . '</span>';
                $row->changeDeltas = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->delta - $dRec->deltaPrevious) . '</b>' . '</span>';
            }
            
            if ($rec->compare == 'year') {
                if (($dRec->quantity - $dRec->quantityLastYear) > 0 && $dRec->quantityLastYear != 0) {
                    $color = 'green';
                    $marker = '+';
                } elseif (($dRec->quantity - $dRec->quantityLastYear) < 0) {
                    $color = 'red';
                    $marker = '';
                } else {
                    $color = 'black';
                    $marker = '';
                }
                
                $row->quantityCompare = "<span class= ''>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->quantityLastYear) . '</span>';
                $row->primeCostCompare = "<span class= ''>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->primeCostLastYear) . '</span>';
                $row->deltaCompare = "<span class= ''>" . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->deltaLastYear) . '</span>';
                $row->changeSales = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->primeCost - $dRec->primeCostLastYear) . '</b>' . '</span>';
                $row->changeDeltas = "<span class= {$color}>" . '<b>' . $marker . core_Type::getByName('double(decimals=2)')->toVerbal($dRec->delta - $dRec->deltaLastYear) . '</b>' . '</span>';
            }
        }
        
        return $row;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param frame2_driver_Proto $Driver
     * @param embed_Manager       $Embedder
     * @param core_ET             $tpl
     * @param stdClass            $data
     */
    protected static function on_AfterRecToVerbal(frame2_driver_Proto $Driver, embed_Manager $Embedder, $row, $rec, $fields = array())
    {
        $groArr = array();
        $artArr = array();
        
        $Date = cls::get('type_Date');
        
        $row->from = $Date->toVerbal($rec->from);
        
        $row->to = $Date->toVerbal($rec->to);
        
        if (isset($rec->group)) {
            // избраната позиция
            $groups = keylist::toArray($rec->group);
            foreach ($groups as &$g) {
                $gro = cat_Groups::getVerbal($g, 'name');
                array_push($groArr, $gro);
            }
            
            $row->group = implode(', ', $groArr);
        }
        
        if (isset($rec->article)) {
            $arts = keylist::toArray($rec->article);
            foreach ($arts as &$ar) {
                $art = cat_Products::fetchField("#id = '{$ar}'", 'name');
                array_push($artArr, $art);
            }
            
            $row->art = implode(', ', $artArr);
        }
        
        $arrCompare = array(
            'no' => 'Без сравнение',
            'previous' => 'С предходен период',
            'year' => 'С миналогодишен период',
            'month' => 'По месеци'
        );
        $row->compare = $arrCompare[$rec->compare];
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle(frame2_driver_Proto $Driver, embed_Manager $Embedder, &$tpl, $data)
    {
        $fieldTpl = new core_ET(tr("|*<!--ET_BEGIN BLOCK-->[#BLOCK#]
								<fieldset class='detail-info'><legend class='groupTitle'><small><b>|Филтър|*</b></small></legend>
							    <small><div><!--ET_BEGIN from-->|От|*: [#from#]<!--ET_END from--></div></small>
                                <small><div><!--ET_BEGIN to-->|До|*: [#to#]<!--ET_END to--></div></small>
                                <small><div><!--ET_BEGIN firstMonth-->|Месец 1|*: [#firstMonth#]<!--ET_END firstMonth--></div></small>
                                <small><div><!--ET_BEGIN secondMonth-->|Месец 2|*: [#secondMonth#]<!--ET_END secondMonth--></div></small>
			                 	<small><div><!--ET_BEGIN dealers-->|Търговци|*: [#dealers#]<!--ET_END dealers--></div></small>
			                	<small><div><!--ET_BEGIN contragent-->|Контрагент|*: [#contragent#]<!--ET_END contragent--></div></small>
                                <small><div><!--ET_BEGIN crmGroup-->|Група контрагенти|*: [#crmGroup#]<!--ET_END crmGroup--></div></small>
                                <small><div><!--ET_BEGIN group-->|Групи продукти|*: [#group#]<!--ET_END group--></div></small>
                                <small><div><!--ET_BEGIN art-->|Артикули|*: [#art#]<!--ET_END art--></div></small>
                                <small><div><!--ET_BEGIN compare-->|Сравнение|*: [#compare#]<!--ET_END compare--></div></small>
                                </fieldset><!--ET_END BLOCK-->"));
        
        if ($data->rec->compare == 'month') {
            unset($data->rec->from);
            unset($data->rec->to);
        } else {
            unset($data->rec->firstMonth);
            unset($data->rec->secondMonth);
        }
        if (isset($data->rec->from)) {
            $fieldTpl->append('<b>' . $data->row->from . '</b>', 'from');
        }
        
        if (isset($data->rec->to)) {
            $fieldTpl->append('<b>' . $data->row->to . '</b>', 'to');
        }
        
        if (isset($data->rec->firstMonth)) {
            $fieldTpl->append('<b>' . acc_Periods::fetch($data->rec->firstMonth)->title . '</b>', 'firstMonth');
        }
        
        if (isset($data->rec->secondMonth)) {
            $fieldTpl->append('<b>' . acc_Periods::fetch($data->rec->secondMonth)->title . '</b>', 'secondMonth');
        }
        
        if ((isset($data->rec->dealers)) && ((min(array_keys(keylist::toArray($data->rec->dealers))) >= 1))) {
            foreach (type_Keylist::toArray($data->rec->dealers) as $dealer) {
                $dealersVerb .= (core_Users::getTitleById($dealer) . ', ');
            }
            
            $fieldTpl->append('<b>' . trim($dealersVerb, ',  ') . '</b>', 'dealers');
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'dealers');
        }
        
        if (isset($data->rec->contragent) || isset($data->rec->crmGroup)) {
            $marker = 0;
            if (isset($data->rec->crmGroup)) {
                foreach (type_Keylist::toArray($data->rec->crmGroup) as $group) {
                    $marker++;
                    
                    $groupVerb .= (crm_Groups::getTitleById($group));
                    
                    if ((count((type_Keylist::toArray($data->rec->crmGroup))) - $marker) != 0) {
                        $groupVerb .= ', ';
                    }
                }
                
                $fieldTpl->append('<b>' . $groupVerb . '</b>', 'crmGroup');
            }
            
            $marker = 0;
            
            if (isset($data->rec->contragent)) {
                foreach (type_Keylist::toArray($data->rec->contragent) as $contragent) {
                    $marker++;
                    
                    $contragentVerb .= (doc_Folders::getTitleById($contragent));
                    
                    if ((count(type_Keylist::toArray($data->rec->contragent))) - $marker != 0) {
                        $contragentVerb .= ', ';
                    }
                }
                
                $fieldTpl->append('<b>' . $contragentVerb . '</b>', 'contragent');
            }
        } else {
            $fieldTpl->append('<b>' . 'Всички' . '</b>', 'contragent');
        }
        
        if (isset($data->rec->group)) {
            $fieldTpl->append('<b>' . $data->row->group . '</b>', 'group');
        }
        
        if (isset($data->rec->article)) {
            $fieldTpl->append($data->rec->art, 'art');
        }
        
        if (isset($data->rec->compare)) {
            $fieldTpl->append('<b>' . $data->row->compare . '</b>', 'compare');
        }
        
        $tpl->append($fieldTpl, 'DRIVER_FIELDS');
    }
    
    
    /**
     * След подготовка на реда за експорт
     *
     * @param frame2_driver_Proto $Driver
     * @param stdClass            $res
     * @param stdClass            $rec
     * @param stdClass            $dRec
     */
    protected static function on_AfterGetExportRec(frame2_driver_Proto $Driver, &$res, $rec, $dRec, $ExportClass)
    {
        $res->group = self::getGroups($dRec, false, $rec);
        
        if ($rec->compare != 'no') {
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $res->quantityCompare = $dRec->quantityPrevious;
                $res->primeCostCompare = $dRec->primeCostPrevious;
                $res->deltaCompare = $dRec->deltaPrevious;
                $res->changeSales = $dRec->primeCost - $dRec->primeCostPrevious;
                $res->changeDeltas = ($dRec->delta - $dRec->deltaPrevious);
            }
            
            if ($rec->compare == 'year') {
                $res->quantityCompare = $dRec->quantityLastYear;
                $res->primeCostCompare = $dRec->primeCostLastYear;
                $res->deltaCompare = $dRec->deltaLastYear;
                $res->changeSales = ($dRec->primeCost - $dRec->primeCostLastYear);
                $res->changeDeltas = ($dRec->delta - $dRec->deltaLastYear);
            }
        }
        
        if ($res->totalValue) {
            $res->group = 'ОБЩО ЗА ПЕРИОДА:';
            $res->primeCost = $dRec->totalValue;
            $res->delta = $dRec->totalDelta;
            
            if (($rec->compare == 'previous') || ($rec->compare == 'month')) {
                $res->primeCostCompare = $dRec->totalPrimeCostPrevious;
                $res->deltaCompare = $dRec->totalDeltaPrevious;
                $res->changeSales = ($dRec->primeCost - $dRec->totalPrimeCostPrevious);
                $res->changeDeltas = ($dRec->delta - $dRec->totalDeltaPrevious);
            }
            
            if ($rec->compare == 'year') {
                $res->primeCostCompare = $dRec->totalPrimeCostLastYear;
                $res->deltaCompare = $dRec->$totalDeltaLastYear;
                $res->changeSales = ($dRec->primeCost - $dRec->totalPrimeCostLastYear);
                $res->changeDeltas = ($dRec->delta - $dRec->$totalDeltaLastYear);
            }
        }
    }
}
