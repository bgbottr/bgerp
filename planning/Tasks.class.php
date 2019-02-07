<?php


/**
 * Мениджър на Производствени операции
 *
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2018 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @title     Производствени операции
 */
class planning_Tasks extends core_Master
{
    /**
     * Дали може да бъде само в началото на нишка
     */
    public $onlyFirstInThread = true;
    
    
    /**
     * Шаблон за единичен изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutTask.shtml';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title,fixedAssets,description,productId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'doc_plg_BusinessDoc, doc_plg_Prototype, doc_DocumentPlg, planning_plg_StateManager, planning_Wrapper, acc_plg_DocumentSummary, plg_Search, plg_Clone, plg_Printing, plg_RowTools2, plg_LastUsedKeys';
    
    
    /**
     * Заглавие
     */
    public $title = 'Производствени операции';
    
    
    /**
     * Единично заглавие
     */
    public $singleTitle = 'Производствена операция';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Opr';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.8|Производство';
    
    
    /**
     * Клас обграждащ горния таб
     */
    public $tabTopClass = 'portal planning';
    
    
    /**
     * Поле за начало на търсенето
     */
    public $filterFieldDateFrom = 'timeStart';
    
    
    /**
     * Поле за крайна дата на търсене
     */
    public $filterFieldDateTo = 'timeEnd';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task-normal.png';
    
    
    /**
     * Да не се кешира документа
     */
    public $preventCache = true;
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'title, progress, folderId, state, modifiedOn, modifiedBy';
    
    
    /**
     * Дали винаги да се форсира папка, ако не е зададена
     *
     * @see doc_plg_BusinessDoc
     */
    public $alwaysForceFolderIfEmpty = true;
    
    
    /**
     * Поле за търсене по потребител
     */
    public $filterFieldUsers = false;
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo, taskWorker';
    
    
    /**
     * Кой може да го добавя?
     */
    public $canAdd = 'ceo, taskPlanning';
    
    
    /**
     * Кой може да го активира?
     */
    public $canActivate = 'ceo, taskPlanning';
    
    
    /**
     * Кой може да го активира?
     */
    public $canChangestate = 'ceo, taskPlanning';
    
    
    /**
     * Кой може да го редактира?
     */
    public $canEdit = 'ceo, taskPlanning';
    
    
    /**
     * Може ли да се редактират активирани документи
     */
    public $canEditActivated = true;
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'expectedTimeStart,timeStart,createdOn';
    
    
    /**
     * Дали в листовия изглед да се показва бутона за добавяне
     */
    public $listAddBtn = false;
    
    
    /**
     * Кои са детайлите на класа
     */
    public $details = 'planning_ProductionTaskDetails,planning_ProductionTaskProducts';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_ProductionTaskProducts,cat_products_Params';
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'progress,totalWeight,scrappedQuantity,inputInTask,totalQuantity,plannedQuantity';
    
    
    /**
     * Кои ключове да се тракват, кога за последно са използвани
     */
    public $lastUsedKeys = 'fixedAssets';
    
    
    /**
     * Кои полета от листовия изглед да се скриват ако няма записи в тях
     */
    public $hideListFieldsIfEmpty = 'expectedTimeStart';
    
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    public $interfaces = 'barcode_SearchIntf';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,silent,input=hidden');
        $this->FLD('totalWeight', 'cat_type_Weight', 'caption=Общо тегло,input=none');
        
        $this->FLD('productId', 'key(mvc=cat_Products,select=name,allowEmpty)', 'mandatory,caption=Произвеждане->Артикул,removeAndRefreshForm=packagingId|inputInTask|paramcat,silent');
        $this->FLD('packagingId', 'key(mvc=cat_UoM,select=name)', 'mandatory,caption=Произвеждане->Опаковка,after=productId,input=hidden,tdClass=small-field nowrap,removeAndRefreshForm,silent');
        $this->FLD('plannedQuantity', 'double(smartRound,Min=0)', 'mandatory,caption=Произвеждане->Планирано,after=packagingId');
        $this->FLD('storeId', 'key(mvc=store_Stores,select=name,allowEmpty)', 'caption=Произвеждане->Склад,input=none');
        $this->FLD('indTime', 'time(noSmart)', 'caption=Произвеждане->Норма,smartCenter');
        $this->FLD('totalQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Количество,after=packagingId,input=none');
        $this->FLD('quantityInPack', 'double(smartRound)', 'input=none');
        $this->FLD('scrappedQuantity', 'double(smartRound)', 'mandatory,caption=Произвеждане->Брак,input=none');
        $this->FLD('description', 'richtext(rows=2,bucket=Notes)', 'caption=Допълнително->Описание');
        $this->FLD('showadditionalUom', 'enum(no=Не,yes=Да)', 'caption=Допълнително->Тегло');
        
        $this->FLD('timeStart', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Времена->Начало, changable, tdClass=leftColImportant,formOrder=101');
        $this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност,changable,formOrder=102');
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00,format=smartTime)', 'caption=Времена->Край,changable, tdClass=leftColImportant,formOrder=103');
        $this->FLD('progress', 'percent', 'caption=Прогрес,input=none,notNull,value=0');
        $this->FNC('systemId', 'int', 'silent,input=hidden');
        $this->FLD('expectedTimeStart', 'datetime(format=smartTime)', 'input=hidden,caption=Очаквано начало');
        $this->FLD('additionalFields', 'blob(serialize, compress)', 'caption=Данни,input=none');
        $this->FLD('fixedAssets', 'keylist(mvc=planning_AssetResources,select=name,makeLinks)', 'caption=Произвеждане->Оборудване,after=packagingId');
        $this->FLD('employees', 'keylist(mvc=crm_Persons,select=id,makeLinks)', 'caption=Произвеждане->Служители,after=fixedAssets');
        $this->FLD('inputInTask', 'int', 'caption=Произвеждане->Влагане в,input=none,after=indTime');
        
