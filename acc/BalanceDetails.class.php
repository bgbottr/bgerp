<?php



/**
 * Мениджър на записите в баланс
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class acc_BalanceDetails extends core_Detail
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'acc_Wrapper, Accounts=acc_Accounts, Lists=acc_Lists, plg_StyleNumbers, plg_AlignDecimals, plg_Printing';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = "accountNum, accountId, baseQuantity, baseAmount, 
                        debitQuantity, debitAmount,    creditQuantity, creditAmount, 
                        blQuantity, blAmount";
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    var $masterKey = 'balanceId';
    
    
    /**
     * @var acc_Accounts
     */
    var $Accounts;
    
    
    /**
     * @var acc_Lists
     */
    var $Lists;
    
    
    /**
     * Временен акумулатор при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    public $balance;
    
    
    /**
     *
     * Стратегии на сметките - използва се при изчисляване на баланс
     * (@see acc_BalanceDetails::calculateBalance())
     *
     * @var array
     */
    private $strategies;
    
    
    /**
     * Кой има достъп до хронологичната справка
     */
    public $canHistory = 'powerUser';
    
    
    /**
     * Работен кеш
     */
    private $cache = array();
    
    
    /**
     * Работен кеш
     */
    private $buffer = array();
    
    
    /**
     * Еденично заглавие
     */
    public $title = 'Детайли на баланса';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('balanceId', 'key(mvc=acc_Balances)', 'caption=Баланс');
        $this->FLD('accountId', 'key(mvc=acc_Accounts,title=title)', 'caption=Сметка->име,column=none');
        $this->EXT('accountNum', 'acc_Accounts', 'externalName=num,externalKey=accountId', 'caption=Сметка->№');
        $this->FLD('ent1Id', 'key(mvc=acc_Items,select=titleLink)', 'caption=Сметка->перо 1');
        $this->FLD('ent2Id', 'key(mvc=acc_Items,select=titleLink)', 'caption=Сметка->перо 2');
        $this->FLD('ent3Id', 'key(mvc=acc_Items,select=titleLink)', 'caption=Сметка->перо 3');
        $this->FLD('baseQuantity', 'double(maxDecimals=3)', 'caption=База->Количество,tdClass=ballance-field');
        $this->FLD('baseAmount', 'double(decimals=2)', 'caption=База->Сума,tdClass=ballance-field');
        $this->FLD('debitQuantity', 'double(maxDecimals=3)', 'caption=Дебит->Количество,tdClass=ballance-field');
        $this->FLD('debitAmount', 'double(decimals=2)', 'caption=Дебит->Сума,tdClass=ballance-field');
        $this->FLD('creditQuantity', 'double(maxDecimals=3)', 'caption=Кредит->Количество,tdClass=ballance-field');
        $this->FLD('creditAmount', 'double(decimals=2)', 'caption=Кредит->Сума,tdClass=ballance-field');
        $this->FLD('blQuantity', 'double(maxDecimals=3)', 'caption=Салдо->Количество,tdClass=ballance-field');
        $this->FLD('blAmount', 'double(decimals=2)', 'caption=Салдо->Сума,tdClass=ballance-field');
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($mvc->isDetailed()) {
            // Детайлизиран баланс на конкретна аналитична сметка
            $mvc->prepareDetailedBalance($data);
        } else {
            // Обобщен баланс на синтетичните сметки
            $mvc->prepareOverviewBalance($data);
        }
    }
    
    
    /**
     * След извличане на записите от базата данни
     */
    public static function on_AfterPrepareListRecs(core_Mvc $mvc, $data)
    {
        if ($mvc->isDetailed()) {
            if($data->groupingForm->isSubmitted()){
            	$allRecs = $data->qCopy->fetchAll();
            	$mvc->doGrouping($data, (array)$data->groupingForm->rec, $data->groupingForm->cmd, $allRecs);
            }
            
            if(!count($data->recs)) return;
            
            $data->allRecs = $data->recs;
            
            $mvc->canonizeSortRecs($data, $mvc->cache);
            
            // Преизчисляваме пейджъра с новия брой на записите
            $conf = core_Packs::getConfig('acc');
            
            $count = 0;
            $Pager = cls::get('core_Pager', array('itemsPerPage' => $conf->ACC_DETAILED_BALANCE_ROWS));
            $Pager->itemsCount = count($data->recs);
            $Pager->calc();
            $data->pager = $Pager;
            
            $start = $data->pager->rangeStart;
            $end = $data->pager->rangeEnd - 1;
           
            // Махаме тези записи които не са в диапазона на страницирането
            foreach ($data->recs as $id => $rec1){
            	if(!($count >= $start && $count <= $end)){
            		unset($data->recs[$id]);
            	}
            	$count++;
            }
        }
    }
    
    
    /**
     * Канонизира и подрежда записите
     */
    public function canonizeSortRecs(&$data, $cache)
    {
    	// Обхождаме записите, създаваме уникално поле за сортиране
    	foreach ($data->recs as $id => &$rec){
    		$sortField = '';
    		foreach (range(1, 3) as $i){
    	
    			if(empty($data->listFields["ent{$i}Id"])) continue;
    	
    			if(isset($rec->{"grouping{$i}"})){
    				$sortField .= $rec->{"grouping{$i}"};
    			} else {
    				$sortField .= $cache[$rec->{"ent{$i}Id"}];
    			}
    		}
    		 
    		$rec->sortField = strtolower(str::utf2ascii($sortField));
    	}
    	
    	// Сортираме записите според полето за сравнение
    	usort($data->recs, array($this, "sortRecs"));
    }
    
    
    /**
     * Филтриране на записите по код
     * Подрежда кодовете или свойствата във възходящ ред.
     * Ако първата аналитичност са еднакви, сравнява по кодовете на втората ако и те по тези на третата
     */
    private function sortRecs($a, $b)
    {
    	if($a->sortField == $b->sortField) return 0;
    	 
    	return (strnatcasecmp($a->sortField, $b->sortField) < 0) ? -1 : 1;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    static function on_AfterPrepareListRows($mvc, $data)
    {
        // При детайлна справка, и потребителя няма роли acc, ceo скриваме
        // записите до които няма достъп
        if($mvc->isDetailed() && !haveRole('ceo,acc')){
            $recs = &$data->recs;
            $rows = &$data->rows;
            
            if(empty($rows)) return;
            
            foreach ($rows as $id => $row){
                
                // Ако потребителя не може да вижда записа него показваме
                if(!$mvc->canReadRecord($recs[$id])){
                    unset($rows[$id]);
                }
            }
        }
        
        $mvc->prepareSummary($data);
    }
    
    
    /**
     * Подготвя обобщаващите данни
     */
    private function prepareSummary(&$data)
    {
        if(!count($data->recs)) return;
        
        if(!$this->isDetailed()) return;
        
        $recs = $data->allRecs;
        
        $arr = array('debitAmount', 'creditAmount', 'baseAmount', 'blAmount');
        $debitQuantity = $debitAmount = $creditAmount = $baseQuantity = $baseAmount = $blAmount = 0;
        
        foreach ($recs as $rec){
            foreach ($arr as $param){
                ${$param} += $rec->{$param};
            }
        }
        
        $data->summary = new stdClass();
        
        foreach ($arr as $param){
            $data->summary->$param = $this->getFieldType($param)->toVerbal(${$param});
            
            if(${$param} < 0){
                $data->summary->$param = "<span style='color:red'>{$data->summary->$param}</span>";
            }
        }
    }
    
    
    /**
     * Дали потребителя може да вижда детайл от баланса, може ако има достъп
     * до всички негови пера
     *
     * @param stdClass $rec - запис от модела
     * @return boolean
     */
    private function canReadRecord($rec)
    {
        foreach (range(1, 3) as $i){
            $ent = $rec->{"ent{$i}Id"};
            
            if(empty($ent)) continue;
            
            $itemRec = acc_Items::fetch($ent, 'classId,objectId');
           
            if($itemRec->classId){
                $AccRegMan = cls::get($itemRec->classId);
                
                if($AccRegMan->haveRightFor('single', $itemRec->objectId)){
                    return TRUE;
                }
            }
        }
        
        return FALSE;
    }
    
    
    /**
     * Групира или филтрира записите на баланс по зададен признак и/или пера.
     *
     * @param stdClass $data
     */
    public function doGrouping(&$data, $by, $cmd, $allRecs)
    {
        $show = $groupedBy = array();
        $Varchar = cls::get('type_Varchar');
        
        // Намираме избраните свойства/пера
        foreach (range(1, 3) as $i){
            if($by["grouping{$i}"]){
                $show[$i] = $by["grouping{$i}"];
            }
            
            if($by["feat{$i}"]){
                $groupedBy[$i] = $by["feat{$i}"];
                if($by["feat{$i}"] == '*'){
                	$data->listFields["ent{$i}Id"] = tr("[По пера]");
                } else {
                	$data->listFields["ent{$i}Id"] = $Varchar->toVerbal($groupedBy[$i]);
                }
            }
        }
        
        // Ако няма филтриране или групиране не правим нищо
        if(!count($show) && !count($groupedBy)) return;
        
        // Ако няма записи не правим нищо
        if(!count($allRecs)) return;
        
        $data->recs = $allRecs;
        
        // Извличаме всички записани свойства за показваните пера
        $featuresArr = acc_Features::getFeaturesByItems();
        
        // Отделят се само записите с посочените пера
        foreach ($data->recs as $id => $rec){
            foreach (range(1, 3) as $i){
                if(isset($show[$i]) && $rec->{"ent{$i}Id"} != $show[$i]){
                    unset($data->recs[$id]);
                    break;
                }
            }
        }
        
        // Ако филтрираме
        if($cmd == 'default'){
            foreach ($data->recs as $id => $rec){
                foreach (range(1, 3) as $i){
                    
                    // Ако групираме по свойство (и то не е '*'), и перото на тази позиция няма това свойство, премахваме реда
                    if(isset($groupedBy[$i])){
                    	if($groupedBy[$i] != '*' && empty($featuresArr[$rec->{"ent{$i}Id"}][$groupedBy[$i]])){
                            unset($data->recs[$id]);
                            break;
                        }
                    }
                }
            }
        } else {
        	
            // Ако групираме
            foreach ($data->recs as $id => $rec){
                foreach (range(1, 3) as $i){
                	
                    // Намираме с-та на избраното свойство ако има такова
                    if(isset($groupedBy[$i])){
                    	if($groupedBy[$i] == '*'){
                    		
                    		// Ако групираме със специалния символ '*', с-та на свойството е името на перото
                    		$rec->{"grouping{$i}"} = acc_Items::getVerbal($rec->{"ent{$i}Id"}, 'title');
                    	}elseif(isset($featuresArr[$rec->{"ent{$i}Id"}][$groupedBy[$i]])){
                    		
                    		// Ако има свойство за това перо, взимаме стойността му
                            $rec->{"grouping{$i}"} = $featuresArr[$rec->{"ent{$i}Id"}][$groupedBy[$i]];
                        } else {
                        	
                        	// Ако няма отива към "Други"
                            $rec->{"grouping{$i}"} = 'others';
                        }
                    }
                }
            }
            
            //  Колонките, за които няма избрано нито перо, нито свойство изчезват
            foreach (range(1, 3) as $i){
                if(empty($show[$i]) && empty($groupedBy[$i])){
                    unset($data->listFields["ent{$i}Id"]);
                    foreach ($data->recs as $id => &$rec){
                    	unset($rec->{"ent{$i}Id"});
                    }
                }
            }
            
            unset($data->listFields['history'], $data->listFields['baseQuantity'], $data->listFields['debitQuantity'], $data->listFields['creditQuantity'], $data->listFields['blQuantity']);
            
            $data->groupByFeature = TRUE;
        }
        
        // Сумиране на еднаквите редове
        $groupedRecs = $groupedIdx = array();
        
        foreach ($data->recs as $rec1) {
            if($cmd == 'default'){
                
                // Ако филтрираме уникалноста са перата и избраните св-ва
                $r = &$groupedIdx[$rec1->ent1Id][$rec1->ent2Id][$rec1->ent3Id][$rec1->grouping1][$rec1->grouping2][$rec1->grouping3];
            } else {
            	
                // Ако групираме това са избраните свойства
                $r = &$groupedIdx[$rec1->grouping1][$rec1->grouping2][$rec1->grouping3];
            }
            
            if (!isset($r)) {
                $r = new stdClass();
                $groupedRecs[] = &$r;
            }
            
            // Групиране на данните
            foreach (array('ent1Id', 'ent2Id', 'ent3Id', 'accountNum', 'balanceId') as $fld){
                $r->$fld = $rec1->$fld;
            }
           
            // Събираме числовите данни
            foreach (array('baseQuantity', 'baseAmount', 'debitQuantity', 'debitAmount', 'creditQuantity', 'creditAmount', 'blQuantity', 'blAmount') as $fld){
                if (!is_null($rec1->$fld)) {
                    $r->$fld += $rec1->$fld;
                }
            }
            
            foreach (array('grouping1', 'grouping2', 'grouping3') as $gr){
                if(isset($rec1->$gr)){
                    $r->$gr = $rec1->$gr;
                }
            }
        }
        
        // Заменяме записите с новите
        $data->recs = $groupedRecs;
        
    }
    
    
    /**
     * Подготовка за обобщен баланс на синтетичните сметки
     *
     * @param StdClass $data
     */
    private function prepareOverviewBalance($data)
    {
        $data->query->where('#ent1Id IS NULL AND #ent2Id IS NULL AND #ent3Id IS NULL');
        $data->query->orderBy('#accountNum', 'ASC');
        
        $data->listFields = array(
            'accountNum' => 'Сметка->№',
            'accountId' => 'Сметка->Име',
            'debitAmount' => 'Обороти->Дебит',
            'creditAmount' => 'Обороти->Кредит',
            'baseAmount' => 'Салдо->Начално',
            'blAmount' => 'Салдо->Крайно',
        );
    }
    
    
    /**
     * Подготовка за детайлизиран баланс на конкретна аналитична сметка,
     * евентуално групиран по зададени признаци
     *
     * @param StdClass $data
     */
    private function prepareDetailedBalance($data)
    {
        // Кода по-надолу има смисъл само за детайлизиран баланс, очаква да има фиксирана
        // сметка.
        expect($this->Master->accountRec);
        
        $data->query->where("#accountId = {$this->Master->accountRec->id}");
        $data->query->where('#ent1Id IS NOT NULL OR #ent2Id IS NOT NULL OR #ent3Id IS NOT NULL');
        $data->qCopy = clone $data->query;
        
        $data->groupingForm = $this->getGroupingForm($data->masterId, $data->query);
        
        if(count($this->cache)){
            $iQuery = acc_Items::getQuery();
            $iQuery->show("num");
            $iQuery->in('id', $this->cache);
            
            while($iRec = $iQuery->fetch()){
                $this->cache[$iRec->id] = $iRec->num;
            }
        }
       
        // Извличаме записите за номенклатурите, по които е разбита сметката
        $listRecs = array();
        
        foreach (range(1, 3) as $i) {
            if ($this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($this->Master->accountRec->{"groupId{$i}"});
            }
        }
        
        $data->listFields = array();
        $data->listFields['history'] = ' ';
        
        /**
         * Указва дали редом с паричните стойности да се покажат и колони с количества.
         *
         * Количествата има смисъл да се виждат само за сметки, на които поне една от
         * аналитичностите е измерима.
         *
         * @var boolean true - показват се и количества, false - не се показват
         */
        $bShowQuantities = FALSE;
        
        foreach ($listRecs as $i => $listRec) {
            $bShowQuantities = $bShowQuantities || ($listRec->isDimensional == 'yes');
            $data->listFields["ent{$i}Id"] = "|*" . acc_Lists::getVerbal($listRec, 'name');
        }
        
        if ($bShowQuantities) {
            $data->listFields += array(
                'baseQuantity' => 'Начално салдо->ДК->К-во',
                'baseAmount' => 'Начално салдо->ДК->Сума',
                'debitQuantity' => 'Обороти->Дебит->К-во',
                'debitAmount' => 'Обороти->Дебит->Сума',
                'creditQuantity' => 'Обороти->Кредит->К-во',
                'creditAmount' => 'Обороти->Кредит->Сума',
                'blQuantity' => 'Крайно салдо->ДК->К-во',
                'blAmount' => 'Крайно салдо->ДК->Сума',
            );
        } else {
            $data->listFields += array(
                'baseAmount' => 'Салдо->Начално',
                'debitAmount' => 'Обороти->Дебит',
                'creditAmount' => 'Обороти->Кредит',
                'blAmount' => 'Салдо->Крайно',
            );
        }
    }
    
    
    /**
     * Лека промяна в детайл-layout-а: лентата с инструменти е над основната таблица, вместо под нея.
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public static function on_AfterRenderDetailLayout($mvc, &$res, $data)
    {
        $res = new ET("
            [#ListToolbar#]</div>
            [#ListSummary#]
            <div class='clearfix21'></div>
            [#ListTable#]
        ");
    }
    
    
    /**
     * Извиква се след подготовката на toolbar-а за табличния изглед
     */
    public static function on_AfterPrepareListToolbar($mvc, &$data)
    {
        $data->toolbar->removeBtn('btnPrint');
    }
    
    
    /**
     * Извиква се след рендиране на Toolbar-а
     */
    public static function on_AfterRenderListToolbar($mvc, &$tpl, $data)
    {
        if ($mvc->isDetailed()) {
            if ($data->groupingForm) {
                if(!$tpl){
                    $tpl = new ET("");
                }
                $tpl->push(('acc/js/balance.js'), 'JS');
                jquery_Jquery::run($tpl, "chosenrefresh();");
                
                $tpl->append($data->groupingForm->renderHtml(), 'ListToolbar');
            }
        }
    }
    
    
    /**
     * Създаване и подготовка на формата за групиране.
     *
     * Формата предлага двойка полета за всяка аналитичност, от първото може да се избира перо от
     * номенклатурата за филтриране а от второто свойство на перото
     *
     * @param int $balanceId ИД на баланса, в контекста на който се случва това
     */
    private function getGroupingForm($balanceId, $query)
    {
        expect($this->Master->accountRec);
        
        static $form;
        
        if (isset($form)) {
            return $form;
        }
        
        $listRecs = array();
        
        foreach (range(1, 3) as $i) {
            if ($groupId = $this->Master->accountRec->{"groupId{$i}"}) {
                $listRecs[$i] = $this->Lists->fetch($groupId);
            }
        }
        
        if(empty($listRecs)) return;
        
        $form = cls::get('core_Form');
        
        // Запомняме кои пера участват в баланса на тази сметка и показваме само тях в списъка
        
        $items = array();
        $cQuery = clone $query;
        $cQuery->show('ent1Id,ent2Id,ent3Id');
        while ($rec = $cQuery->fetch()) {
            foreach (range(1, 3) as $i){
                if(!empty($rec->{"ent{$i}Id"})){
                    $this->cache[$rec->{"ent{$i}Id"}] = $rec->{"ent{$i}Id"};
                    $items[$i][$rec->{"ent{$i}Id"}] = $rec->{"ent{$i}Id"};
                }
            }
        }
        
        $form->method = 'GET';
        $form->class = 'simpleForm';
        $form->fieldsLayout = getTplFromFile("acc/tpl/BalanceFilterFormFields.shtml");
        $form->FNC("accId", 'int', 'silent,input=hidden');
        $form->input("accId", TRUE);
        
        foreach ($listRecs as $i => $listRec) {
            $this->setGroupingForField($i, $listRec, $form, $items[$i]);
        }
        $form->showFields = trim($form->showFields, ',');
        
        $form->input(NULL, TRUE);
        
        if($form->isSubmitted()){
            foreach (range(1, 3) as $i){
                if($form->rec->{"grouping{$i}"} && $form->rec->{"feat{$i}"}){
                    $form->setError("grouping{$i},feat{$i}", "Не може да са избрани едновременно перо и свойтво за една позиция");
                }
            }
        }
        
        $form->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png,style=margin-top:6px;');
        $form->toolbar->addSbBtn('Групирай', 'group', 'id=filterGr', 'ef_icon = img/16/sum2.png,style=margin-top:6px;');
        
        return $form;
    }
    
    
    /**
     * Подготвя полетата за филтриране
     */
    private function setGroupingForField($i, $listRec, &$form, $options)
    {
        $form->formAttr['id'] = 'groupForm';
        
        if(count($options)){
        	$nOptions = array();
        	$iQuery = acc_Items::getQuery();
        	$iQuery->in('id', $options);
        	$iQuery->show('id,title');
        	
        	while($iRec = $iQuery->fetch()){
        		$nOptions[$iRec->id] = $iRec->title;
        	}
        	
        	$options = $nOptions;
        }
        
        if(!count($options)){
            $options = array();
        }
        
        $features = acc_Features::getFeatureOptions(array_keys($options));
        $features = array('' => '') + $features + array('*' => '[По пера]');
        
        $listName = acc_Lists::getVerbal($listRec, 'name');
        $form->fieldsLayout->replace($listName, "caption{$i}");
        $form->FNC("grouping{$i}", 'key(mvc=acc_Items,allowEmpty,select=title)', "silent,caption={$listName},width=330px,input,class=balance-grouping");
        $form->FNC("feat{$i}", 'varchar', "silent,caption={$listName}->Свойства,width=330px,input,class=balance-feat");
        if(count($options)){
            $form->setOptions("grouping{$i}", $options);
        } else {
            $form->setReadOnly("grouping{$i}");
        }
        
        $form->setOptions("feat{$i}", $features);
        $form->showFields .= "grouping{$i},";
        $form->showFields .= "feat{$i}," ;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $masterRec = $mvc->Master->fetch($rec->balanceId);
        
        if($mvc->isDetailed()){
            
            $histImg = ht::createElement('img', array('src' => sbf('img/16/clock_history.png', '')));
            $url = array('acc_BalanceHistory', 'History', 'fromDate' => $masterRec->fromDate, 'toDate' => $masterRec->toDate, 'accNum' => $rec->accountNum, 'ent1Id' => $rec->ent1Id, 'ent2Id' => $rec->ent2Id, 'ent3Id' => $rec->ent3Id);
            $row->history = ht::createLink($histImg, $url, NULL, 'title=Подробен преглед');
            $row->history = "<span style='margin:0 4px'>{$row->history}</span>";
            
            foreach (range(1, 3) as $i) {
                if(isset($rec->{"grouping{$i}"})){
                    $row->{"ent{$i}Id"} = $rec->{"grouping{$i}"};
                    
                    if($row->{"ent{$i}Id"} == 'others'){
                        $row->{"ent{$i}Id"} = "<i>" . tr('Други') . "</i>";
                    }
                }
            }
        } else {
            $row->ROW_ATTR['class'] .= ' level-' . strlen($rec->accountNum);
            $row->accountId = acc_Balances::getAccountLink($rec->accountId, $masterRec, FALSE, TRUE);
        }
    }
    
    
    /**
     * Дали разглеждаме детайлизираната справка
     */
    private function isDetailed()
    {
        return !empty($this->Master->accountRec);
    }
    
    
    /**
     * Записва баланса в таблицата
     */
    public function saveBalance($balanceId)
    {
        $toSave = array();
    	if(count($this->balance)) {
            foreach ($this->balance as $accId => $l0) {
                foreach ($l0 as $ent1 => $l1) {
                    foreach ($l1 as $ent2 => $l2) {
                        foreach ($l2 as $ent3 => $rec) {
                            $rec['balanceId'] = $balanceId;
                            
                            // Ако има сума закръгляме я до втория знак преди запис
                            foreach (array('blAmount', 'baseAmount') as $fld){
                            	if(!is_null($rec[$fld])){
                            		$rec[$fld] = round($rec[$fld], 2);
                            	}
                            }
                            
                            // Закръгляме количествата само ако закръглени равнят на нула
                            foreach (array('blQuantity', 'baseQuantity') as $fld){
                            	if(!is_null($rec[$fld])){
                            		if(!is_null($rec[$fld]) && round($rec[$fld], 8) == 0){
                            			$rec[$fld] = round($rec[$fld], 8);
                            		}
                            	}
                            }
                            
                            $toSave[] = (object)$rec;
                        }
                    }
                }
            }
        }
        
        // Записваме всички данни на веднъж
        $this->saveArray($toSave);
        
        // Изтриваме запаметените изчислени данни
        unset($this->balance, $this->strategies);
    }
    
    
    /**
     * Зарежда в сингълтона баланса с посоченото id
     */
    public function loadBalance($balanceId, $isMiddleBalance = FALSE, $accs = NULL, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {
        $query = $this->getQuery();
       
        static::filterQuery($query, $balanceId, $accs, $itemsAll, $items1, $items2, $items3);
        
        // Да се пропускат записите с нулево крайно салдо, при зареждането на не-междинен баланс
        if(!$isMiddleBalance){
        	$query->where('#blQuantity != 0 OR #blAmount != 0');
        }
        
        while ($rec = $query->fetch()) {
            $accId = $rec->accountId;
            $ent1Id = !empty($rec->ent1Id) ? $rec->ent1Id : null;
            $ent2Id = !empty($rec->ent2Id) ? $rec->ent2Id : null;
            $ent3Id = !empty($rec->ent3Id) ? $rec->ent3Id : null;
            
            if ($strategy = $this->getStrategyFor($accId, $ent1Id, $ent2Id, $ent3Id)) {
                
                // "Захранваме" обекта стратегия с количество и сума, ако к-то е неотрицателно
                if($rec->blQuantity >= 0){
                    $strategy->feed($rec->blQuantity, $rec->blAmount);
                }
            }
            
            $b = &$this->balance[$accId][$ent1Id][$ent2Id][$ent3Id];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            
            if($isMiddleBalance){
            	
            	// Ако зареждаме междинен баланс взимаме и неговия дебитен/кредитен оборот
            	$this->inc($b['debitQuantity'], $rec->debitQuantity);
            	$this->inc($b['debitAmount'], $rec->debitAmount);
            	$this->inc($b['creditQuantity'], $rec->creditQuantity);
            	$this->inc($b['creditAmount'], $rec->creditAmount);
            	
            	$b['baseQuantity'] += $rec->baseQuantity;
            	$b['baseAmount']   += $rec->baseAmount;
            	
            } else {
            	
            	// Ако не зареждаме междинен баланс взимаме само  крайното му салдо като начално
            	$b['baseQuantity'] += $rec->blQuantity;
            	$b['baseAmount']   += $rec->blAmount;
            }
            
            $b['blQuantity'] += $rec->blQuantity;
            $b['blAmount'] += $rec->blAmount;
        }
    }
    
    
    /**
     * Изчислява стойността на счетоводен баланс за зададен период от време.
     *
     * @param string $from дата в MySQL формат
     * @param string $to дата в MySQL формат
     */
    public function calcBalanceForPeriod($from, $to)
    {
        $JournalDetails = &cls::get('acc_JournalDetails');
        
        $query = $JournalDetails->getQuery();
        acc_JournalDetails::filterQuery($query, $from, $to);
        $query->orderBy('valior,id', 'ASC');
        $recs = $query->fetchAll();
        
        // Дигаме времето за изпълнение на скрипта пропорционално на извлечените записи
        $timeLimit = ceil(count($recs) / 3000) * 20;
        if($timeLimit != 0){
        	core_App::setTimeLimit($timeLimit);
        }
        
        if(count($recs)){
            
            // Захранваме стратегиите при нужда
            foreach ($recs as $rec){
                $this->feedStrategy($rec);
            }
            
            foreach ($recs as $rec){
                $this->calcAmount($rec);
                
                $update = $this->updateJournal($rec);
                
                $this->addEntry($rec, 'debit');
                $this->addEntry($rec, 'credit');
                
                // Обновява се записа само ако има промяна с цената
                if($update){
                    $JournalDetails->save_($rec);
                }
            }
        }
    }
    
    
    /**
     * Проверява дали сумата на записа се различава от тази по стратегия
     * Ако не участват сметки по стратегия или няма променени цени по стратегия
     * не се прави промяна на записа
     */
    private function updateJournal(&$rec)
    {
    	$res = FALSE;
    	 
    	// Обхождаме дебита и кредита
    	foreach (array('debit', 'credit') as $type){
    		$quantityField = "{$type}Quantity";
    		$priceField = "{$type}Price";
    
    		// Ако има количество
    		if($rec->{$quantityField}){
    			
    			// Изчисляваме цената
    			@$price = round($rec->amount / $rec->{$quantityField}, 4);
    			
    			// Ако изчислената сума е различна от записаната в журнала
    			if(trim($rec->{$priceField}) != trim($price)){
    				
    				// Ако няма цена на записа
    				if(!$rec->amount){
    					
    					// Намираме последното перо от тази страна
    					$lastItem = NULL;
    					foreach (array(3, 2, 1) as $i){
    						if(!empty($rec->{"{$type}Item{$i}"})){
    							$lastItem = $rec->{"{$type}Item{$i}"};
    							break;
    						}
    					}
    					
    					// Ако има такова перо
    					if(!empty($lastItem)){
    						$itemRec = acc_Items::fetch($lastItem, 'classId,objectId');
    						
    						// И има интерфейс за дефолт цена
    						if(cls::haveInterface('acc_RegistryDefaultCostIntf', $itemRec->classId)){
    							
    							// Извличаме дефолт цената му според записа
    							$Register = cls::get($itemRec->classId);
    							$defCost = $Register->getDefaultCost($itemRec->objectId);
    							
    							// Присвояваме дефолт сумата за сума на записа, и преизчисляваме цената
    							$rec->amount = $defCost * $rec->{$quantityField};
    							@$price = round($rec->amount / $rec->{$quantityField}, 4);
    						}
    					}
    				}
    				
    				// Презаписваме цената
    				$rec->{$priceField} = $price;
    				$res = TRUE;
    			}
    		}
    	}
    	 
    	return $res;
    }
    
    
    /**
     * Захранване на стратегията
     */
    private function feedStrategy($rec)
    {
        $debitStrategy = $creditStrategy = NULL;
        
        // Намираме стратегиите на дебит и кредит с/ките (ако има)
        $debitStrategy = $this->getStrategyFor($rec->debitAccId, $rec->debitItem1, $rec->debitItem2, $rec->debitItem3);
        $creditStrategy = $this->getStrategyFor($rec->creditAccId, $rec->creditItem1, $rec->creditItem2, $rec->creditItem3);
        
        // Ако кредитната сметка е със стратегия и е пасивна, захранваме я с данните от кредита
        if ($creditStrategy) {
            $creditType = $this->Accounts->getType($rec->creditAccId);
            
            if($creditType == 'passive'){
                $creditStrategy->feed($rec->creditQuantity, $rec->amount);
            }
        }
        
        // Ако дебитната сметка е със стратегия и е активна, захранваме я с данните от дебита
        if ($debitStrategy) {
            $debitType = $this->Accounts->getType($rec->debitAccId);
            
            if($debitType == 'active'){
                $debitStrategy->feed($rec->debitQuantity, $rec->amount);
            }
        }
    }
    
    
    /**
     * Попълва с адекватна стойност с полето $rec->amount
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     */
    private function calcAmount($rec)
    {
        $debitStrategy = $creditStrategy = NULL;
        
        // Намираме стратегиите на дебит и кредит сметките (ако има)
        $debitStrategy = $this->getStrategyFor($rec->debitAccId, $rec->debitItem1, $rec->debitItem2, $rec->debitItem3);
        $creditStrategy = $this->getStrategyFor($rec->creditAccId, $rec->creditItem1, $rec->creditItem2, $rec->creditItem3);
        
        // Ако има кредитна стратегия и тя е активна, опитваме се да извлечем цената според стратегията
        if ($creditStrategy) {
            $creditType = $this->Accounts->getType($rec->creditAccId);
            
            if($creditType == 'active'){
            	$amount = $creditStrategy->consume($rec->creditQuantity);
            	if (!is_null($amount)) {
                    $rec->amount = $amount;
                }
            }
        }
        
        // Ако има дебитна стратегия и тя е пасивна, опитваме се да извлечем цената според стратегията
        if($debitStrategy) {
            $debitType = $this->Accounts->getType($rec->debitAccId);
            
            if($debitType == 'passive'){
            	$amount = $debitStrategy->consume($rec->debitQuantity);
            	if (!is_null($amount)) {
                    $rec->amount = $amount;
                }
            }
        }
    }
    
    
    /**
     * Взима стратегията за посочения обект
     */
    private function &getStrategyFor($accountId, $ent1Id, $ent2Id, $ent3Id)
    {
        $e1 = !empty($ent1Id) ? $ent1Id : null;
        $e2 = !empty($ent2Id) ? $ent2Id : null;
        $e3 = !empty($ent3Id) ? $ent3Id : null;
        
        $strategy = NULL;
        
        if (isset($this->strategies[$accountId][$e1][$e2][$e3])) {
            // Имаме вече създаден обект-стратегия
            $strategy = $this->strategies[$accountId][$e1][$e2][$e3];
        } elseif (isset($this->strategies[$accountId]) &&
            $this->strategies[$accountId] === false) {
            // Тази сметка вече е била "питана" за стратегия (дебитна или кредитна) и
            // резултатът е бил отрицателен. За това си спестяваме ново питане - гарантирано е, 
            // че отговорът отново ще бъде същият.
            $strategy = FALSE;
        } elseif ($strategy = $this->Accounts->createStrategyObject($accountId)) {
            // Има стратегия - записваме инстанцията й.
            $this->strategies[$accountId][$e1][$e2][$e3] = &$strategy;
        } else {
            // Няма стратегия. И това не зависи от перата. За да спестим бъдещи извиквания,
            // записваме false
            $this->strategies[$accountId] = FALSE;
        }
        
        return $strategy;
    }
    
    
    /**
     * Добавя дебитната или кредитната част на ред от транзакция (@see acc_JournalDetails)
     * в баланса
     *
     * @param stdClass $rec запис от модела @link acc_JournalDetails
     * @param string $type 'debit' или 'credit'
     */
    private function addEntry($rec, $type)
    {
        expect(in_array($type, array('debit', 'credit')));
        
        $quantityField = "{$type}Quantity";
        
        $sign = ($type == 'debit') ? 1 : -1;
        
        $accId = $rec->{"{$type}AccId"};
        
        $ent1Id = !empty($rec->{"{$type}Item1"}) ? $rec->{"{$type}Item1"} : NULL;
        $ent2Id = !empty($rec->{"{$type}Item2"}) ? $rec->{"{$type}Item2"} : NULL;
        $ent3Id = !empty($rec->{"{$type}Item3"}) ? $rec->{"{$type}Item3"} : NULL;
        
        if ($ent1Id != NULL || $ent2Id != NULL || $ent3Id != NULL) {
            
            $b = &$this->balance[$accId][$ent1Id][$ent2Id][$ent3Id];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = $ent1Id;
            $b['ent2Id'] = $ent2Id;
            $b['ent3Id'] = $ent3Id;
            
            $this->inc($b[$quantityField], $rec->{$quantityField});
            $this->inc($b["{$type}Amount"], $rec->amount);
            
            $this->inc($b['blQuantity'], $rec->{$quantityField} * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
        }
        
        for ($accNum = $this->Accounts->getNumById($accId); !empty($accNum); $accNum = substr($accNum, 0, -1)) {
            if (!($accId = $this->Accounts->getIdByNum($accNum))) {
                continue;
            }
            
            $b = &$this->balance[$accId][null][null][null];
            
            $b['accountId'] = $accId;
            $b['ent1Id'] = NULL;
            $b['ent2Id'] = NULL;
            $b['ent3Id'] = NULL;
            
            $this->inc($b[$quantityField], $rec->{$quantityField});
            $this->inc($b["{$type}Amount"], $rec->amount);
            $this->inc($b['blQuantity'], $rec->{$quantityField} * $sign);
            $this->inc($b['blAmount'], $rec->amount * $sign);
        }
    }
    
    
    /**
     * Ако вторият аргумент е с празна (empty()) стойност - не прави нищо. В противен случай
     * увеличава стойността на първия аргумент със стойността на втория.
     *
     * Ако $v = '', $add = '', то след $v += $add, $v ще има стойност нула. Целта на този метод
     * е стойността на $v да остане непроменена (празна) в такива случаи.
     *
     * @param number $v
     * @param mixed $add
     */
    private function inc(&$v, $add)
    {
        // Добавяме стойността, само ако не е NULL
    	if (!is_null($add)) {
            $v += $add;
            
            // Машинно закръгляне
            $v = round($v, 9);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * Забранява ръчното манипулиране на записи
     *
     * @param core_Mvc $mvc
     * @param string $requiredRoles
     * @param string $action
     * @param stdClass|NULL $rec
     * @param int|NULL $userId
     */
    static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = NULL, $userId = NULL)
    {
        if (!in_array($action, array('list', 'read', 'history'))){
            $requiredRoles = 'no_one';
        }
        
        if($action == 'history' && isset($rec)){
            if(!haveRole('ceo, acc') && !$mvc->canReadRecord($rec)){
                $requiredRoles = 'no_one';
            }
        }
    }
    
    
    /**
     * Филтрира заявка към модела за показване на определени данни
     *
     * @param core_Query $query - Заявка към модела
     * @param mixed $accs       - списък от систем ид-та на сметките
     * @param mixed $itemsAll   - списък от пера, за които може да са на произволна позиция
     * @param mixed $items1     - списък с пера, от които поне един може да е на първа позиция
     * @param mixed $items2     - списък с пера, от които поне един може да е на втора позиция
     * @param mixed $items3     - списък с пера, от които поне един може да е на трета позиция
     * @return array            - масив със всички извлечени записи
     */
    public static function filterQuery(core_Query &$query, $id, $accs = NULL, $itemsAll = NULL, $items1 = NULL, $items2 = NULL, $items3 = NULL)
    {
        expect($query->mvc instanceof acc_BalanceDetails);
        
        // Трябва да има поне една зададена сметка
        $accounts = arr::make($accs);
        
        if(count($accounts) >= 1){
            foreach ($accounts as $sysId){
            	$accId = acc_Accounts::fetchField("#systemId = '{$sysId}'", 'id');
            	
                $query->orWhere("#accountId = {$accId}");
            }
        }
        
        // ... само детайлите от последния баланс
        $query->where("#balanceId = {$id}");
        
        // Перата които може да са на произволна позиция
        $itemsAll = arr::make($itemsAll);
        
        if(count($itemsAll)){
        	$itemsAll = array_values($itemsAll);
            foreach ($itemsAll as $indexAll => $itemId){
                
                // Трябва да инт число
                expect(ctype_digit($itemId));
                
                // .. и перото да участва на произволна позиция
                $or = ($indexAll == 0) ? FALSE : TRUE;
                $query->where("#ent1Id = {$itemId} || #ent2Id = {$itemId} || #ent3Id = {$itemId}", $or);
            }
        }
        
        // Проверка на останалите параметри от 1 до 3
        foreach (range(1, 3) as $i){
            $var = ${"items{$i}"};
            
            // Ако е NULL продалжаваме
            if(!$var) continue;
            $varArr = arr::make($var);
            
            // За перата се изисква поне едно от тях да е на текущата позиция
            $j = 0;
            
            foreach($varArr as $itemId){
                $or = ($j == 0) ? FALSE : TRUE;
                $query->where("#ent{$i}Id = {$itemId}", $or);
                $j++;
            }
        }
    }
    
    
    /**
     * След рендиране на List Summary-то
     */
    static function on_AfterRenderListSummary($mvc, &$tpl, $data)
    {
        if($data->summary){
            $table = getTplFromFile('acc/tpl/BalanceSummary.shtml');
            $table->placeObject($data->summary);
            
            if(empty($tpl)){
                $tpl = new ET("");
            }
            
            $tpl->append($table, 'ListSummary');
        }
    }
}