        $this->setDbIndex('inputInTask');
    }
    
    
    /**
     * След подготовка на сингъла
     */
    protected static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $rec = $data->rec;
        
        $d = new stdClass();
        $d->masterId = $rec->id;
        $d->masterClassId = planning_Tasks::getClassId();
        if ($rec->state == 'closed' || $rec->state == 'stopped' || $rec->state == 'rejected') {
            $d->noChange = true;
            unset($data->editUrl);
        }
        
        cat_products_Params::prepareParams($d);
        $data->paramData = $d;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param cat_ProductDriver $Driver
     * @param embed_Manager     $Embedder
     * @param core_ET           $tpl
     * @param stdClass          $data
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if (isset($data->paramData)) {
            $paramTpl = cat_products_Params::renderParams($data->paramData);
            $tpl->append($paramTpl, 'PARAMS');
        }
        
        // Ако има записани допълнителни полета от артикула
        if (is_array($data->rec->additionalFields) && count($data->rec->additionalFields)) {
            $productFields = planning_Tasks::getFieldsFromProductDriver($data->rec->productId);
            
            // Добавяне на допълнителните полета от артикула
            foreach ($data->rec->additionalFields as $field => $value) {
                if (!isset($value) || $value === '') {
                    continue;
                }
                if (!isset($productFields[$field])) {
                    continue;
                }
                
                // Рендират се
                $block = clone $tpl->getBlock('ADDITIONAL_VALUE');
                $field1 = $productFields[$field]->caption;
                $field1 = explode('->', $field1);
                $field1 = (count($field1) == 2) ? $field1[1] : $field1[0];
                
                $block->placeArray(array('value' => $productFields[$field]->type->toVerbal($value), 'field' => tr($field1)));
                $block->removePlaces();
                $tpl->append($block, 'ADDITIONAL');
            }
        }
    }
    
    
    /**
     * Конвертира един запис в разбираем за човека вид
     * Входният параметър $rec е оригиналният запис от модела
     * резултата е вербалният еквивалент, получен до тук
     */
    public static function recToVerbal_($rec, &$fields = '*')
    {
        static::fillGapsInRec($rec);
        $row = parent::recToVerbal_($rec, $fields);
        $mvc = cls::get(get_called_class());
        $row->title = self::getHyperlink($rec->id, (isset($fields['-list']) ? true : false));
        
        $red = new color_Object('#FF0000');
        $blue = new color_Object('green');
        $grey = new color_Object('#bbb');
        
        $progressPx = min(100, round(100 * $rec->progress));
        $progressRemainPx = 100 - $progressPx;
        
        $color = ($rec->progress <= 1) ? $blue : $red;
        $row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$color}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
        
        $grey->setGradient($color, $rec->progress);
        $row->progress = "<span style='color:{$grey};'>{$row->progress}</span>";
        
        if ($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
            $remainingTime = dt::mysql2timestamp($rec->timeEnd) - time();
            $rec->remainingTime = cal_Tasks::roundTime($remainingTime);
            
            $typeTime = cls::get('type_Time');
            if ($rec->remainingTime > 0) {
                $row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
            } else {
                $row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
            }
        }
        
        // Ако е изчислено очакваното начало и има продължителност, изчисляваме очаквания край
        if (isset($rec->expectedTimeStart, $rec->timeDuration)) {
            $rec->expectedTimeEnd = dt::addSecs($rec->timeDuration, $rec->expectedTimeStart);
            $row->expectedTimeEnd = $mvc->getFieldType('expectedTimeStart')->toVerbal($rec->expectedTimeEnd);
        }
        
        if (isset($rec->originId)) {
            $origin = doc_Containers::getDocument($rec->originId);
            $row->originId = $origin->getLink();
            $row->originShortLink = $origin->getShortHyperlink();
        }
        
        if (isset($rec->inputInTask)) {
            $row->inputInTask = planning_Tasks::getLink($rec->inputInTask);
        }
        
        $row->folderId = doc_Folders::getFolderTitle($rec->folderId);
        $row->productId = cat_Products::getHyperlink($rec->productId, true);
        $shortUom = cat_UoM::getShortName(cat_Products::fetchField($rec->productId, 'measureId'));
        
        foreach (array('plannedQuantity', 'totalQuantity', 'scrappedQuantity') as $quantityFld) {
            if (!$rec->{$quantityFld}) {
                $rec->{$quantityFld} = 0;
                $row->{$quantityFld} = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($rec->{$quantityFld});
                $row->{$quantityFld} = "<span class='quiet'>{$row->{$quantityFld}}</span>";
            } else {
                $rec->{$quantityFld} *= $rec->quantityInPack;
                $row->{$quantityFld} = cls::get('type_Double', array('params' => array('smartRound' => true)))->toVerbal($rec->{$quantityFld});
            }
            
            $row->{$quantityFld} .= ' ' . "<span style='font-weight:normal'>" . $shortUom . '</span>';
        }
        
        if (isset($rec->storeId)) {
            $row->storeId = store_Stores::getHyperlink($rec->storeId, true);
        }
        
        $row->packagingId = cat_UoM::getShortName($rec->packagingId);
        deals_Helper::getPackInfo($row->packagingId, $rec->productId, $rec->packagingId, $rec->quantityInPack);
        
        // Ако няма зададено очаквано начало и край, се приема, че са стандартните
        $rec->expectedTimeStart = ($rec->expectedTimeStart) ? $rec->expectedTimeStart : ((isset($rec->timeStart)) ? $rec->timeStart : null);
        $rec->expectedTimeEnd = ($rec->expectedTimeEnd) ? $rec->expectedTimeEnd : ((isset($rec->timeEnd)) ? $rec->timeEnd : null);
        
        // Проверяване на времената
        foreach (array('expectedTimeStart' => 'timeStart', 'expectedTimeEnd' => 'timeEnd') as $eTimeField => $timeField) {
            
            // Вербализиране на времената
            $DateTime = core_Type::getByName('datetime(format=d.m H:i)');
            $row->{$timeField} = $DateTime->toVerbal($rec->{$timeField});
            $row->{$eTimeField} = $DateTime->toVerbal($rec->{$eTimeField});
            
            // Ако има очаквано и оригинално време
            if (isset($rec->{$eTimeField}, $rec->{$timeField})) {
                
                // Колко е разликата в минути между тях?
                $diffVerbal = null;
                $diff = dt::secsBetween($rec->{$eTimeField}, $rec->{$timeField});
                $diff = ceil($diff / 60);
                if ($diff != 0) {
                    $diffVerbal = cls::get('type_Int')->toVerbal($diff);
                    $diffVerbal = ($diff > 0) ? "<span class='red'>+{$diffVerbal}</span>" : "<span class='green'>{$diffVerbal}</span>";
                }
                
                // Ако има разлика
                if (isset($diffVerbal)) {
                    
                    // Показва се след очакваното време в скоби, с хинт оригиналната дата
                    $hint = 'Зададено|*: ' . $row->{$timeField};
                    $diffVerbal = ht::createHint($diffVerbal, $hint, 'notice', true, array('height' => '12', 'width' => '12'));
                    $row->{$eTimeField} .= " <span style='font-weight:normal'>({$diffVerbal})</span>";
                }
            }
        }
        
        if (isset($fields['-list']) && !isset($fields['-detail'])) {
            $row->title .= "<br><small>{$row->originShortLink}</small>";
        }
        
        if (isset($fields['-single'])) {
            
            // Показване на разширеното описание на артикула
            $row->toggleBtn = "<a href=\"javascript:toggleDisplay('{$rec->id}inf')\"  style=\"background-image:url(" . sbf('img/16/toggle1.png', "'") . ');" class=" plus-icon more-btn"> </a>';
            $row->productDescription = cat_Products::getAutoProductDesc($rec->productId, null, 'detailed', 'job');
            $row->tId = $rec->id;
        }
        
        if (!empty($rec->employees)) {
            $row->employees = planning_Hr::getPersonsCodesArr($rec->employees, true);
            $row->employees = implode(', ', $row->employees);
        }
        
        return $row;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentInterface
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        $row = new stdClass();
        
        $row->title = self::getRecTitle($rec);
        $row->authorId = $rec->createdBy;
        $row->author = $this->getVerbal($rec, 'createdBy');
        $row->recTitle = $row->title;
        $row->state = $rec->state;
        $row->subTitle = doc_Containers::getDocument($rec->originId)->getShortHyperlink();
        
        return $row;
    }
    
    
    /**
     * Прави заглавие на МО от данните в записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $title = cat_Products::getVerbal($rec->productId, 'name');
        $title = "Opr{$rec->id} - " . $title;
        
        return $title;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    protected static function on_AfterInputEditForm($mvc, &$form)
    {
        $rec = &$form->rec;
        
        if ($form->isSubmitted()) {
            if ($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
                $form->setError('timeEnd', 'Крайният срок трябва да е след началото на операцията');
            }
            
            if (!empty($rec->timeStart) && !empty($rec->timeDuration) && !empty($rec->timeEnd)) {
                if (strtotime(dt::addSecs($rec->timeDuration, $rec->timeStart)) != strtotime($rec->timeEnd)) {
                    $form->setWarning('timeStart,timeDuration,timeEnd', 'Въведеното начало плюс продължителността не отговарят на въведената крайната дата');
                }
            }
            
            // Може да се избират само оборудвания от една група
            if (isset($rec->fixedAssets)) {
                if (!planning_AssetGroups::haveSameGroup($rec->fixedAssets)) {
                    $form->setError('fixedAssets', 'Оборудванията са от различни групи');
                }
            }
            
            $pInfo = cat_Products::getProductInfo($rec->productId);
            $rec->quantityInPack = ($pInfo->packagings[$rec->packagingId]) ? $pInfo->packagings[$rec->packagingId]->quantity : 1;
            $rec->title = cat_Products::getTitleById($rec->productId);
            
            if (empty($rec->id)) {
                $description = cat_Products::fetchField($form->rec->productId, 'info');
                if (!empty($description)) {
                    $rec->description = $description;
                }
            }
        }
    }
    
    
    /**
     * Добавя допълнителни полетата в антетката
     *
     * @param core_Master $mvc
     * @param NULL|array  $res
     * @param object      $rec
     * @param object      $row
     */
    protected static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr['info'] = array('name' => tr('Информация'), 'val' => tr("|*<table>
																		   <tr><td style='font-weight:normal'>|Задание|*:</td> <td>[#originId#]</td></tr>
																		   <tr><td style='font-weight:normal'>|Артикул|*:</td> <td>[#productId#] [#toggleBtn#]</td></tr>
																		   <!--ET_BEGIN inputInTask--><tr><td style='font-weight:normal'>|Влагане в|*:</td> <td>[#inputInTask#]</td></tr><!--ET_END inputInTask-->
																		   <!--ET_BEGIN storeId--><tr><td style='font-weight:normal'>|Склад|*:</td> <td>[#storeId#]</td></tr><!--ET_END storeId-->
																		   <!--ET_BEGIN fixedAssets--><tr><td style='font-weight:normal'>|Оборудване|*:</td> <td>[#fixedAssets#]</td></tr><!--ET_END fixedAssets-->
																		   <!--ET_BEGIN employees--><tr><td style='font-weight:normal'>|Служители|*:</td> <td>[#employees#]</td></tr><!--ET_END employees-->
																		   <tr><td colspan='2'>[#progressBar#] [#progress#]</td></tr>
																		   </table>"));
        $packagingId = cat_UoM::getTitleById($rec->packagingId);
        $resArr['quantity'] = array('name' => tr('Количества'), 'val' => tr("|*<table>
				<tr><td style='font-weight:normal'>|Планирано|*:</td><td>[#plannedQuantity#]</td></tr>
				<tr><td style='font-weight:normal'>|Произведено|*:</td><td>[#totalQuantity#]</td></tr>
				<tr><td style='font-weight:normal'>|Бракувано|*:</td><td>[#scrappedQuantity#]</td></tr>
				<tr><td style='font-weight:normal'>|Произв. ед.|*:</td><td>{$packagingId}</td></tr>
				<!--ET_BEGIN indTime--><tr><td style='font-weight:normal'>|Заработка|*:</td><td>[#indTime#]</td></tr><!--ET_END indTime-->
				</table>"));
        
        if ($rec->showadditionalUom == 'yes') {
            $resArr['quantity']['val'] .= tr("|*<span style='font-weight:normal'>|Общо тегло|*:</span> [#totalWeight#]");
        } else {
            $resArr['quantity']['val'] .= tr("|*<span style='font-weight:normal'>|Без допълнително тегло|*:</span>");
        }
        
        if (!empty($rec->indTime)) {
            $row->indTime .= '/' . tr($packagingId);
        }
        
        if (!empty($row->timeStart) || !empty($row->timeDuration) || !empty($row->timeEnd) || !empty($row->expectedTimeStart) || !empty($row->expectedTimeEnd)) {
            $resArr['start'] = array('name' => tr('Планирани времена'), 'val' => tr("|*<!--ET_BEGIN expectedTimeStart--><div><span style='font-weight:normal'>|Очаквано начало|*</span>: [#expectedTimeStart#]</div><!--ET_END expectedTimeStart-->
		        	<!--ET_BEGIN timeDuration--><div><span style='font-weight:normal'>|Прод-ност|*</span>: [#timeDuration#]</div><!--ET_END timeDuration-->
        			 																 <!--ET_BEGIN expectedTimeEnd--><div><span style='font-weight:normal'>|Очакван край|*</span>: [#expectedTimeEnd#]</div><!--ET_END expectedTimeEnd-->
        																			 <!--ET_BEGIN remainingTime--><div>[#remainingTime#]</div><!--ET_END remainingTime-->"));
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
        $rec = $this->fetch($id);
        $updateFields = 'totalQuantity,totalWeight,scrappedQuantity,progress,modifiedOn,modifiedBy';
        if (!$rec->quantityInPack) {
            $rec->quantityInPack = 1;
            $updateFields .= ',quantityInPack';
        }
        
        // Колко е общото к-во досега
        $dQuery = planning_ProductionTaskDetails::getQuery();
        $dQuery->where("#taskId = {$rec->id} AND #productId = {$rec->productId} AND #type = 'production' AND #state != 'rejected'");
        $dQuery->XPR('sumQuantity', 'double', "SUM(#quantity / {$rec->quantityInPack})");
        $dQuery->XPR('sumWeight', 'double', 'SUM(#weight)');
        $dQuery->XPR('sumScrappedQuantity', 'double', "SUM(#scrappedQuantity / {$rec->quantityInPack})");
        $dQuery->show('sumQuantity,sumWeight,sumScrappedQuantity');
        
        $res = $dQuery->fetch();
        
        // Преизчисляваме общото тегло
        $rec->totalWeight = $res->sumWeight;
        $rec->totalQuantity = $res->sumQuantity;
        $rec->scrappedQuantity = $res->sumScrappedQuantity;
        
        // Изчисляваме колко % от зададеното количество е направено
        if (!empty($rec->plannedQuantity)) {
            $percent = ($rec->totalQuantity - $rec->scrappedQuantity) / $rec->plannedQuantity;
            $rec->progress = round($percent, 2);
        }
        
        $rec->progress = max(array($rec->progress, 0));
        
        return $this->save($rec, $updateFields);
    }
    
    
    /**
     * Проверка дали нов документ може да бъде добавен в
     * посочената папка като начало на нишка
     *
     * @param $folderId int ид на папката
     */
    public static function canAddToFolder($folderId)
    {
        return true;
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'add' || $action == 'edit' || $action == 'changestate') {
            if (isset($rec->originId)) {
                $origin = doc_Containers::getDocument($rec->originId);
                $state = $origin->fetchField('state');
                if ($state == 'closed' || $state == 'draft' || $state == 'rejected') {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'add') {
            if (isset($rec->originId)) {
                // Може да се добавя само към активно задание
                if ($origin = doc_Containers::getDocument($rec->originId)) {
                    if (!$origin->isInstanceOf('planning_Jobs')) {
                        $requiredRoles = 'no_one';
                    }
                }
            } elseif ($rec->folderId) {
                $requiredRoles = 'no_one';
            }
        }
        
        // Ако има прогрес, операцията не може да се оттегля
        if ($action == 'reject' && isset($rec)) {
            if (planning_ProductionTaskDetails::fetchField("#taskId = {$rec->id} AND #state != 'rejected'")) {
                $requiredRoles = 'no_one';
            }
        }
        
        if ($action == 'close' && $rec) {
            if ($rec->state != 'active' && $rec->state != 'wakeup' && $rec->state != 'stopped') {
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * След успешен запис
     */
    protected static function on_AfterCreate($mvc, &$rec)
    {
        // Ако записа е създаден с клониране не се прави нищо
        if ($rec->_isClone === true) {
            
            return;
        }
        
        if (isset($rec->originId)) {
            $originDoc = doc_Containers::getDocument($rec->originId);
            $originRec = $originDoc->fetch();
            
            // Ако е по източник
            if (isset($rec->systemId)) {
                $tasks = cat_Products::getDefaultProductionTasks($originRec->productId, $originRec->quantity);
                if (isset($tasks[$rec->systemId])) {
                    $def = $tasks[$rec->systemId];
                    
                    // Намираме на коя дефолтна операция отговаря и извличаме продуктите от нея
                    $r = array();
                    foreach (array('production' => 'product', 'input' => 'input', 'waste' => 'waste') as $var => $type) {
                        if (is_array($def->products[$var])) {
                            foreach ($def->products[$var] as $p) {
                                $p = (object) $p;
                                $nRec = new stdClass();
                                $nRec->taskId = $rec->id;
                                $nRec->packagingId = $p->packagingId;
                                $nRec->quantityInPack = $p->quantityInPack;
                                $nRec->plannedQuantity = $p->packQuantity * $rec->plannedQuantity * $rec->quantityInPack * $p->quantityInPack;
                                $nRec->productId = $p->productId;
                                $nRec->type = $type;
                                $nRec->storeId = $rec->storeId;
                                
                                planning_ProductionTaskProducts::save($nRec);
                            }
                        }
                    }
                }
            }
        }
        
        // Копиране на параметрите на артикула към операцията
        
        if (!is_array($rec->params)) {
            
            return;
        }
        
        $tasksClassId = planning_Tasks::getClassId();
        foreach ($rec->params as $k => $o) {
            if (!isset($rec->{$k})) {
                continue;
            }
            
            $nRec = (object) array('paramId' => $o->paramId, 'paramValue' => $rec->{$k}, 'classId' => $tasksClassId, 'productId' => $rec->id);
            if ($id = cat_products_Params::fetchField("#classId = {$tasksClassId} AND #productId = {$rec->id} AND #paramId = {$o->paramId}", 'id')) {
                $nRec->id = $id;
            }
            
            cat_products_Params::save($nRec, null, 'REPLACE');
        }
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $form = &$data->form;
        $rec = $form->rec;
        
        if (isset($rec->systemId)) {
            $form->setField('prototypeId', 'input=none');
        }
        
        if (empty($rec->id)) {
            if ($folderId = Request::get('folderId', 'key(mvc=doc_Folders)')) {
                unset($rec->threadId);
                $rec->folderId = $folderId;
            }
        }
        
        // За произвеждане може да се избере само артикула от заданието
        $origin = doc_Containers::getDocument($rec->originId);
        $originRec = $origin->fetch();
        
        // Добавяме допустимите опции
        $products = cat_Products::getByProperty('canManifacture');
        $form->setOptions('productId', array('' => '') + $products);
        
        if (count($products) == 1) {
            $form->setDefault('productId', key($products));
        }
        
        $tasks = cat_Products::getDefaultProductionTasks($originRec->productId, $originRec->quantity);
        
        if (isset($rec->systemId, $tasks[$rec->systemId])) {
            foreach (array('plannedQuantity', 'productId', 'quantityInPack', 'packagingId') as $fld) {
                $form->setDefault($fld, $tasks[$rec->systemId]->{$fld});
            }
            $form->setReadOnly('productId');
        }
        
        // Ако не е указано друго, е артикула от заданието
        $form->setDefault('productId', $originRec->productId);
        
        if (isset($rec->productId)) {
            if (empty($rec->id)) {
                
                // Показване на параметрите за задача във формата, като задължителни полета
                $params = cat_Products::getParams($rec->productId);
                $taskParams = cat_Params::getTaskParamIds();
                $diff = array_intersect_key($params, $taskParams);
                foreach ($diff as $pId => $v) {
                    $paramRec = cat_Params::fetch($pId);
                    $name = cat_Params::getVerbal($paramRec, 'name');
                    $form->FLD("paramcat{$pId}", 'double', "caption=Параметри на задачата->{$name},mandatory,before=description");
                    $ParamType = cat_Params::getTypeInstance($pId, $mvc, $rec->id);
                    $form->setFieldType("paramcat{$pId}", $ParamType);
                    
                    // Дефолта е параметъра от дефолтната задача за този артикул, ако има такава
                    if (isset($rec->systemId, $tasks[$rec->systemId])) {
                        $form->setDefault("paramcat{$pId}", $tasks[$rec->systemId]->params[$pId]);
                    }
                    
                    if (!empty($paramRec->suffix)) {
                        $suffix = cat_Params::getVerbal($paramRec, 'suffix');
                        $form->setField("paramcat{$pId}", "unit={$suffix}");
                    }
                    
                    if (isset($v)) {
                        if ($ParamType instanceof fileman_FileType) {
                            $form->setDefault("paramcat{$pId}", $v);
                        } else {
                            $form->setSuggestions("paramcat{$pId}", array('' => '', "{$v}" => "{$v}"));
                        }
                    }
                    
                    $rec->params["paramcat{$pId}"] = (object) array('paramId' => $pId);
                }
            }
            
            $packs = cat_Products::getPacks($rec->productId);
            $form->setOptions('packagingId', $packs);
            
            $measureId = ($originRec->productId == $rec->productId) ? $originRec->packagingId : cat_Products::fetchField($rec->productId, 'measureId');
            $form->setDefault('packagingId', $measureId);
            $productInfo = cat_Products::getProductInfo($rec->productId);
            
            // Ако артикула е вложим, може да се влага по друга операция
            if (isset($productInfo->meta['canConvert'])) {
                $tasks = self::getTasksByJob($origin->that, true);
                unset($tasks[$rec->id]);
                if (count($tasks)) {
                    $form->setField('inputInTask', 'input');
                    $form->setOptions('inputInTask', array('' => '') + $tasks);
                }
            }
            
            $measureShort = cat_UoM::getShortName($rec->packagingId);
            if (!isset($productInfo->meta['canStore'])) {
                $form->setField('plannedQuantity', "unit={$measureShort}");
            } else {
                $form->setField('packagingId', 'input');
                $form->setField('storeId', 'input,mandatory');
            }
            $form->setField('indTime', "unit=|за|* 1 {$measureShort}");
            
            if ($rec->productId == $originRec->productId) {
                $toProduce = ($originRec->quantity - $originRec->quantityProduced) / $originRec->quantityInPack;
                if ($toProduce > 0) {
                    $form->setDefault('plannedQuantity', $toProduce);
                }
            }
            
            // Подаване на формата на драйвера на артикула, ако иска да добавя полета
            if ($Driver = cat_Products::getDriver($rec->productId)) {
                $Driver->addTaskFields($rec->productId, $form);
                
                // Попълване на полетата с данните от драйвера
                $driverFields = planning_Tasks::getFieldsFromProductDriver($rec->productId);
                
                foreach ($driverFields as $name => $f) {
                    if (isset($rec->additionalFields[$name])) {
                        $rec->{$name} = $rec->additionalFields[$name];
                    }
                }
            }
        }
        
        foreach (array('fixedAssets' => 'planning_AssetResources', 'employees' => 'planning_Hr') as $field => $Det) {
            $arr = $Det::getByFolderId($rec->folderId);
            if (!empty($rec->{$field})) {
                $alreadyIn = keylist::toArray($rec->{$field});
                foreach ($alreadyIn as $fId) {
                    if (!array_key_exists($fId, $arr)) {
                        $arr[$fId] = $Det::getTitleById($fId, false);
                    }
                }
            }
            
            if (count($arr)) {
                $form->setSuggestions($field, array('' => '') + $arr);
            } else {
                $form->setField($field, 'input=none');
            }
        }
        
        if (isset($rec->id)) {
            $taskClassId = planning_Tasks::getClassId();
            $haveDetail = planning_ProductionTaskDetails::fetch("#type = 'production' AND #taskId = {$rec->id}");
            $haveParams = cat_products_Params::fetchField("#classId = '{$taskClassId}' AND #productId = {$rec->id}");
            
            if ($haveDetail || $haveParams) {
                $form->setReadOnly('productId');
                $form->setReadOnly('packagingId');
            }
            
            if ($haveDetail && $data->action != 'clone') {
                if (!empty($rec->fixedAssets)) {
                    $form->setField('fixedAssets', 'input=none');
                }
                
                if (!empty($rec->employees)) {
                    $form->setField('employees', 'input=hidden');
                }
            }
        }
    }
    
    
    /**
     * Връща масив със съществуващите задачи
     *
     * @param int      $containerId
     * @param stdClass $data
     *
     * @return void
     */
    protected function prepareExistingTaskRows($containerId, &$data)
    {
        // Всички създадени задачи към заданието
        $query = $this->getQuery();
        $query->where("#state != 'rejected'");
        $query->where("#originId = {$containerId}");
        $query->XPR('orderByState', 'int', "(CASE #state WHEN 'wakeup' THEN 1 WHEN 'active' THEN 2 WHEN 'stopped' THEN 3 WHEN 'closed' THEN 4 WHEN 'waiting' THEN 5 ELSE 6 END)");
        $query->orderBy('#orderByState=ASC');
        $fields = $this->selectFields();
        $fields['-list'] = $fields['-detail'] = true;
        
        // Подготвяме данните
        while ($rec = $query->fetch()) {
            $data->recs[$rec->id] = $rec;
            $row = planning_Tasks::recToVerbal($rec, $fields);
            
            $subArr = array();
            if (!empty($row->fixedAssets)) {
                $subArr[] = tr('Оборудване:|* ') . $row->fixedAssets;
            }
            if (!empty($row->employees)) {
                $subArr[] = tr('Служители:|* ') . $row->employees;
            }
            if (count($subArr)) {
                $row->info = '<small>' . implode(' &nbsp; ', $subArr) . '</small>';
            }
            
            $row->modified = $row->modifiedOn . ' ' . tr('от||by') . ' ' . $row->modifiedBy;
            $row->modified = "<div style='text-align:center'> {$row->modified} </div>";
            
            $data->rows[$rec->id] = $row;
        }
    }
    
    
    /**
     * Подготвя задачите към заданията
     */
    public function prepareTasks($data)
    {
        $masterRec = $data->masterData->rec;
        $containerId = $data->masterData->rec->containerId;
        
        $data->recs = $data->rows = array();
        $this->prepareExistingTaskRows($containerId, $data);
        
        // Ако потребителя може да добавя операция от съответния тип, ще показваме бутон за добавяне
        if ($this->haveRightFor('add', (object) array('originId' => $containerId))) {
            if (!Mode::isReadOnly()) {
                $data->addUrlArray = array('planning_Jobs', 'selectTaskAction', 'originId' => $containerId, 'ret_url' => true);
            }
        }
    }
    
    
    /**
     * Рендира задачите на заданията
     */
    public function renderTasks($data)
    {
        $tpl = new ET('');
        
        // Ако няма намерени записи, не се рендира нищо
        // Рендираме таблицата с намерените задачи
        $table = cls::get('core_TableView', array('mvc' => $this));
        $fields = 'title=Операция,progress=Прогрес,expectedTimeStart=Времена->Начало, timeDuration=Времена->Прод-ст, timeEnd=Времена->Край, modified=Модифицирано,info=@info';
        $data->listFields = core_TableView::filterEmptyColumns($data->rows, $fields, 'timeStart,timeDuration,timeEnd,expectedTimeStart');
        $this->invoke('BeforeRenderListTable', array($tpl, &$data));
        
        $tpl = $table->get($data->rows, $data->listFields);
        
        // Имали бутони за добавяне
        if (isset($data->addUrlArray)) {
            $btn = ht::createBtn('Нова операция', $data->addUrlArray, false, false, "title=Създаване на производствена операция към задание,ef_icon={$this->singleIcon}");
            $tpl->append($btn, 'btnTasks');
        }
        
        // Връщаме шаблона
        return $tpl;
    }
    
    
    /**
     * Преди запис на документ
     */
    protected static function on_BeforeSave(core_Manager $mvc, $res, $rec)
    {
        $rec->additionalFields = array();
        
        // Вкарване на записите специфични от драйвера в блоб поле
        $productFields = self::getFieldsFromProductDriver($rec->productId);
        if (is_array($productFields)) {
            foreach ($productFields as $name => $field) {
                if (isset($rec->{$name})) {
                    $rec->additionalFields[$name] = $rec->{$name};
                }
            }
        }
        
        $rec->additionalFields = count($rec->additionalFields) ? $rec->additionalFields : null;
    }
    
    
    /**
     * Ф-я връщаща полетата специфични за артикула от драйвера
     *
     * @param int $productId
     *
     * @return array
     */
    public static function getFieldsFromProductDriver($productId)
    {
        $form = cls::get('core_Form');
        if ($Driver = cat_Products::getDriver($productId)) {
            $Driver->addTaskFields($productId, $form);
        }
        
        return $form->selectFields();
    }
    
    
    /**
     * Подготовка на филтър формата
     */
    protected static function on_AfterPrepareListFilter($mvc, $data)
    {
        $data->listFilter->FLD('assetId', 'key(mvc=planning_AssetResources,select=name,allowEmpty)', 'caption=Оборудване');
        $data->listFilter->showFields .= ',assetId';
        $data->listFilter->input('assetId');
        
        // Филтър по всички налични департаменти
        $folders = doc_Folders::getOptionsByCoverInterface('planning_ActivityCenterIntf');
       
        if (count($folders)) {
            $data->listFilter->setField('folderId', 'input');
            $data->listFilter->setOptions('folderId', array('' => '') + $folders);
            $data->listFilter->showFields .= ',folderId';
            $data->listFilter->input('folderId');
        }
        
        // Филтър по департамент
        if ($folderId = $data->listFilter->rec->folderId) {
            $data->query->where("#folderId = {$folderId}");
            unset($data->listFields['folderId']);
        }
        
        if ($assetId = $data->listFilter->rec->assetId) {
            $data->query->where("LOCATE('|{$assetId}|', #fixedAssets)");
        }
        
        // Показване на полето за филтриране
        if ($filterDateField = $data->listFilter->rec->filterDateField) {
            $filterFieldArr = array($filterDateField => ($filterDateField == 'expectedTimeStart') ? 'Очаквано начало' : ($filterDateField == 'timeStart' ? 'Начало' : 'Създаване'));
            arr::placeInAssocArray($data->listFields, $filterFieldArr, 'title');
        }
        
        if (!Request::get('Rejected', 'int')) {
            $data->listFilter->setOptions('state', array('' => '') + arr::make('draft=Чернова, active=Активен, pendingandactive=Активни+Чакащи,closed=Приключен, stopped=Спрян, wakeup=Събуден,waiting=Чакащо', true));
            $data->listFilter->setField('state', 'placeholder=Всички,formOrder=1000');
            $data->listFilter->showFields .= ',state';
            $data->listFilter->input('state');
            
            if ($state = $data->listFilter->rec->state) {
                if ($state != 'pendingandactive') {
                    $data->query->where("#state = '{$state}'");
                } else {
                    $data->query->where("#state = 'active' OR #state = 'waiting'");
                }
            }
        }
    }
    
    
    /**
     * Връща масив от задачи към дадено задание
     *
     * @param int  $jobId      - ид на задание
     * @param bool $onlyActive - Не оттеглените или само активните/събудени/спрени
     *
     * @return array $res         - масив с намерените задачи
     */
    public static function getTasksByJob($jobId, $onlyActive = false)
    {
        $res = array();
        $oldContainerId = planning_Jobs::fetchField($jobId, 'containerId');
        $query = static::getQuery();
        $query->where("#originId = {$oldContainerId}");
        
        if ($onlyActive === true) {
            $query->where("#state = 'active' || #state = 'wakeup' || #state = 'stopped'");
        } else {
            $query->where("#state != 'rejected'");
        }
        
        while ($rec = $query->fetch()) {
            $res[$rec->id] = self::getRecTitle($rec, false);
        }
        
        return $res;
    }
    
    
    /**
     * Ако са въведени две от времената (начало, продължителност, край) а третото е празно, изчисляваме го.
     * ако е въведено само едно време или всички не правим нищо
     *
     * @param stdClass $rec - записа който ще попълним
     *
     * @return void
     */
    protected static function fillGapsInRec(&$rec)
    {
        if (isset($rec->timeStart, $rec->timeDuration) && empty($rec->timeEnd)) {
            
            // Ако има начало и продължителност, изчисляваме края
            $rec->timeEnd = dt::addSecs($rec->timeDuration, $rec->timeStart);
        } elseif (isset($rec->timeStart, $rec->timeEnd) && empty($rec->timeDuration)) {
            
            // Ако има начало и край, изчисляваме продължителността
            $rec->timeDuration = $diff = strtotime($rec->timeEnd) - strtotime($rec->timeStart);
        } elseif (isset($rec->timeDuration, $rec->timeEnd) && empty($rec->timeStart)) {
            
            // Ако има продължителност и край, изчисляваме началото
            $rec->timeStart = dt::addSecs(-1 * $rec->timeDuration, $rec->timeEnd);
        }
    }
    
    
    /**
     * Добавя ключови думи за пълнотекстово търсене
     */
    protected static function on_AfterGetSearchKeywords($mvc, &$res, $rec)
    {
        if (empty($rec->id)) {
            
            return;
        }
        
        // Добавяне на всички ключови думи от прогреса
        $dQuery = planning_ProductionTaskDetails::getQuery();
        $dQuery->XPR('concat', 'varchar', 'GROUP_CONCAT(#searchKeywords)');
        $dQuery->where("#taskId = {$rec->id}");
        $dQuery->limit(1);
        
        if ($keywords = $dQuery->fetch()->concat) {
            $keywords = str_replace(' , ', ' ', $keywords);
            $res = ' ' . $res . ' ' . $keywords;
        }
    }
    
    
    /**
     * Връща количеството произведено по задачи по дадено задание
     *
     * @param mixed                     $jobId
     * @param product|input|waste|start $type
     *
     * @return float $quantity
     */
    public static function getProducedQuantityForJob($jobId)
    {
        expect($jobRec = planning_Jobs::fetchRec($jobId));
        
        $query = planning_Tasks::getQuery();
        $query->XPR('sum', 'double', 'SUM((COALESCE(#totalQuantity, 0) - COALESCE(#scrappedQuantity, 0))* #quantityInPack)');
        $query->where("#originId = {$jobRec->containerId} AND #productId = {$jobRec->productId}");
        $query->where("#state != 'rejected' AND #state != 'pending'");
        $query->show('totalQuantity,sum');
        
        $sum = $query->fetch()->sum;
        $quantity = (!empty($sum)) ? $sum : 0;
        
        return $quantity;
    }
    
    
    /**
     * Връща името на операцията готово за партида
     *
     * @param mixed $taskId - ид на операцията
     *
     * @return string $batchName - името на партидата, генерирана от операцията
     */
    public static function getBatchName($taskId)
    {
        $rec = self::fetchRec($taskId);
        $productName = cat_Products::getVerbal($rec->productId, 'name');
        $code = cat_Products::getVerbal($rec->productId, 'code');
        $batchName = "{$productName}/{$code}/Opr{$rec->id}";
        $batchName = str::removeWhitespaces($batchName);
        
        return $batchName;
    }
    
    
    /**
     * В кои корици може да се вкарва документа
     *
     * @return array - интерфейси, които трябва да имат кориците
     */
    public static function getCoversAndInterfacesForNewDoc()
    {
        return array('folderClass' => 'planning_Centers');
    }
    
    
    /**
     * Търси по подадения баркод
     *
     * @param string $str
     *
     * @return array
     * ->title - заглавие на резултата
     * ->url - линк за хипервръзка
     * ->comment - html допълнителна информация
     * ->priority - приоритет
     */
    public function searchByCode($str)
    {
        $resArr = array();
        
        $str = trim($str);
        
        $taskDetilQuery = planning_ProductionTaskDetails::getQuery();
        $taskDetilQuery->where(array("#serial = '[#1#]'", $str));
        
        while($dRec = $taskDetilQuery->fetch()) {
            
            $res = new stdClass();
            
            $tRec = $this->fetch($dRec->taskId);
            
            $res->title = $tRec->title;
            
            if ($this->haveRightFor('single', $tRec)) {
                $res->url = array('planning_Tasks', 'single', $dRec->taskId);
                
                $dRow = planning_ProductionTaskDetails::recToVerbal($dRec);
                $res->comment = tr('Артикул') . ': ' . $dRow->productId . ' ' . tr('Количество') . ': ' . $dRow->quantity . $dRow->shortUoM;
                
                if ($tRec->progress) {
                    $progress = $this->getVerbal($tRec, 'progress');
                    $res->title .= ' (' . $progress . ')';
                }
            }
            
            $res->priority = 1;
            if ($dRec->state == 'active') {
                $res->priority = 2;
            } else if ($dRec->state == 'rejected') {
                $res->priority = 0;
            }
            
            $resArr[] = $res;
        }
        
        return $resArr;
    }
}
