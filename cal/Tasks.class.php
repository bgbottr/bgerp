<?php


/**
 * Клас 'cal_Tasks' - Документ - задача
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class cal_Tasks extends embed_Manager
{
    /**
     * Интерфейс на драйверите
     */
    public $driverInterface = 'cal_TaskTypeIntf';
    
    
    /**
     * Име на папката по подразбиране при създаване на нови документи от този тип.
     * Ако стойноста е 'FALSE', нови документи от този тип се създават в основната папка на потребителя
     */
    public $defaultFolder = false;
    
    
    const maxLenTitle = 120;
    
    
    protected $limitShowMonths = 6;
    
    
    /**
     * Период на показване на чакащи и активни задачи в портала
     */
    protected static $taskShowPeriod = 3;
    
    
    /**
     * Масив със състояниет, за които да се праща нотификация
     *
     * @see planning_plg_StateManager
     */
    public $notifyActionNamesArr = array('active' => 'Активиране',
        'waiting' => 'Паузирана',
        'closed' => 'Приключване',
        'wakeup' => 'Събуждане',
        'stopped' => 'Спиране',
        'rejected' => 'Оттегляне');
    
    
    /**
     * Масив със състояния, за които да се изтрива предишната нотификация
     *
     * @see planning_plg_StateManager
     */
    public $removeOldNotifyStatesArr = array('closed');
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'email_DocumentIntf, doc_DocumentIntf, doc_ContragentDataIntf';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, cal_Wrapper,doc_plg_SelectFolder, doc_plg_Prototype, doc_DocumentPlg, planning_plg_StateManager, plg_Printing, 
    				 doc_SharablePlg, bgerp_plg_Blank, plg_Search, change_Plugin, plg_Sorting, plg_Clone, doc_AssignPlg';
    
    
    /**
     * Какви детайли има този мастер
     */
    public $details = 'cal_TaskConditions';
    
    
    /**
     * Заглавие
     */
    public $title = 'Задачи';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Задача';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'id, title, timeStart, timeEnd, timeDuration, progress, assign=Потребители->Възложени, sharedUsers=Потребители->Споделени';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'title, description';
    
    
    /**
     * Поле в което да се показва иконата за единичен изглед
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Кои полета да се извличат при изтриване
     */
    public $fetchFieldsBeforeDelete = 'id';
    
    
    /**
     * Кой може да променя състоянието?
     */
    public $canChangestate = 'powerUser';
    
    
    /**
     * Кой има право да го чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'powerUser';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'powerUser';
    
    
    /**
     * Кой има право да го види?
     */
    public $canView = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';
    
    
    /**
     * Кой има достъп до сингъла
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Кой може да променя активирани записи
     */
    public $canChangerec = 'powerUser, admin, ceo';
    
    
    /**
     * Кой може да възлага задачата
     */
    public $canAssign = 'powerUser';
    
    
    /**
     * Кой може да възлага задачата
     */
    public $canActivate = 'powerUser';
    
    
    /**
     * Икона за единичния изглед
     */
    public $singleIcon = 'img/16/task-normal.png';
    
    
    /**
     * Шаблон за единичния изглед
     */
    public $singleLayoutFile = 'cal/tpl/SingleLayoutTasks.shtml';
    
    
    /**
     * Списък с корици и интерфейси, където може да се създава нов документ от този клас
     */
    public $coversAndInterfacesForNewDoc = '*';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Tsk';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '1.3|Общи';
    
    
    /**
     * Изгледи
     */
    public static $view = array('WeekHour' => 1,
        'WeekHour4' => 2,
        'WeekHour6' => 3,
        'WeekDay' => 4,
        'Months' => 5,
        'YearWeek' => 6,
        'Years' => 7,);
    
    
    /**
     * Поле за филтър по дата - начало
     */
    public $filterFieldDateFrom = 'timeStart';
    
    
    /**
     * Поле за филтър по дата - край
     */
    public $filterFieldDateTo = 'timeEnd';
    
    
    /**
     * Да се показва антетка
     */
    public $showLetterHead = true;
    
    
    /**
     * Предефинирани подредби на листовия изглед
     */
    public $listOrderBy = array(
        'endStart' => array('Всички стари->нови', 'all=Всички стари->нови'),
        'startEnd' => array('Всички нови->стари', 'all=Всички нови->стари'),
        'onStart' => array('По началото', 'timeStart=По началото'),
        'onEnd' => array('По края', 'timeEnd=По края'),
        'noStartEnd' => array('Без начало и край', 'noStartEnd=Без начало и край'),
    );
    
    
    /**
     * Полета, които при клониране да не са попълнени
     *
     * @see plg_Clone
     */
    public $fieldsNotToClone = 'timeStart,timeDuration,timeEnd,expectationTimeEnd, expectationTimeStart, expectationTimeDuration,timeClosed';
    
    
    public $canPending = 'powerUser';
    
    
    /**
     * Кой може да добавя външен сигнал?
     */
    public $canNew = 'every_one';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('title', 'varchar(128)', 'caption=Заглавие,width=100%,changable,silent');
        
        $this->FLD('description', 'richtext(bucket=calTasks, passage=Общи)', 'caption=Описание,changable');
        
        // Споделяне
        $this->FLD('sharedUsers', 'userList', 'caption=Споделяне->Потребители,changable,autohide');
        
        // Приоритет
        $this->FLD(
            'priority',
            'enum(normal=Нормален,
                                     low=Нисък,
                                     high=Спешен,
                                     critical=Критичен)',
            'caption=Споделяне->Приоритет,maxRadio=4,columns=4,notNull,value=normal,autohide,changable'
        );
        
        if (Mode::is('screenMode', 'narrow')) {
            $this->setField('priority', 'columns=2');
            $this->setFieldTypeParams('priority', 'columns=2');
        }
        
        // Начало на задачата
        $this->FLD(
            'timeStart',
            'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00, format=smartTime)',
            'caption=Времена->Начало, silent, changable, tdClass=leftColImportant'
        );
        
        // Продължителност на задачата
        $this->FLD('timeDuration', 'time', 'caption=Времена->Продължителност,changable');
        
        // Краен срок на задачата
        $this->FLD('timeEnd', 'datetime(timeSuggestions=08:00|09:00|10:00|11:00|12:00|13:00|14:00|15:00|16:00|17:00|18:00, format=smartTime, defaultTime=23:59:59)', 'caption=Времена->Край,changable, tdClass=leftColImportant');
        
        // Изпратена ли е нотификация?
        $this->FLD('notifySent', 'enum(no,yes)', 'caption=Изпратена нотификация,notNull,input=none');
        
        // Дали началото на задачата не е точно определено в рамките на деня?
        $this->FLD('allDay', 'enum(no,yes)', 'caption=Цял ден?,input=none');
        
        // Каква част от задачата е изпълнена?
        $this->FLD('progress', 'percent(min=0,max=1,decimals=0)', 'caption=Прогрес,input=none,notNull,value=0');
        
        // Колко време е отнело изпълнението?
        $this->FLD('workingTime', 'time', 'caption=Отработено време,input=none');
        
        // Очакван край на задачата
        $this->FLD('expectationTimeEnd', 'datetime(format=smartTime)', 'caption=Времена->Очакван край,input=none');
        
        // Очаквано начало на задачата
        $this->FLD('expectationTimeStart', 'datetime(format=smartTime)', 'caption=Времена->Очаквано начало,input=none');
        
        // Изчислен старт  на задачата
        $this->FLD('timeCalc', 'datetime(format=smartTime)', 'caption=Времена->Изчислен старт,input=none');
        
        // Точното време на активация на задачата
        $this->FLD('timeActivated', 'datetime(format=smartTime)', 'caption=Времена->Активирана на,input=none');
        
        // Точното време на затваряне
        $this->FLD('timeClosed', 'datetime(format=smartTime)', 'caption=Времена->Затворена на,input=none');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * @param stdClass $data
     */
    public function prepareEditForm_($data)
    {
        if (!Request::get($this->driverClassField) && !Request::get('id')) {
            $sTaskId = cal_TaskType::getClassId();
            
            // Ако е в папка на система, да е избран сигнал
            if ($folderId = Request::get('folderId')) {
                if (doc_Folders::getCover($folderId)->instance instanceof support_Systems) {
                    if (cls::load('support_TaskType', true)) {
                        $sTaskId = support_TaskType::getClassId();
                    }
                }
            }
            
            Request::push(array($this->driverClassField => $sTaskId));
        }
        
        return parent::prepareEditForm_($data);
    }
    
    
    /**
     * Подготовка на формата за добавяне/редактиране
     */
    public static function on_AfterPrepareEditForm($mvc, $data)
    {
        $data->form->setField($mvc->driverClassField, 'input=hidden');
        
        Request::setProtected(array('srcId', 'srcClass'));
        
        $data->form->FNC('SrcId', 'int', 'input=hidden, silent');
        $data->form->FNC('SrcClass', 'varchar', 'input=hidden, silent');
        
        if ($srcId = Request::get('srcId', 'int')) {
            if ($srcClass = Request::get('srcClass')) {
                $data->form->setDefault('SrcId', $srcId);
                $data->form->setDefault('SrcClass', $srcClass);
            }
        }
        
        $cu = core_Users::getCurrent();
        $data->form->setDefault('priority', 'normal');
        
        if ($defUsers = Request::get('DefUsers')) {
            if (type_Keylist::isKeylist($defUsers) && $mvc->fields['assign']->type->toVerbal($defUsers)) {
                $data->form->setDefault('assign', $defUsers);
            }
        }
        
        if (Mode::is('screenMode', 'narrow')) {
            $data->form->fields[priority]->maxRadio = 2;
        }
        
        $rec = $data->form->rec;
        
        if ($rec->allDay == 'yes') {
            list($rec->timeStart, ) = explode(' ', $rec->timeStart);
        }
        
        $data->form->setField('title', 'mandatory');
    }
    
    
    /**
     *
     *
     * @param cal_Tasks $mvc
     * @param object    $res
     * @param object    $form
     *
     * @see doc_plg_SelectFolder
     */
    public static function on_BeforePrepareSelectForm($mvc, &$res, $form)
    {
        if (!$form->rec->{$mvc->driverClassField}) {
            $driverClass = Request::get('driverClass');
            if ($driverClass && cls::load($driverClass, true)) {
                
                if (!isset($form->rec)) {
                    $form->rec = new stdClass();
                }
                
                $form->rec->{$mvc->driverClassField} = $driverClass;
            }
        }
    }
    
    
    /**
     * Връща URL за създаване на задача от съответния тип, със защитени параметри
     *
     * @param int    $rId
     * @param string $clsName
     * @param string $type
     *
     * @return string
     */
    public static function getUrlForCreate($rId, $clsName, $type = 'сигнал')
    {
        $pArr = array('srcId', 'srcClass');
        Request::setProtected($pArr);
        
        $me = cls::get(get_called_class());
        
        $interfaces = core_Classes::getOptionsByInterface($me->driverInterface, 'title');
        
        expect($interfaces);
        
        $driverId = array_search(strtolower($type), array_map('mb_strtolower', $interfaces));
        
        if (!$driverId) {
            $driverId = key($interfaces);
        }
        
        $urlArr = array($me, 'add', 'srcId' => $rId, 'srcClass' => $clsName, 'ret_url' => true);
        
        if ($driverId) {
            $urlArr[$me->driverClassField] = $driverId;
        }
        
        $url = toUrl($urlArr);
        
        Request::removeProtected($pArr);
        
        return $url;
    }
    
    
    /**
     * Подготвяне на вербалните стойности
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $grey = new color_Object('#bbb');
        $blue = new color_Object('#2244cc');
        
        $progressPx = min(100, round(100 * $rec->progress));
        $progressRemainPx = 100 - $progressPx;
        $row->progressBar = "<div style='white-space: nowrap; display: inline-block;'><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$blue}; width:{$progressPx}px;'> </div><div style='display:inline-block;top:-5px;border-bottom:solid 10px {$grey};width:{$progressRemainPx}px;'></div></div>";
        
        if ($rec->timeEnd && ($rec->state != 'closed' && $rec->state != 'rejected')) {
            $remainingTime = dt::mysql2timestamp($rec->timeEnd) - time();
            $rec->remainingTime = self::roundTime($remainingTime);
            
            $typeTime = cls::get('type_Time');
            if ($rec->remainingTime > 0) {
                $row->remainingTime = ' (' . tr('остават') . ' ' . $typeTime->toVerbal($rec->remainingTime) . ')';
            } else {
                $row->remainingTime = ' (' . tr('просрочване с') . ' ' . $typeTime->toVerbal(-$rec->remainingTime) . ')';
            }
        }
        
        $bold = '';
        if ($rec->progress) {
            $grey->setGradient($blue, $rec->progress);
            
            $lastTime = bgerp_Recently::getLastDocumentSee($rec);
            if ($lastTime < $rec->modifiedOn) {
                $bold = 'font-weight:bold;';
            }
        }
        $row->progress = "<span style='color:{$grey};{$bold}'>{$row->progress}</span>";
        
        // Ако имаме само начална дата на задачата
        if ($rec->timeStart && !$rec->timeEnd) {
            // я парвим хипервръзка към календара- дневен изглед
            $row->timeStart = ht::createLink($row->timeStart, array('cal_Calendar', 'day', 'from' => $rec->timeStart, 'Task' => 'true'), null, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
        
        // Ако имаме само крайна дата на задачата
        } elseif ($rec->timeEnd && !$rec->timeStart) {
            // я правим хипервръзка към календара - дневен изглед
            $row->timeEnd = ht::createLink($row->timeEnd, array('cal_Calendar', 'day', 'from' => $rec->timeEnd, 'Task' => 'true'), null, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
        
        // Ако задачата е с начало и край едновременно
        } elseif ($rec->timeStart && $rec->timeEnd) {
            // и двете ги правим хипервръзка към календара - дневен изглед
            $row->timeStart = ht::createLink($row->timeStart, array('cal_Calendar', 'day', 'from' => $rec->timeStart, 'Task' => 'true'), null, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
            $row->timeEnd = ht::createLink($row->timeEnd, array('cal_Calendar', 'day', 'from' => $rec->timeEnd, 'Task' => 'true'), null, array('ef_icon' => 'img/16/calendar5.png', 'title' => 'Покажи в календара'));
        }
    }
    
    
    /**
     * Показване на задачите в портала
     */
    public static function renderPortal($userId = null)
    {
        if (empty($userId)) {
            $userId = core_Users::getCurrent();
        }
        
        // Създаваме обекта $data
        $data = new stdClass();
        
        // Създаваме заявката
        $data->query = self::getQuery();
        
        // Подготвяме полетата за показване
        $data->listFields = 'groupDate,title,progress';
        
        if (Mode::is('listTasks', 'by')) {
            $data->query->where("#createdBy = ${userId}");
        } else {
            $data->query->like('assign', "|{$userId}|");
        }
        
        // Вадим 3 работни дни
        $now = dt::now();
        $before = $after = dt::now(false);
        while (self::$taskShowPeriod--) {
            $before = cal_Calendar::nextWorkingDay($before, null, -1);
            $after = cal_Calendar::nextWorkingDay($after, null, 1);
        }
        $before .= ' 00:00:00';
        $after .= ' 23:59:59';
        
        $data->query->where("#state = 'active'");
        $data->query->orWhere("#state = 'wakeup'");
        $data->query->orWhere(array("(#state = 'waiting' OR #state = 'pending') AND #expectationTimeStart <= '[#1#]' AND #expectationTimeStart >= '[#2#]'", $after, $before));
        $data->query->orWhere(array("(#state = 'closed' OR #state = 'stopped') AND #timeClosed <= '[#1#]' AND #timeClosed >= '[#2#]'", $after, $before));
        
        // Чакащите задачи под определено време да са в началото
        $waitingShow = dt::addSecs(cal_Setup::get('WAITING_SHOW_TOP_TIME'), $now);
        $data->query->XPR('waitingOrderTop', 'datetime', "IF((#state = 'waiting' AND (#expectationTimeStart) AND (#expectationTimeStart <= '{$waitingShow}')), -#expectationTimeStart, NULL)");
        $data->query->orderBy('waitingOrderTop', 'DESC');
        
        // Време за подредба на записите в портала
        $data->query->XPR('orderByState', 'int', "(CASE #state WHEN 'active' THEN 1 WHEN 'wakeup' THEN 1 WHEN 'waiting' THEN 2 WHEN 'pending' THEN 3 ELSE 4 END)");
        $data->query->orderBy('#orderByState=ASC');
        
        // Чакащите задачи, ако имат начало първо по тях да се подреждат, после по последно
        $data->query->XPR('waitingOrder', 'datetime', "IF((#state = 'waiting' AND (#timeStart)), -#timeStart, NULL)");
        
        $data->query->orderBy('waitingOrder', 'DESC');
        $data->query->orderBy('modifiedOn', 'DESC');
        $data->query->orderBy('createdOn', 'DESC');
        
        // Подготвяме навигацията по страници
        self::prepareListPager($data);
        
        // Подготвяме филтър формата
        self::prepareListFilter($data);
        
        // Подготвяме записите за таблицата
        self::prepareListRecs($data);
        
        if (is_array($data->recs)) {
            foreach ($data->recs as &$rec) {
                $rec->savedState = $rec->state;
                $rec->state = '';
            }
        }
        
        // Подготвяме редовете на таблицата
        self::prepareListRows($data);
        
        if (is_array($data->recs)) {
            $me = cls::get(get_called_class());
            $now = dt::now();
            foreach ($data->recs as $id => &$rec) {
                $row = &$data->rows[$id];
                
                $title = str::limitLen(type_Varchar::escape($rec->title), self::maxLenTitle, 20, ' ... ', true);
                
                // Документа да е линк към single' а на документа
                $row->title = ht::createLink($title, self::getSingleUrlArray($rec->id), null, array('ef_icon' => $me->getIcon($rec->id)));
                
                if ($row->title instanceof core_ET) {
                    $row->title->append($row->subTitleDiv);
                } else {
                    $row->title .= $row->subTitleDiv;
                }
                
                if ($rec->savedState) {
                    $sState = $rec->savedState;
                    
                    if (($rec->savedState != 'closed') && ($rec->savedState != 'stopped')) {
                        $tEnd = $rec->timeEnd;
                        if (!$tEnd && $rec->timeStart) {
                            if ($rec->timeStart != $rec->expectationTimeEnd) {
                                $tEnd = $rec->expectationTimeEnd;
                            }
                        }
                        if (($tEnd) && ($tEnd < $now)) {
                            $sState = 'late';
                        }
                    }
                    $row->title = "<div class='state-{$sState}-link'>{$row->title}</div>";
                }
            }
        }
        
        $tpl = new ET('
            [#PortalPagerTop#]
            [#PortalTable#]
        	[#PortalPagerBottom#]
          ');
        
        // Попълваме таблицата с редовете
        
        if ($data->listFilter && $data->pager->pagesCount > 1) {
            $formTpl = $data->listFilter->renderHtml();
            $formTpl->removeBlocks();
            $formTpl->removePlaces();
            $tpl->append($formTpl, 'ListFilter');
        }
        
        $tpl->append(self::renderListPager($data), 'PortalPagerTop');
        $tpl->append(self::renderListTable($data), 'PortalTable');
        $tpl->append(self::renderListPager($data), 'PortalPagerBottom');
        
        return $tpl;
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$res, $data)
    {
        foreach ($data->rows as $id => $row) {
            $row->subTitle = $mvc->getDocumentRow($id)->subTitle;
            $row->subTitleDiv = "<div class='threadSubTitle'>{$row->subTitle}</div>";
            
            if ($row->title instanceof core_ET) {
                $row->title->append($row->subTitleDiv);
            } else {
                $row->title .= $row->subTitleDiv;
            }
        }
    }
    
    
    /**
     * Проверява и допълва въведените данни от 'edit' формата
     */
    protected static function on_AfterInputEditForm($mvc, $form)
    {
        $rec = $form->rec;
        
        $rec->allDay = (strlen($rec->timeStart) == 10) ? 'yes' : 'no';
        
        if ($form->isSubmitted()) {
            if ($form->cmd == 'active') {
                $sharedUsersArr = type_UserList::toArray($form->rec->sharedUsers);
            }
            
            if ($rec->timeStart && $rec->timeEnd && ($rec->timeStart > $rec->timeEnd)) {
                $form->setError('timeEnd', 'Не може крайния срок да е преди началото на задачата');
            }
            
            if ($rec->timeStart && $rec->timeEnd && $rec->timeDuration) {
                $form->setError('timeEnd,timeStart,timeDuration', 'Не може задачата да има едновременно начало, продължителност и край. Попълнете само две от тях');
            }
            
            // при активиране на задачата
            if ($rec->state == 'active') {
                
                // проверява дали сме и задали начало и край
                // или сме и задали начало и продължителност
                if (($rec->timeStart && $rec->timeEnd) || ($rec->timeStart && $rec->timeDuration)) {
                    // ако имаме зададена продължителност
                    if ($rec->timeDuration) {
                        
                        // то изчисляваме края на задачата
                        // като към началото добавяме продължителността
                        $taskEnd = dt::timestamp2Mysql(dt::mysql2timestamp($rec->timeStart) + $rec->timeDuration);
                    } else {
                        $taskEnd = $rec->timeEnd;
                    }
                    
                    // правим заявка към базата
                    $query = self::getQuery();
                    
                    // Търсим всички задачи, които са шернати на споделените и възложените потребители или на текущия потребител
                    // и имат някаква стойност за начало и край
                    // или за начало и продължителност
                    
                    $sharedUsersArr = keylist::toArray($rec->sharedUsers);
                    
                    if ($rec->assign) {
                        $sharedUsersArr += type_Keylist::toArray($rec->assign);
                    }
                    
                    if (empty($sharedUsersArr)) {
                        $cu = core_Users::getCurrent();
                        $sharedUsersArr[$cu] = $cu;
                    }
                    
                    $query->likeKeylist('sharedUsers', $sharedUsersArr);
                    
                    if ($rec->id) {
                        $query->where("#id != {$rec->id}");
                    }
                    
                    $query->where("(#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$rec->timeStart}' AND #timeEnd >= '{$rec->timeStart}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL  AND #timeStart <= '{$rec->timeStart}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$rec->timeStart}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$taskEnd}' AND #timeEnd >= '{$taskEnd}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL AND #timeStart <= '{$taskEnd}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$taskEnd}')");
                    
                    
                    $query->where("#state = 'active'");
                    
                    doc_Threads::restrictAccess($query);
                    
                    $query->orderBy('modifiedOn', 'DESC');
                    
                    $cnt = $query->count();
                    
                    $limit = 10;
                    
                    $query->limit($limit);
                    
                    $link = '';
                    
                    // За всяка една задача отговаряща на условията проверяваме
                    while ($recTask = $query->fetch()) {
                        $link .= ($link) ? '<br>' : '';
                        
                        $link .= ht::createLink($recTask->title, $mvc->getSingleUrlArray($recTask->id), null, 'ef_icon=img/16/task-normal.png');
                    }
                    
                    if ($link) {
                        if ($cnt > 1) {
                            $link = '<br>' . $link;
                        }
                        
                        if ($cnt > $limit) {
                            $link .= '<br>' . ' +' . tr('още') . ': ' . ($cnt - $limit);
                        }
                        
                        $form->setWarning('timeStart, timeDuration, timeEnd', "|Засичане по време с|*: {$link}");
                    }
                }
            }
        }
        
        if ($form->isSubmitted() && ($rec->state != 'draft')) {
            $mvc->calculateExpectationTime($rec);
        }
    }
    
    
    /**
     * След подготовка на сингъла
     */
    public static function on_AfterPrepareSingle($mvc, &$res, $data)
    {
        $pArr = array();

//         if (cal_TaskProgresses::isInstalled()) {
//             $pQuery = cal_TaskProgresses::getQuery();
//             $pQuery->where(array('#taskId = [#1#]', $data->rec->id));
//             $pQuery->orderBy('createdOn', 'ASC');

//             while ($pRec = $pQuery->fetch()) {
//                 $pRow = cal_TaskProgresses::recToVerbal($pRec);

//                 $rowAttr = array();

//                 if ($pRec->state == 'rejected') {
//                     $rowAttr['class'] = 'state-' . $pRec->state;
//                 }

//                 $pArr[] = array('ROW_ATTR' => $rowAttr, 'progress' => $pRow->progress, 'workingTime' => $pRow->workingTime, 'createdOn' => $pRow->createdOn, 'createdBy' => $pRow->createdBy, 'message' => $pRow->message);
//             }
//         }
        
        if ($pClsId = cal_Progresses::getClassId() && $data->rec->containerId) {
            $cQuery = doc_Comments::getQuery();
            $cQuery->where(array("#originId = '[#1#]'", $data->rec->containerId));
            $cQuery->where(array("#driverClass = '[#1#]'", cal_Progresses::getClassId()));
            $cQuery->where("#state != 'draft'");
            $cQuery->where('#activatedOn IS NOT NULL');
            
            $cQuery->orderBy('activatedOn', 'ASC');
            
            $isPartner = haveRole('partner');
            
            while ($cRec = $cQuery->fetch()) {
                
                // Партньорите да не виждат всичките прогреси - само видимите документи
                if ($isPartner) {
                    if (!doc_Comments::haveRightFor('single', $cRec)) {
                        continue;
                    }
                }
                
                $rowAttr = array();
                
                if ($cRec->state == 'rejected') {
                    $rowAttr['class'] = 'state-' . $cRec->state;
                }
                
                $cRow = doc_Comments::recToVerbal($cRec);
                
                $message = $cRow->body;
                $message = strip_tags($message);
                $message = str::limitLen($message, 150);
                
                $pArr[] = array('ROW_ATTR' => $rowAttr, 'links' => doc_Comments::getLinkToSingle($cRec->id, 'id'), 'progress' => $cRow->progress, 'workingTime' => $cRow->workingTime, 'createdOn' => $cRow->createdOn, 'createdBy' => $cRow->createdBy, 'message' => $message);
            }
        }
        
        $data->Progresses = $pArr;
    }
    
    
    /**
     * След рендиране на единичния изглед
     *
     * @param core_Manager $mvc
     * @param core_ET      $tpl
     * @param stdClass     $data
     */
    protected static function on_AfterRenderSingle($mvc, &$tpl, $data)
    {
        if ($data->Progresses) {
            $table = cls::get('core_TableView');
            
            $showFieldArr = array('links', 'createdOn', 'createdBy', 'message', 'progress', 'workingTime');
            
            if (Mode::is('screenMode', 'narrow')) {
//                 $showFieldArr = array('progress', 'createdOn', 'createdBy', 'message', 'workingTime');
            }
            
            $tTpl = $table->get($data->Progresses, $showFieldArr);
            
            $tplx = new ET('<div class="clearfix21 portal" style="margin-top:20px;background-color:transparent;">
                            <div class="legend" style="background-color:#ffc;font-size:0.9em;padding:2px;color:black">' . tr('Прогрес') . '</div>
                            <div class="listRows">
                            [#TABLE#]
                            </div>
	                   </div>
	                ');
            $tplx->replace($tTpl, 'TABLE');
            
            
            $tpl->append($tplx, 'DETAILS');
        }
    }
    
    
    /**
     * Дали може да се добавя прогрес към съответната задача
     *
     * @param stdClass $rec
     *
     * @return bool
     */
    public static function canAddProgress($rec)
    {
        if ($rec->state != 'rejected' && $rec->state != 'draft' && $rec->state != 'template') {
            
            return true;
        }
        
        return false;
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед.
     *
     * @param cal_Tasks $mvc
     * @param stdClass  $data
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if ($mvc->canAddProgress($data->rec)) {
            $data->toolbar->addBtn('Прогрес', array('doc_Comments', 'add', 'originId' => $data->rec->containerId, cls::get('doc_Comments')->driverClassField => cal_Progresses::getClassId(), 'ret_url' => true), 'onmouseup=saveSelectedTextToSession("' . $mvc->getHandle($data->rec->id) . '"), ef_icon=img/16/progressbar.png', "title=Добавяне на прогрес към задачата, row=1");
        }
        
        if (cal_TaskConditions::haveRightFor('add', (object) array('baseId' => $data->rec->id))) {
            $data->toolbar->addBtn('Условие', array('cal_TaskConditions', 'add', 'baseId' => $data->rec->id, 'ret_url' => true), 'ef_icon=img/16/task-option.png, row=2', 'title=Добавяне на зависимост между задачите');
        }
        
        if ($data->rec->timeEnd) {
            $taskEnd = $data->rec->timeEnd;
        } else {
            $taskEnd = dt::now();
        }
        
        if ($data->rec->timeStart) {
            $taskStart = $data->rec->timeStart;
        } else {
            $taskStart = dt::now();
        }
        
        // ако имаме зададена продължителност
        if ($data->rec->timeDuration) {
            if (!$data->rec->timeEnd) {
                // то изчисляваме края на задачата
                // като към началото добавяме продължителността
                $taskEnd = dt::timestamp2Mysql(dt::mysql2timestamp($data->rec->timeStart) + $data->rec->timeDuration);
            }
            
            if (!$data->rec->timeStart) {
                // то изчисляваме началото на задачата
                // като от края на задачата вадим продължителността
                $taskStart = dt::timestamp2Mysql(dt::mysql2timestamp($data->rec->timeEnd) - $data->rec->timeDuration);
            }
        }
        
        // ако имаме бутон "Активиране"
        if (isset($data->toolbar->buttons['Активиране'])) {
            
            // заявка към базата
            $query = self::getQuery();
            
            // при следните условия
            $query->likeKeylist('sharedUsers', $data->rec->sharedUsers);
            $query->where("(#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$taskStart}' AND #timeEnd >= '{$taskStart}')
                            OR
                           (#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$taskEnd}' AND #timeEnd >= '{$taskEnd}')");
            
            // и намерим такъв запис
            if ($query->fetch()) {
                // променяме бутона "Активиране"
                $data->toolbar->buttons['Активиране']->warning = 'По същото време има и други задачи с някои от същите споделени потребители';
            }
        }
    }
    
    
    /**
     * Извиква се преди вкарване на запис в таблицата на модела
     */
    public static function on_BeforeSave($mvc, &$id, $rec, $saveFileds = null)
    {
        if (!$rec->{$mvc->driverClassField}) {
            $rec->{$mvc->driverClassField} = cal_TaskType::getClassId();
        }
        
        if ($rec->__isReplicate) {
            if ($rec->state == 'closed' || $rec->state == 'stopped') {
                if (($rec->brState != 'draft') && ($rec->brState != 'rejected')) {
                    $rec->state = $rec->brState;
                }
                
                if ($rec->state == 'closed' || $rec->state == 'stopped') {
                    $rec->state = 'wakeup';
                }
            }
            
            $rec->progress = null;
            $rec->brState = null;
        }
    }
    
    
    /**
     * Извиква се след вкарване на запис в таблицата на модела
     */
    public static function on_AfterSave($mvc, &$id, $rec, $saveFileds = null)
    {
        $mvc->updateTaskToCalendar($rec->id);
    }
    
    
    /**
     * След изтриване на запис
     */
    public static function on_AfterDelete($mvc, &$numDelRows, $query, $cond)
    {
        foreach ($query->getDeletedRecs() as $id => $rec) {
            
            // изтриваме всички записи за тази задача в календара
            $mvc->updateTaskToCalendar($rec->id);
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие.
     *
     * @param core_Mvc $mvc
     * @param string   $requiredRoles
     * @param string   $action
     * @param stdClass $rec
     * @param int      $userId
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'postpone') {
            if ($rec->id) {
                if ($rec->state !== 'active' || (!$rec->timeStart)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'edit') {
            if ($rec->id) {
                if (!cal_Tasks::haveRightFor('single', $rec)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'changestate') {
            if ($rec->id) {
                if (!$mvc->haveRightFor('single', $rec->id, $userId)) {
                    $requiredRoles = 'no_one';
                }
            }
        }
        
        if ($action == 'edit' && $rec->state == 'pending') {
            $requiredRoles = 'no_one';
        }
    }
    
    
    /**
     * Проверява дали може да се променя записа в зависимост от състоянието на документа
     *
     * @param core_Mvc $mvc
     * @param bool     $res
     * @param string   $state
     */
    public function on_AfterCanChangeRec($mvc, &$res, $rec)
    {
        // Чернова документи не могат да се променят
        if ($res !== false && $rec->state != 'draft') {
            $res = true;
        }
    }
    
    
    /**
     * Функция, която се извиква преди активирането на документа
     *
     * @param cal_Tasks $mvc
     * @param stdClass  $rec
     */
    public static function on_BeforeActivation($mvc, $rec)
    {
        $now = dt::verbal2mysql();
        
        // изчисляваме очакваните времена
        self::calculateExpectationTime($rec);
        
        // проверяваме дали може да стане задачата в активно състояние
        $canActivate = self::canActivateTask($rec);
        
        $sharedUsersArr = keylist::toArray($rec->sharedUsers);
        
        if ($rec->assign) {
            $sharedUsersArr += type_Keylist::toArray($rec->assign);
        }
        
        if ($now >= $canActivate && $canActivate !== null) {
            $rec->timeCalc = $canActivate->calcTime;
        
        // ако не може, задачата става заявка
        } elseif (empty($sharedUsersArr)) {
            $rec->state = 'pending';
            
            core_Statuses::newStatus("|Не е избран потребител. Документа е приведен в състояние 'Заявка'|*");
        } else {
            $rec->state = 'waiting';
        }
        
        if ($rec->id) {
            $mvc->updateTaskToCalendar($rec->id);
        }
    }
    
    
    /**
     * Игнорираме pager-а
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    public static function on_BeforePrepareListPager($mvc, &$res, $data)
    {
        // Ако искаме да видим графиката на структурата
        // не ни е необходимо страницирване
        if (Request::get('Chart') == 'Gantt') {
            // Задаваме броя на елементите в страница
            $mvc->listItemsPerPage = 1000000;
        }
        
        if (Request::get('Ctr') == 'Portal') {
            // Задаваме броя на елементите в страница
            $portalArrange = core_Setup::get('PORTAL_ARRANGE');
            if ($portalArrange == 'recentlyNotifyTaskCal') {
                $mvc->listItemsPerPage = 10;
            } else {
                $mvc->listItemsPerPage = 20;
            }
        }
    }
    
    
    /**
     * Филтър на on_AfterPrepareListFilter()
     * Малко манипулации след подготвянето на формата за филтриране
     *
     * @param core_Mvc $mvc
     * @param stdClass $data
     */
    public static function on_AfterPrepareListFilter($mvc, $data)
    {
        // Добавяме поле във формата за търсене
        $data->listFilter->FNC('from', 'date', 'caption=От,input=none');
        $data->listFilter->FNC('to', 'date', 'caption=До,input=none');
        $data->listFilter->FNC('selectedUsers', 'users', 'caption=Потребител,input,silent,autoFilter');
        $data->listFilter->FNC('Chart', 'varchar', 'caption=Таблица,input=hidden,silent,autoFilter');
        $data->listFilter->FNC('View', 'varchar', 'caption=Изглед,input=hidden,silent,autoFilter');
        $data->listFilter->FNC('stateTask', 'enum(all=Всички,active=Активни,draft=Чернови,waiting=Чакащи,pending=Заявка,actPend=Активни+Чакащи,closed=Приключени)', 'caption=Състояние,input,silent,autoFilter');
        
        $options = array();
        
        // Подготовка на полето за подредба
        foreach ($mvc->listOrderBy as $key => $attr) {
            $options[$key] = $attr[0];
        }
        $orderType = cls::get('type_Enum');
        
        $orderType->options = $options;
        
        $data->listFilter->FNC('order', $orderType, 'caption=Подредба,input,silent', array('removeAndRefreshForm' => 'from|to|selectedUsers|Chart|View|stateTask'));
        
        $data->listFilter->view = 'vertical';
        $data->listFilter->title = 'Задачи';
        $data->listFilter->layout = new ET(tr('|*' . getFileContent('acc/plg/tpl/FilterForm.shtml')));
        
        // по подразбиране е текущия потребител
        if (!$data->listFilter->rec->selectedUsers) {
            $data->listFilter->rec->selectedUsers = keylist::fromArray(arr::make(core_Users::getCurrent('id'), true));
        }
        
        // задачи с всякакъв статус
        if (!$data->listFilter->rec->stateTask) {
            $data->listFilter->rec->stateTask = 'all';
        }
        
        // по критерий "Всички"
        if (!$data->listFilter->rec->order) {
            $data->listFilter->rec->order = 'all';
        }
        
        // филтъра по дата е -1/+1 месец от днещната дата
        $data->listFilter->setDefault('from', date('Y-m-01', strtotime('-1 months', dt::mysql2timestamp(dt::now()))));
        $data->listFilter->setDefault('to', date('Y-m-t', strtotime('+1 months', dt::mysql2timestamp(dt::now()))));
        
        $data->listFilter->toolbar->addSbBtn('Филтрирай', 'default', 'id=filter', 'ef_icon = img/16/funnel.png');
        
        // Показваме само това поле. Иначе и другите полета
        // на модела ще се появят
        if ($data->action === 'list') {
            $data->listFilter->showFields .= 'search,selectedUsers,order, stateTask, ' . $mvc->driverClassField;
        } else {
            $data->listFilter->showFields .= 'selectedUsers';
        }
        $data->listFilter->input('selectedUsers, Chart, View, stateTask, order, ' . $mvc->driverClassField, 'silent');
        
        // размяна на датите във филтъра
        $dateRange = array();
        
        if ($data->listFilter->rec->from) {
            $dateRange[0] = $data->listFilter->rec->from;
        }
        
        if ($data->listFilter->rec->to) {
            $dateRange[1] = $data->listFilter->rec->to;
        }
        
        if (count($dateRange) == 2) {
            sort($dateRange);
        }
        
        // сега
        $now = dt::now();
        
        // поле което прави подредба по очакваните времена
        $data->query->XPR('relativeDate', 'datetime', "if(#expectationTimeStart, #expectationTimeStart, '{$now}')");
        
        // възможност за подредба "най-нови->стари"
        if ($data->listFilter->rec->order == 'endStart') {
            $data->query->orderBy('#state, #priority=DESC, #relativeDate=ASC, #createdOn=DESC');
        
        // възможност за подредба "стари->най-нови"
        } else {
            $data->query->orderBy('#state, #priority=DESC, #relativeDate=DESC, #createdOn=DESC');
        }
        
        if ($data->action === 'list') {
            $chart = Request::get('Chart');
            
            // ако ще подреждаме по "начало" или "край" на задачата ще показваме и филтъра за дата
            if ($data->listFilter->rec->order == 'onStart' || $data->listFilter->rec->order == 'onEnd') {
                $data->listFilter->showFields = 'search,selectedUsers,order, from, to,stateTask';
                $data->listFilter->input('from, to', 'silent');
            }
            
            
            if (($data->listFilter->rec->selectedUsers != 'all_users') && (strpos($data->listFilter->rec->selectedUsers, '|-1|') === false)) {
                $data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #createdBy, '|%')");
                $data->query->orLikeKeylist('sharedUsers', $data->listFilter->rec->selectedUsers);
                $data->query->orLikeKeylist('assign', $data->listFilter->rec->selectedUsers);
            }
            
            if ($data->listFilter->rec->stateTask != 'all' && $data->listFilter->rec->stateTask != 'actPend') {
                $data->query->where(array("#state = '[#1#]'", $data->listFilter->rec->stateTask));
            } elseif ($data->listFilter->rec->stateTask == 'actPend') {
                $data->query->where("#state = 'active' OR #state = 'waiting'");
            }
            
            if ($data->listFilter->rec->order == 'onStart') {
                $data->query->where("(#timeStart IS NOT NULL AND #timeStart <= '{$dateRange[1]}' AND #timeStart >= '{$dateRange[0]}')");
                $data->query->orderBy('#timeStart=ASC,#state=DESC');
            }
            
            if ($data->listFilter->rec->order == 'noStartEnd') {
                $data->query->where('(#timeStart IS NULL AND #timeDuration IS NULL AND #timeEnd IS NULL)');
            }
            
            if ($data->listFilter->rec->order == 'onEnd') {
                $data->query->where("(#timeEnd IS NOT NULL AND #timeEnd <= '{$dateRange[1]}' AND #timeEnd >= '{$dateRange[0]}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL  AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) <= '{$dateRange[1]}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$dateRange[0]}')
	        		              ");
                $data->query->orderBy('#state=DESC,#timeEnd=ASC');
            }
            
            if ($data->listFilter->rec->order == 'onStart') {
                $data->title = 'Търсене на задачи по начало на задачата в периода |*<span class="green">"' .
                    $data->listFilter->getFieldType('from')->toVerbal($data->listFilter->rec->from) . ' -
    			' . $data->listFilter->getFieldType('to')->toVerbal($data->listFilter->rec->to) . '"</span>';
            } elseif ($data->listFilter->rec->order == 'onEnd') {
                $data->title = 'Търсене на задачи по края на задачата в периода |*<span class="green">"' .
                    $data->listFilter->getFieldType('from')->toVerbal($data->listFilter->rec->from) . ' -
    			' . $data->listFilter->getFieldType('to')->toVerbal($data->listFilter->rec->to) . '"</span>';
            } elseif ($data->listFilter->rec->order == 'noStartEnd') {
                $data->title = 'Търсене на задачи |*<span class="green">"' .
                    'без начало и край"</span>';
            } elseif ($data->listFilter->rec->search) {
                $data->title = 'Търсене на задачи отговарящи на |*<span class="green">"' .
                    $data->listFilter->getFieldType('search')->toVerbal($data->listFilter->rec->search) . '"</span>';
            } else {
                //$data->query->where("'{$data->listFilter->rec->selectedUsers}' LIKE CONCAT('%|', #sharedUsers, '|%')");
                $data->title = 'Задачите на |*<span class="green">' .
                    $data->listFilter->getFieldType('selectedUsers')->toVerbal($data->listFilter->rec->selectedUsers) . '</span>';
            }
            
            if ($chart == 'Gantt') {
                $data->query->where("(#timeStart IS NOT NULL AND #timeEnd IS NOT NULL AND #timeStart <= '{$dateRange[1]}' AND #timeEnd >= '{$dateRange[0]}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeDuration IS NOT NULL  AND #timeStart <= '{$dateRange[1]}' AND ADDDATE(#timeStart, INTERVAL #timeDuration SECOND) >= '{$dateRange[0]}')
	        		              OR
	        		              (#timeStart IS NOT NULL AND #timeStart <= '{$dateRange[1]}' AND  #timeStart >= '{$dateRange[0]}')
	        		              ");
            }
            
            // Да може да се филтрира по вида на документа
            if ($data->listFilter->rec && $data->listFilter->rec->{$mvc->driverClassField}) {
                $data->query->where(array("#{$mvc->driverClassField} = '[#1#]'", $data->listFilter->rec->{$mvc->driverClassField}));
            }
        }
    }
    
    
    /**
     * Добавя след таблицата
     *
     * @param core_Mvc $mvc
     * @param StdClass $res
     * @param StdClass $data
     */
    public static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        $currUrl = getCurrentUrl();
        $needOneOnly = 0;
        
        if ($currUrl['Ctr'] == 'cal_Tasks') {
            $chartType = Request::get('Chart');
            
            $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));
            
            $currUrl['Act'] = 'list';
            $currUrl['Chart'] = 'List';
            $tabs->TAB('List', 'Таблица', $currUrl);
            
            $queryClone = clone $data->listSummary->query;
            
            $queryClone->where('#timeStart IS NOT NULL');
            
            if ($queryClone->fetch()) {
                
                // ще може ли да определим типа на Ганта
                $ganttType = self::getGanttTimeType($data);
                
                // и ще имаме активен бутон за него
                $currUrl['Act'] = 'list';
                $currUrl['Chart'] = 'Gantt';
                $currUrl['View'] = $ganttType;
                $tabs->TAB('Gantt', 'Гант', $currUrl);
                
                if ($chartType == 'Gantt') {
                    // и ще го изчертаем
                    $tpl = static::getGantt($data);
                }
                
                // в противен слувачай бутона ще е неактивен
            } else {
                $tabs->TAB('Gantt', 'Гант', '');
            }
            
            $tpl = $tabs->renderHtml($tpl, $chartType);
            
            $mvc->currentTab = 'Задачи';
        }
    }
    
    
    /**
     * Прихваща извикването на AfterInputChanges в change_Plugin
     *
     * @param core_MVc $mvc
     * @param object   $oldRec - Стария запис
     * @param object   $newRec - Новия запис
     */
    public function on_AfterInputChanges($mvc, $oldRec, $newRec)
    {
        // Ако не е обект, а е подаден id
        if (!is_object($newRec)) {
            
            // Опитваме се да извлечем данните
            $newRec = cal_Tasks::fetch($newRec);
        }
        
        // Очакваме да има такъв запис
        expect($newRec, 'Няма такъв запис');
        
        if ($newRec->notifySent === 'yes') {
            $newRec->notifySent = 'no';
        }
        
        // Ако отговаря на условията да се активира, вместо да е заявка
        if ($oldRec->state == 'pending' && $newRec->state == 'pending') {
            $canActivate = $mvc->canActivateTask($newRec);
            if ($canActivate !== null) {
                $now = dt::now();
                if (dt::addDays(-1 * cal_Tasks::$taskShowPeriod, $canActivate) <= $now) {
                    $newRec->state = 'active';
                    $newRec->timeActivated = dt::now();
                }
            }
        }
    }
    
    
    /**
     * Обновява информацията за задачата в календара
     */
    public static function updateTaskToCalendar($id)
    {
        $rec = static::fetch($id);
        
        $events = array();
        
        // Годината на датата от преди 30 дни е начална
        $cYear = date('Y', time() - 30 * 24 * 60 * 60);
        
        // Начална дата
        $fromDate = "{$cYear}-01-01";
        
        // Крайна дата
        $toDate = ($cYear + 2) . '-12-31';
        
        // Префикс на клучовете за записите в календара от тази задача
        $prefix = "TSK-{$id}";
        
        // Подготвяме запис за началната дата
        if ($rec->state == 'active' || $rec->state == 'closed' || $rec->state == 'pending' || $rec->state == 'waiting') {
            $calRec = new stdClass();
            
            setIfNot($calRec->time, $rec->timeStart, $rec->timeCalc, $rec->expectationTimeStart, $calRec->timeEnd);
            
            // В чии календари да влезе?
            $calRec->users = $rec->assign;
            
            if ($calRec->time && $calRec->time >= $fromDate && $calRec->time <= $toDate && $calRec->users) {
                // Ключ на събитието
                $calRec->key = $prefix . '-Start';
                
                if ($rec->timeStart) {
                    // Начало на задачата
                    $calRec->time = $rec->timeStart;
                } elseif ($rec->timeCalc) {
                    $calRec->time = $rec->timeCalc;
                }
                
                //Запис на очакван край в календара
                $calRec->timeEnd = $rec->expectationTimeEnd;
                
                // Дали е цял ден?
                $calRec->allDay = $rec->allDay;
                
                // Икона на записа
                $calRec->type = 'task';
                
                // Заглавие за записа в календара
                $calRec->title = "{$rec->title}";
                
                
                // Статус на задачата
                $calRec->state = $rec->state;
                
                // Какъв да е приоритета в числово изражение
                $calRec->priority = self::getNumbPriority($rec);
                
                // Url на задачата
                $calRec->url = array('cal_Tasks', 'Single', $id);
                
                $events[] = $calRec;
                
                list($startDate, ) = explode(' ', $calRec->time);
            }
        }
        
        // Подготвяме запис за Крайния срок
        if ($rec->state == 'active' || $rec->state == 'waiting' || $rec->state == 'pending') {
            $calRec = new stdClass();
            
            // Време за край на задачата
            setIfNot($calRec->time, $rec->timeEnd, $rec->expectationTimeEnd);
            
            // В чии календари да влезе?
            $calRec->users = $rec->assign;
            
            if ($calRec->time && $calRec->time >= $fromDate && $calRec->time <= $toDate && $calRec->users && (!$startDate || strpos($calRec->time, $startDate) === false)) {
                
                // Ключ на събитието
                $calRec->key = $prefix . '-End';
                
                if ($rec->timeEnd) {
                    // Начало на задачата
                    $calRec->time = $rec->timeEnd;
                }
                
                //Запис на очакван край в календара
                $calRec->timeEnd = $rec->expectationTimeEnd;
                
                // Дали е цял ден?
                $calRec->allDay = $rec->allDay;
                
                // Икона на записа
                $calRec->type = 'end-date';
                
                // Заглавие за записа в календара
                $calRec->title = "Краен срок за \"{$rec->title}\"";
                
                // В чии календари да влезе?
                $calRec->users = $rec->assign;
                
                // Статус на задачата
                $calRec->state = $rec->state;
                
                // Какъв да е приоритета в числово изражение
                $calRec->priority = self::getNumbPriority($rec) - 1;
                
                // Url на задачата
                $calRec->url = array('cal_Tasks', 'Single', $id);
                
                $events[] = $calRec;
            }
        }
        
        // Подготвяме запис за Крайния срок
        if ($rec->state == 'closed') {
            $calRec = new stdClass();
            
            // Време за край на задачата
            setIfNot($calRec->time, $rec->timeClosed);
            
            // В чии календари да влезе?
            $calRec->users = $rec->assign;
            
            if ($calRec->time && $calRec->time >= $fromDate && $calRec->time <= $toDate && $calRec->users && (!$startDate || strpos($calRec->time, $startDate) === false)) {
                
                // Ключ на събитието
                $calRec->key = $prefix . '-End';
                
                if ($rec->timeEnd) {
                    // Начало на задачата
                    $calRec->time = $rec->timeEnd;
                }
                
                // Дали е цял ден?
                $calRec->allDay = $rec->allDay;
                
                // Икона на записа
                $calRec->type = 'end-date';
                
                // Заглавие за записа в календара
                $calRec->title = "Приключена задача \"{$rec->title}\"";
                
                // В чии календари да влезе?
                $calRec->users = $rec->assign;
                
                // Статус на задачата
                $calRec->state = $rec->state;
                
                // Какъв да е приоритета в числово изражение
                $calRec->priority = self::getNumbPriority($rec) - 1;
                
                // Url на задачата
                $calRec->url = array('cal_Tasks', 'single', $id);
                
                $events[] = $calRec;
            }
        }
        
        return cal_Calendar::updateEvents($events, $fromDate, $toDate, $prefix);
    }
    
    
    /**
     * Връща приоритета на задачата за отразяване в календара
     */
    public static function getNumbPriority($rec)
    {
        if ($rec->state == 'active' || $rec->state == 'waiting') {
            switch ($rec->priority) {
                case 'low':
                    $res = 100;
                    break;
                case 'normal':
                    $res = 200;
                    break;
                case 'high':
                    $res = 300;
                    break;
                case 'critical':
                    $res = 400;
                    break;
            }
        } else {
            $res = 0;
        }
        
        return $res;
    }
    
    
    /**
     * Интерфейсен метод на doc_DocumentIntf
     *
     * @param int $id
     *
     * @return stdClass $row
     */
    public function getDocumentRow($id)
    {
        $rec = $this->fetch($id);
        
        $row = new stdClass();
        
        //Заглавие
        $row->title = $this->getVerbal($rec, 'title');
        
        $row->subTitle = '';
        
        if ($rec->progress) {
            $Driver = $this->getDriver($rec->id);
            
            if ($Driver) {
                $progressArr = $Driver->getProgressSuggestions($rec);
            } else {
                $progressArr = array();
            }
            
            Mode::push('text', 'plain');
            $pVal = $this->getVerbal($rec, 'progress');
            Mode::pop('text');
            
            $pValStr = $progressArr[$pVal];
            
            if ($pValStr && ($pValStr != $pVal)) {
                $row->subTitle .= $pValStr;
            } else {
                $row->subTitle .= $this->getVerbal($rec, 'progress');
            }
            
            $row->subTitle .= ' (' . self::getLastProgressAuthor($rec) . ')';
            
            $row->title .= ' (' . $this->getVerbal($rec, 'progress') . ')';
        }
        
        if ($rec->state == 'closed' && $rec->progress != 1) {
            $row->title = '✗ ' . trim($row->title);
        }
        
        if ($rec->state == 'closed' && $rec->progress == 1) {
            $row->title = '✓ ' . trim($row->title);
        }
        
        if ($rec->state == 'stopped' && $rec->progress != 1) {
            $row->title = '॥ ' . trim($row->title);
        }
        
        $usersArr = type_Keylist::toArray($rec->assign);
        if (!empty($usersArr)) {
            $subTitleMaxUsersCnt = 3;
            $othersStr = '';
            if (count($usersArr) > $subTitleMaxUsersCnt) {
                $usersArr = array_slice($usersArr, 0, $subTitleMaxUsersCnt, true);
                $othersStr = ' ' . tr('и др.');
            }
            
            $Users = cls::get('type_userList');
            
            // В заглавието добавяме потребителя
            $row->subTitle .= $row->subTitle ? ' - ' : '';
            $row->subTitle .= $Users->toVerbal(type_userList::fromArray($usersArr));
            $row->subTitle .= $othersStr;
        }
        
        //Състояние
        $row->state = $rec->state;
        
        $date = '';
        
        if ($rec->state == 'active' && $rec->timeEnd) {
            $date = $rec->timeEnd;
        }
        
        if (($rec->state == 'waiting' || $rec->state == 'pending') && $rec->timeStart) {
            $date = $rec->timeStart;
        }
        
        if ($date) {
            $row->subTitle .= $row->subTitle ? ' - ' : '';
            $row->subTitle .= dt::mysql2verbal($date, 'smartTime');
        }
        
        //Създателя
        $row->author = $this->getVerbal($rec, 'createdBy');
        
        //id на създателя
        $row->authorId = $rec->createdBy;
        
        $row->recTitle = $rec->title;
        
        $Driver = $this->getDriver($id);
        if ($Driver) {
            $Driver->prepareDocumentRow($rec, $row);
        }
        
        return $row;
    }
    
    
    /**
     * Връща създателя на последния прогрес
     *
     * @param stdClass $rec
     * @param bool     $removeRejected
     *
     * @return FALSE|stdClass
     */
    public static function getLastProgressAuthor($rec, $removeRejected = true)
    {
        $cQuery = doc_Comments::getQuery();
        $cQuery->where(array("#driverClass = '[#1#]'", cal_Progresses::getClassId()));
        $cQuery->where(array("#originId = '[#1#]'", $rec->containerId));
        $cQuery->where("#state != 'rejected'");
        $cQuery->where("#state != 'draft'");
        $cQuery->orderBy('activatedOn', 'DESC');
        $cQuery->limit(1);
        $cQuery->show('createdBy');
        
        if ($r = $cQuery->fetch()) {
            $author = doc_Comments::getVerbal($r, 'createdBy');
        } else {
            if (cal_TaskProgresses::isInstalled()) {
                // За съвместимост със старите задачи
                $author = cal_TaskProgresses::getLastProgressAuthor($rec->id);
            }
        }
        
        return $author;
    }
    
    
    /**
     * Променяме някои параметри на бутона в папката
     *
     * @param int $folderId
     */
    public function getButtonParamsForNewInFolder($folderId)
    {
        $pArr = array();
        if (cls::load('support_TaskType', true)) {
            $pArr = cls::get('support_TaskType')->getButtonParamsForNewInFolder($folderId);
        }
        
        return $pArr;
    }
    
    
    /**
     * Връща иконата на документа
     *
     * @param int|null $id
     *
     * @return string|null
     */
    public function getIcon_($id = null)
    {
        $rec = self::fetch($id);
        
        $icon = 'img/16/task-' . $rec->priority . '.png';
        
        if (log_Browsers::isRetina()) {
            $tempIcon = 'img/32/task-' . $rec->priority . '.png';
            if (getFullPath($tempIcon)) {
                $icon = $tempIcon;
            }
        }
        
        return $icon;
    }
    
    
    /**
     * Изпращане на нотификации за започването на задачите
     */
    public function cron_SendNotifications()
    {
        // Обикаляме по всички чакащи задачи
        $query = $this->getQuery();
        $query->where("#state = 'waiting'");
        $query->orWhere("#state = 'pending'");
        
        $activatedTasks = array();
        $now = dt::verbal2mysql();
        
        while ($rec = $query->fetch()) {
           
           // Ако веднъж е преизчислено времето да не се прави повторно
            if ($rec->state == 'pending' && !cal_TaskConditions::fetch("#baseId = '{$rec->id}'")) {
                if (!$rec->timeStart && !$rec->timeEnd && !$rec->timeDuration) {
                    if ($rec->expectationTimeStart && $rec->expectationTimeEnd) {
                        continue;
                    }
                }
            }
            
            $oldRec = clone $rec;
            
            // изчисляваме очакваните времена
            self::calculateExpectationTime($rec);
            
            $saveFields = 'expectationTimeStart, expectationTimeEnd';
            
            if ($rec->state == 'waiting') {
                // обновяваме в календара
                self::updateTaskToCalendar($rec->id);
                
                // и проверяваме дали може да я активираме
                $canActivate = self::canActivateTask($rec);
                $exRec = $rec;
                
                if ($canActivate != false) {
                    if ($now >= $canActivate) {
                        $rec->state = 'active';
                        $rec->timeActivated = $now;
                        
                        $activatedTasks[] = $rec;
                        
                        // и да изпратим нотификация на потребителите
                        self::doNotificationForActiveTasks($activatedTasks);
                    } else {
                        $rec->state = $exRec->state;
                    }
                } else {
                    $rec->state = $exRec->state;
                }
                
                $saveFields .= ', state, timeActivated';
            }
            
            // Правим запис, ако има променени полета
            $saveFieldsArr = arr::make($saveFields);
            foreach ($saveFieldsArr as $fName) {
                if ($oldRec->{$fName} != $rec->{$fName}) {
                    self::save($rec, $saveFields);
                    break;
                }
            }
        }
    }
    
    
    /**
     * Сменя задачите в сесията между 'поставените към', на 'поставените от' и обратно
     */
    public function act_SwitchByTo()
    {
        if (Mode::is('listTasks', 'by')) {
            Mode::setPermanent('listTasks', 'to');
        } else {
            Mode::setPermanent('listTasks', 'by');
        }
        
        return new Redirect(array('Portal', 'Show', '#' => Mode::is('screenMode', 'narrow') ? 'taskPortal' : null));
    }
    
    
    /**
     * Изпълнява се след начално установяване
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        $rec = new stdClass();
        $rec->systemId = 'StartTasks';
        $rec->description = 'Известяване за стартирани задачи';
        $rec->controller = 'cal_Tasks';
        $rec->action = 'SendNotifications';
        $rec->period = 1;
        $rec->offset = 0;
        $res .= core_Cron::addOnce($rec);
        
        // Създаваме, кофа, където ще държим всички прикачени файлове в задачи
        $Bucket = cls::get('fileman_Buckets');
        $res .= $Bucket->createBucket('calTasks', 'Прикачени файлове в задачи', null, '104857600', 'user', 'every_one');
    }
    
    
    /**
     * Изчертаване на структурата с данни от базата
     */
    public static function getGantt($data)
    {
        // масив с цветове
        $colors = array('#610b7d',
            '#1b7d23',
            '#4a4e7d',
            '#7d6e23',
            '#33757d',
            '#211b7d',
            '#72142d',
            '#EE82EE',
            '#0080d0',
            '#FF1493',
            '#C71585',
            '#0d777d',
            '#4B0082',
            '#7d1c24',
            '#483D8B',
            '#7b237d',
            '#8B008B',
            '#FFC0CB',
            '#cc0000',
            '#00cc00',
            '#0000cc',
            '#cc00cc',
            '#3366CC',
            '#FF9999',
            '#FF3300',
            '#9999FF',
            '#330033',
            '#003300',
            '#0000FF',
            '#FFFF33',
            '#66CDAA',
            '#98FB98',
            '#4169E1',
            '#D2B48C',
            '#9ACD32',
            '#00FF7F',
            '#4169E1',
            '#EEE8AA',
            '#9370DB',
            '#3CB371',
            '#FFB6C1',
            '#DAA520',
            '#483D8B',
            '#8B0000',
            '#00FFFF',
            '#DC143C',
            '#8A2BE2',
            '#D2B48C',
            '#3CB371',
            '#AFEEEE',
        );
        if ($data->recs) {
            // за всеки едиин запис от базата данни
            foreach ($data->recs as $v => $rec) {
                if ($rec->timeStart) {
                    // ако няма продължителност на задачата
                    if (!$rec->timeDuration && !$rec->timeEnd) {
                        // продължителността на задачата е края - началото
                        $timeDuration = 1800;
                    } elseif (!$rec->timeDuration && $rec->timeEnd) {
                        $timeDuration = dt::mysql2timestamp($rec->timeEnd) - dt::mysql2timestamp($rec->timeStart);
                    } else {
                        $timeDuration = $rec->timeDuration;
                    }
                    
                    // ако нямаме край на задачата
                    if (!$rec->timeEnd) {
                        // изчисляваме края, като начало + продължителност
                        $timeEnd = dt::timestamp2Mysql(dt::mysql2timestamp($rec->timeStart) + $timeDuration);
                    } else {
                        $timeEnd = $rec->timeEnd;
                    }
                    
                    // масив с шернатите потребители
                    $assignedUsersArr[$rec->assign] = keylist::toArray($rec->assign);
                    
                    // Ако имаме права за достъп до сингъла
                    if (cal_Tasks::haveRightFor('single', $rec)) {
                        // ще се сложи URL
                        $flagUrl = 'yes';
                    } else {
                        $flagUrl = false;
                    }
                    
                    // масива със задачите
                    $resTask[] = array(
                        'taskId' => $rec->id,
                        'rowId' => keylist::toArray($rec->assign),
                        'timeline' => array(
                            '0' => array(
                                'duration' => $timeDuration,
                                'startTime' => dt::mysql2timestamp($rec->timeStart))),
                        
                        'color' => $colors[$v % 50],
                        'hint' => $rec->title,
                        'url' => $flagUrl,
                        'progress' => $rec->progress
                    );
                }
            }
            
            if (!empty($assignedUsersArr)) {
                // правим масив с ресурсите или в нашия случай това са потребителитя
                foreach ($assignedUsersArr as $users) {
                    // има 2 полета ид = номера на потребителя
                    // и линк към профила му
                    foreach ($users as $id => $resors) {
                        $link = crm_Profiles::createLink($resors);
                        $resources[$id]['name'] = (string) crm_Profiles::createLink($resors);
                        $resources[$id]['id'] = $resors;
                    }
                }
            }
            
            if (is_array($resources)) {
                // номерирваме ги да почват от 0
                foreach ($resources as $res) {
                    $resUser[] = $res;
                }
            }
            
            $cntResTask = count($resTask);
            
            // правим помощен масив = на "rowId" от "resTasks"
            for ($i = 0; $i < $cntResTask; $i++) {
                $j = 0;
                $rowArr[] = $resTask[$i]['rowId'];
                
                // Проверка дали ще има URL
                if ($resTask[$i]['url'] == 'yes') {
                    // Слагаме линк
                    $resTask[$i]['url'] = toUrl(array('cal_Tasks', 'single', $resTask[$i]['taskId']));
                } else {
                    // няма да има линк
                    unset($resTask[$i]['url']);
                }
            }
            
            if (is_array($rowArr)) {
                // за всяко едно ид от $rowArr търсим отговарящия му ключ от $resUser
                foreach ($rowArr as $k => $v) {
                    foreach ($v as $a => $t) {
                        foreach ($resUser as $key => $value) {
                            if ($t == $value['id']) {
                                $resTask[$k]['rowId'][$a] = $key;
                            }
                        }
                    }
                }
            }
        }
        
        // други параметри
        $others = self::renderGanttTimeType($data);
        
        $params = $others->otherParams;
        $header = $others->headerInfo;
        
        // връщаме един обект от всички масиви
        $res = (object) array('tasksData' => $resTask, 'headerInfo' => $header, 'resources' => $resUser, 'otherParams' => $params);
        
        $chart = gantt_Adapter::render($res);
        
        return $chart;
    }
    
    
    /**
     * Определяне на системното имен на гантовете
     *
     * @param stdClass $data
     */
    public static function getGanttTimeType($data)
    {
        $dateTasks = self::calcTasksMinStartMaxEndTime($data);
        
        // Масив [0] - датата
        //       [1] - часа
        $startTasksTime = dt::timestamp2Mysql($dateTasks->minStartTaskTime);
        $endTasksTime = dt::timestamp2Mysql($dateTasks->maxEndTaskTime);
        
        // ако периода на таблицата е в рамките на една една седмица
        if (dt::daysBetween($endTasksTime, $startTasksTime) < 3) {
            $type = 'WeekHour';
        
        // ако периода на таблицата е в рамките на седмица - месец
        } elseif (dt::daysBetween($endTasksTime, $startTasksTime) >= 3 && dt::daysBetween($endTasksTime, $startTasksTime) < 5) {
            $type = 'WeekHour4';
        
        // ако периода на таблицата е в рамките на седмица - месец
        } elseif (dt::daysBetween($endTasksTime, $startTasksTime) >= 5 && dt::daysBetween($endTasksTime, $startTasksTime) < 7) {
            $type = 'WeekHour6';
        
        // ако периода на таблицата е в рамките на седмица - месец
        } elseif (dt::daysBetween($endTasksTime, $startTasksTime) >= 7 && dt::daysBetween($endTasksTime, $startTasksTime) < 28) {
            $type = 'WeekDay';
        
        // ако периода на таблицата е в рамките на месец - 3 месеца
        } elseif (dt::daysBetween($endTasksTime, $startTasksTime) >= 28 && dt::daysBetween($endTasksTime, $startTasksTime) < 84) {
            $type = 'Months';
        
        // ако периода на таблицата е в рамките на година - седмици
        } elseif (dt::daysBetween($endTasksTime, $startTasksTime) >= 84 && dt::daysBetween($endTasksTime, $startTasksTime) < 168) {
            $type = 'YearWeek';
        
        // ако периода на таблицата е по-голям от година
        } elseif (dt::daysBetween($endTasksTime, $startTasksTime) >= 168) {
            $type = 'Years';
        }
        
        return  $type;
    }
    
    
    /**
     * Прави линкове към по-голям и по-маък тип гант
     *
     * @param string $ganttType
     */
    public static function getNextGanttType($ganttType)
    {
        $currUrl = getCurrentUrl();
        
        // текущия ни гант тайп
        $ganttType = Request::get('View');
        
        // намираме го в масива
        $curIndex = self::$view[$ganttType];
        
        // следващия ще е с индекс текущия +1
        $next = $curIndex + 1;
        
        if ($next <= count(self::$view)) {
            $nextType = array_search($next, self::$view);
            $currUrl['View'] = $nextType;
            
            $nextUrl = $currUrl;
        }
        
        // предишния ще е с индекс текущия - 1
        $prev = $curIndex - 1;
        
        if ($prev >= 1) {
            $prevType = array_search($prev, self::$view);
            $currUrl['View'] = $prevType;
            $prevUrl = $currUrl;
        }
        
        // връщаме 2-те URL-та
        return (object) array('prevUrl' => $prevUrl, 'nextUrl' => $nextUrl);
    }
    
    
    /**
     * Изчисляване на необходимите параметри за изчертаването на ганта
     *
     * @param stdClass $data
     */
    public static function renderGanttTimeType($data)
    {
        $stringTz = date_default_timezone_get();
        
        // Сетваме времевата зона
        date_default_timezone_set('UTC');
        
        $ganttType = Request::get('View');
        
        $url = self::getNextGanttType($ganttType);
        
        $dateTasks = self::calcTasksMinStartMaxEndTime($data);
        
        // Масив [0] - датата
        //       [1] - часа
        $startTasksTime = explode(' ', dt::timestamp2Mysql($dateTasks->minStartTaskTime));
        $endTasksTime = explode(' ', dt::timestamp2Mysql($dateTasks->maxEndTaskTime));
        
        // Масив [0] - година
        //       [1] - месец
        //       [2] - ден
        $startExplode = explode('-', $startTasksTime[0]);
        $endExplode = explode('-', $endTasksTime[0]);
        
        // иконите на стрелките
        $iconPlus = sbf('img/16/gantt-arr-down.png', '');
        $iconMinus = sbf('img/16/gantt-arr-up.png', '');
        
        $imgPlus = ht::createElement('img', array('src' => $iconPlus));
        $imgMinus = ht::createElement('img', array('src' => $iconMinus));
        $otherParams = $headerInfo = $res = array();
        
        switch ($ganttType) {
        
        // ако периода на таблицата е по-голям от година
            case 'Years':
                
                // делението е година/месец
                $otherParams['mainHeaderCaption'] = tr('година');
                $otherParams['subHeaderCaption'] = tr('месеци');
                
                // таблицата започва от първия ден на стартовия месец
                $otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], 1, $startExplode[0]);
                
                // до последния ден на намерения месец за край
                $otherParams['endTime'] = dt::mysql2timestamp(dt::getLastDayOfMonth($endTasksTime[0]). ' 23:59:59');
                
                // урл-тата на стрелките
                $otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
                $otherParams['biggerPeriod'] = ' ';
                
                // кое време е сега?
                $otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
                
                $curDate = dt::timestamp2mysql(mktime(0, 0, 0, $startExplode[1], 1, $startExplode[0]));
                $toDate = dt::getLastDayOfMonth($endTasksTime[0]). ' 23:59:59';
                
                // генерираме номерата на седмиците между началото и края
                while ($curDate < $toDate) {
                    $w = date('Y', dt::mysql2timestamp($curDate));
                    $res[$w]['mainHeader'] = $w;
                    $res[$w]['subHeader'][] = dt::getMonth(date('m', dt::mysql2timestamp($curDate)), $format = 'M');
                    $curDate = dt::addMonths(1, $curDate);
                }
                
                foreach ($res as $headerArr) {
                    $headerInfo[] = $headerArr;
                }
            
            break;
            
            // ако периода на таблицата е в рамките на една една седмица
            case 'WeekHour4':
                
                // делението е ден/час
                $otherParams['mainHeaderCaption'] = tr('ден');
                $otherParams['subHeaderCaption'] = tr('часове');
                
                // таблицата започва от 00ч на намерения за начало ден
                $otherParams['startTime'] = dt::mysql2timestamp($startTasksTime[0]);
                
                // до 23:59:59ч на намерения за край ден
                $otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);
                
                //урл-тата на стрелките
                $otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
                $otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
                
                // кое време е сега?
                $otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
                
                for ($i = 0; $i <= dt::daysBetween($endTasksTime[0], $startTasksTime[0]); $i++) {
                    $color = cal_Calendar::getColorOfDay(dt::addDays($i, $startTasksTime[0]));
                    
                    if (isset($color)) {
                        // оформяме заглавните части като показваме всеки един ден
                        $headerInfo[$i]['mainHeader'] = "<span class = '{$color}'>" . date('d.m. ', dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0]))) . '</span>';
                    } else {
                        $headerInfo[$i]['mainHeader'] = date('d.m. ', dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0])));
                    }
                    for ($j = 0; $j <= 23; $j = $j + 4) {
                        // започваме да чертаем от 00ч на намерения за начало ден, до 23ч на намерения за край ден
                        $headerInfo[$i]['subHeader'][$j] = date('H', mktime($j, $j, 0, $startExplode[1], $i, $endExplode[0])) . ':00';
                    }
                }
            
            break;
            
            // ако периода на таблицата е в рамките на една една седмица
            case 'WeekHour6':
                
                // делението е ден/час
                $otherParams['mainHeaderCaption'] = tr('ден');
                $otherParams['subHeaderCaption'] = tr('часове');
                
                // таблицата започва от 00ч на намерения за начало ден
                $otherParams['startTime'] = dt::mysql2timestamp($startTasksTime[0]);
                
                // до 23:59:59ч на намерения за край ден
                $otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);
                
                //урл-тата на стрелките
                $otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
                $otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
                
                // кое време е сега?
                $otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
                
                for ($i = 0; $i <= dt::daysBetween($endTasksTime[0], $startTasksTime[0]); $i++) {
                    $color = cal_Calendar::getColorOfDay(dt::addDays($i, $startTasksTime[0]));
                    
                    if (isset($color)) {
                        // оформяме заглавните части като показваме всеки един ден
                        $headerInfo[$i]['mainHeader'] = "<span class = '{$color}'>" . date('d.m. ', dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0]))) . '</span>';
                    } else {
                        $headerInfo[$i]['mainHeader'] = date('d.m. ', dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0])));
                    }
                    for ($j = 0; $j <= 23; $j = $j + 6) {
                        // започваме да чертаем от 00ч на намерения за начало ден, до 23ч на намерения за край ден
                        $headerInfo[$i]['subHeader'][$j] = date('H', mktime($j, $j, 0, $startExplode[1], $i, $endExplode[0])) . ':00';
                    }
                }
            
            break;
            
            // ако периода на таблицата е в рамките на една една седмица
            case 'WeekHour':
                
                // делението е ден/час
                $otherParams['mainHeaderCaption'] = tr('ден');
                $otherParams['subHeaderCaption'] = tr('часове');
                
                // таблицата започва от 00ч на намерения за начало ден
                $otherParams['startTime'] = dt::mysql2timestamp($startTasksTime[0]);
                
                // до 23:59:59ч на намерения за край ден
                $otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);
                
                //урл-тата на стрелките
                $otherParams['smallerPeriod'] = ' ';
                $otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
                
                // кое време е сега?
                $otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
                
                for ($i = 0; $i <= dt::daysBetween($endTasksTime[0], $startTasksTime[0]); $i++) {
                    $color = cal_Calendar::getColorOfDay(dt::addDays($i, $startTasksTime[0]));
                    
                    if (isset($color)) {
                        // оформяме заглавните части като показваме всеки един ден
                        $headerInfo[$i]['mainHeader'] = "<span class = '{$color}'>" . date('d.m. ', dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0]))) . '</span>';
                    } else {
                        $headerInfo[$i]['mainHeader'] = date('d.m. ', dt::mysql2timestamp(dt::addDays($i, $startTasksTime[0])));
                    }
                    for ($j = 0; $j <= 23; $j++) {
                        // започваме да чертаем от 00ч на намерения за начало ден, до 23ч на намерения за край ден
                        $headerInfo[$i]['subHeader'][$j] = date('H', mktime($j, $j, 0, $startExplode[1], $i, $endExplode[0])) . ':00';
                    }
                }
            
            break;
            
            // ако периода на таблицата е в рамките на седмица - месец
            case 'WeekDay':
                
                // делението е седмица/ден
                $otherParams['mainHeaderCaption'] = tr('седмица');
                $otherParams['subHeaderCaption'] = tr('ден');
                
                // от началото на намерения стартов ден
                $otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]);
                
                // до края на намерения за край ден
                $otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);
                
                // урл-тата на стрелките
                $otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
                $otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
                
                // кое време е сега?
                $otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
                
                $curDate = $startTasksTime[0]. ' 00:00:00';
                $toDate = $endTasksTime[0]. ' 23:59:59';
                
                // генерираме номерата на седмиците между началото и края
                while ($curDate < $toDate) {
                    $color = cal_Calendar::getColorOfDay($curDate);
                    $w = date('W', dt::mysql2timestamp($curDate));
                    $res[$w]['mainHeader'] = $w;
                    
                    if (isset($color)) {
                        $res[$w]['subHeader'][] = "<span class = '{$color}'>" . date('d.m. ', dt::mysql2timestamp($curDate)) . '</span>';
                    } else {
                        $res[$w]['subHeader'][] = date('d.m. ', dt::mysql2timestamp($curDate));
                    }
                    
                    $curDate = dt::addDays(1, $curDate);
                }
                
                foreach ($res as $headerArr) {
                    $headerInfo[] = $headerArr;
                }
            
            break;
           
           // ако периода на таблицата е в рамките на месец - ден
            case 'Months':
                
                // делението е месец/ден
                $otherParams['mainHeaderCaption'] = tr('месец');
                $otherParams['subHeaderCaption'] = tr('ден');
                
                // таблицата започва от 1 ден на намерения за начало месец
                $otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]);
                
                // до последния ден на намерения за край месец
                $otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2] + 3, $endExplode[0]);
                
                // урл-тата на стрелките
                $otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
                $otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
                
                // кое време е сега?
                $otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
                
                $curDate = $startTasksTime[0]. ' 00:00:00';
                $toDate = dt::addDays(3, $endTasksTime[0]). ' 23:59:59';
                
                // генерираме номерата на седмиците между началото и края
                while ($curDate <= $toDate) {
                    $color = cal_Calendar::getColorOfDay($curDate);
                    $curDateExplode = explode('-', $curDate);
                    $w = dt::getMonth($curDateExplode[1], 'F') . ' ' . $curDateExplode[0];
                    $res[$w]['mainHeader'] = $w;
                    
                    if (isset($color)) {
                        $res[$w]['subHeader'][] = "<span class='{$color}'>" . date('d.m ', dt::mysql2timestamp($curDate)) . '</span>';
                    } else {
                        $res[$w]['subHeader'][] = date('d.m ', dt::mysql2timestamp($curDate));
                    }
                    $curDate = dt::addDays(1, $curDate);
                }
                
                foreach ($res as $headerArr) {
                    $headerInfo[] = $headerArr;
                }
            
            break;
           
           // ако периода на таблицата е в рамките на година - седмици
            case 'YearWeek':
                
                // делението е месец/седмица
                $otherParams['mainHeaderCaption'] = tr('година');
                $otherParams['subHeaderCaption'] = tr('седмица');
                
                if (date('N', mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0])) != 1) {
                    // таблицата започва от понеделника преди намерената стартова дата
                    $otherParams['startTime'] = dt::mysql2timestamp(date('Y-m-d H:i:s', strtotime('last Monday', mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]))));
                } else {
                    $otherParams['startTime'] = mktime(0, 0, 0, $startExplode[1], $startExplode[2], $startExplode[0]);
                }
                
                if (date('N', mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0])) != 7) {
                    // до неделята след намеренета за край дата
                    $otherParams['endTime'] = dt::mysql2timestamp(date('Y-m-d H:i:s', strtotime('Sunday', mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]))));
                } else {
                    $otherParams['endTime'] = mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]);
                }
                
                // урл-тата на стрелките
                $otherParams['smallerPeriod'] = ht::createLink($imgPlus, $url->prevUrl)->getContent();
                $otherParams['biggerPeriod'] = ht::createLink($imgMinus, $url->nextUrl)->getContent();
                
                // кое време е сега?
                $otherParams['currentTime'] = dt::mysql2timestamp(dt::now());
                
                $curDate = date('Y-m-d H:i:s', $otherParams['startTime']);
                $toDate = dt::addSecs(86399, date('Y-m-d H:i:s', strtotime('Sunday', mktime(23, 59, 59, $endExplode[1], $endExplode[2], $endExplode[0]))));
                
                // генерираме номерата на седмиците между началото и края
                while ($curDate < $toDate) {
                    $curDateExplode = explode('-', $curDate);
                    $w = $curDateExplode[0];
                    
                    // ако 31.12 е ден до сряда, то 01 седмица ще се отбелязва в следващата година
                    if (date('W', dt::mysql2timestamp($curDate)) == 01 && date('N', mktime(23, 59, 59, 12, 31, $startExplode[0])) <= 3) {
                        $w = $w + 1;
                    }
                    
                    $res[$w]['mainHeader'] = $w;
                    
                    // номера на седмицата
                    $res[$w]['subHeader'][date('W', dt::mysql2timestamp($curDate))] = '&nbsp;' . date('W', dt::mysql2timestamp($curDate)) . '&nbsp;';
                    
                    // обикаляме по седмиците
                    $curDate = dt::addDays(7, $curDate);
                }
                
                // тези действия са за номериране на вътрешния масив от 0,1, ...
                foreach ($res as $key => $headerArr) {
                    foreach ($headerArr['subHeader'] as $val) {
                        $subInfo[$key]['mainHeader'] = $key;
                        $subInfo[$key]['subHeader'][] = $val;
                    }
                }
                
                // тези действия са за номериране на външния масив от 0,1, ...
                foreach ($subInfo as $infoArr) {
                    $headerInfo[] = $infoArr;
                }
            
            break;
        }
        
        date_default_timezone_set($stringTz);
        
        return (object) array('otherParams' => $otherParams, 'headerInfo' => $headerInfo);
    }
    
    
    /**
     * Изчислява мин начало и макс край на всички задачи
     *
     * @param stdClass $data
     */
    public static function calcTasksMinStartMaxEndTime($data)
    {
        $start = $end = array();
        if ($data->recs) {
            $data = $data->recs;
        }
        
        if (is_array($data)) {
            // за всеки едиин запис от базата данни
            foreach ($data as $rec) {
                if ($rec->timeStart) {
                    $timeStart = $rec->timeStart;
                } else {
                    $timeStart = $rec->expectationTimeStart;
                }
                
                if ($rec->timeEnd) {
                    $timeEnd = $rec->timeEnd;
                } else {
                    $timeEnd = $rec->expectationTimeEnd;
                }
                
                if ($timeStart) {
                    // правим 2 масива с начални и крайни часове
                    if ($timeStart) {
                        $start[] = dt::mysql2timestamp($timeStart);
                        $end[] = dt::mysql2timestamp($timeEnd);
                    }
                }
            }
        }
        
        if (count($start) >= 2 && count($end) >= 2) {
            $startTime = min($start);
            $endTime = max($end);
        } else {
            $startTime = dt::mysql2timestamp($timeStart);
            $endTime = dt::mysql2timestamp($timeEnd);
        }
        
        return (object) array('minStartTaskTime' => $startTime, 'maxEndTaskTime' => $endTime);
    }
    
    
    /**
     * Може ли една задача да стане в състояние 'active'?
     *
     * @param stdClass $rec
     *
     * @return datetime|NULL|FALSE
     */
    public static function canActivateTask($rec)
    {
        // Без отговорник да не може да се активират
        $sharedUsersArr = keylist::toArray($rec->sharedUsers);
        
        if ($rec->assign) {
            $sharedUsersArr += type_Keylist::toArray($rec->assign);
        }
        
        if (empty($sharedUsersArr)) {
            
            return ;
        }
        
        // сега
        $now = dt::verbal2mysql();
        $nowTimeStamp = dt::mysql2timestamp($now);
        
        $calcTime = false;
        $calcTimeS = $arrCond = array();
        
        // Ако сме активирали през singleToolbar-а
        if ($rec->id) {
            $query = cal_TaskConditions::getQuery();
            $query->where("#baseId = '{$rec->id}'");
            
            while ($recCond = $query->fetch()) {
                $arrCond[] = $recCond;
            }
            
            // ако задачата е зависима
            if (!empty($arrCond)) {
                foreach ($arrCond as $cond) {
                    // зависиама по прогрес
                    if ($cond->activationCond == 'onProgress') {
                        // процентите на завършване на бащината задача
                        $progress = self::fetchField($cond->dependId, 'progress');
                        
                        // ако е равен или по голям на искания от потребутеля процент
                        if ($progress >= $cond->progress) {
                            // времето за стартирване на текущата задача е сега
                            $calcTime = $now;
                        } else {
                            $calcTime = null;
                        }
                        
                        return $calcTime;
                        
                        // ако ще правим изчисления по времена
                    }
                    
                    // правим масив с всички изчислени времена
                    $calcTimeS[] = self::calculateTimeToStart($rec, $cond);
                }
                
                // взимаме и началното време на текущата задача,
                // ако има такова
                $timeStart = self::fetchField($rec->id, 'timeStart');
                
                if ($timeStart != null) {
                    // прибавяме го към масива
                    array_push($calcTimeS, $timeStart);
                    
                    // най-малкото време е времето за стартирване на текущата задача
                    $calcTime = min($calcTimeS);
                } else {
                    if (!empty($calcTimeS)) {
                        $calcTime = min($calcTimeS);
                    } else {
                        $calcTime = null;
                    }
                }
                
                return $calcTime;
                
                // задачата не е зависима от други задачи
            }
            $timeStart = self::fetchField($rec->id, 'timeStart');
            $timeEnd = self::fetchField($rec->id, 'timeEnd');
            $timeDuration = self::fetchField($rec->id, 'timeDuration');
            
            if ($timeStart != null) {
                // времето за стартиране е времето оказано от потребителя
                $calcTime = $timeStart;
            } elseif (!$timeStart && ($timeEnd && $timeDuration)) {
                $calcTime = dt::timestamp2Mysql(dt::mysql2timestamp($timeEnd) - $timeDuration);
            } else {
                // ако не е оказано време от потребителя - е сега
                $calcTime = $now;
            }
            
            return $calcTime;
        } elseif (!$rec->id && $rec->timeStart) {
            if (!empty($arrCond)) {
                foreach ($arrCond as $cond) {
                    if ($cond->activationCond == 'onProgress') {
                        // proverka za systoqnieto ?!?
                        $progress = self::fetchField($cond->dependId, 'progress');
                        
                        if ($progress >= $cond->progress) {
                            $calcTime = $now;
                        }
                    } else {
                        $calcTimeS[] = self::calculateTimeToStart($rec, $cond);
                    }
                    
                    return $calcTime;
                }
                $timeStart = $rec->timeStart;
                
                if ($timeStart != null) {
                    // прибавяме го към масива
                    array_push($calcTimeS, $timeStart);
                    
                    // най-малкото време е времето за стартирване на текущата задача
                    $calcTime = min($calcTimeS);
                } else {
                    if (!empty($calcTimeS)) {
                        $calcTime = min($calcTimeS);
                    }
                }
            } else {
                $calcTime = $rec->timeStart;
            }
        } elseif (!$rec->timeStart && !$rec->id) {
            $calcTime = $now;
        }
        
        // връщаме времето за активиране
        return $calcTime;
    }
    
    
    /**
     * Добавя нотификация за приключена задача
     *
     * @param stdClass $rec
     * @param string   $msg
     * @param array    $notifyUsersArr
     * @param bool     $removeOldNotify
     */
    public static function notifyForChanges($rec, $msg, $notifyUsersArr = array(), $removeOldNotify = false)
    {
        $rec = self::fetchRec($rec);
        
        if (!$rec) {
            
            return ;
        }
        
        if (isset($notifyUsersArr) && empty($notifyUsersArr)) {
            
            return ;
        }
        
        if (is_null($notifyUsersArr)) {
            $notifyUsersArr = array($rec->createdBy => $rec->createdBy);
        }
        
        $cu = core_Users::getCurrent();
        unset($notifyUsersArr[$cu]);
        
        $message = "|{$msg}|*" . ' "' . $rec->title . '"';
        $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
        $customUrl = array('cal_Tasks', 'single',  $rec->id);
        $priority = 'normal';
        
        if ($removeOldNotify) {
            bgerp_Notifications::clear($url);
        }
        
        foreach ($notifyUsersArr as $uId) {
            if ($uId < 1) {
                continue;
            }
            
            if (!cal_Tasks::haveRightFor('single', $rec->id)) {
                continue;
            }
            
            bgerp_Notifications::add($message, $url, $uId, $priority, $customUrl);
        }
    }
    
    
    /**
     *
     *
     * @param cal_Tasks $mvc
     * @param stdClass  $rec
     * @param string    $state
     */
    protected function on_AfterChangeState($mvc, $rec, $state)
    {
        // Променяме времето
        if (($state == 'stopped') || ($state == 'closed')) {
            $rec->timeClosed = dt::now();
            self::save($rec, 'timeClosed');
        }
    }
    
    
    /**
     * Връща разбираемо за човека заглавие, отговарящо на записа
     */
    public static function getRecTitle($rec, $escaped = true)
    {
        $me = cls::get(get_called_class());
        $dRow = $me->getDocumentRow($rec->id);
        
        $handle = $me->getHandle($rec->id);
        
        return "{$handle} - {$dRow->title}";
    }
    
    
    /**
     * Правим нотификация на всички шернати потребители,
     * че е стартирана задачата
     */
    public static function doNotificationForActiveTasks($activatedTasks)
    {
        foreach ($activatedTasks as $rec) {
            $subscribedArr = keylist::toArray($rec->sharedUsers);
            
            if ($rec->assign) {
                $subscribedArr += type_Keylist::toArray($rec->assign);
            }
            
            if (is_array($subscribedArr)) {
                $message = '|Стартирана е задачата|* "' . self::getVerbal($rec, 'title') . '"';
                $url = array('doc_Containers', 'list', 'threadId' => $rec->threadId);
                $customUrl = array('cal_Tasks', 'single',  $rec->id);
                $priority = 'normal';
                
                foreach ($subscribedArr as $userId) {
                    if ($userId > 0 && self::haveRightFor('single', $rec, $userId)) {
                        bgerp_Notifications::add($message, $url, $userId, $priority, $customUrl);
                    }
                }
            }
            
            $rec->notifySent = 'yes';
            
            self::save($rec, 'notifySent');
        }
    }
    
    
    /**
     * Изчисляваме новото начало за стратиране на задачата
     * ако тя е зависима по време от някоя друга
     *
     * @param stdClass $rec
     * @param stdClass $recCond
     */
    public static function calculateExpectationTime(&$rec)
    {
        $stringTz = date_default_timezone_get();
        date_default_timezone_set('UTC');
        
        // сега
        $now = dt::verbal2mysql();
        
        // ако задачата има id следователно може да е зависима от други
        if ($rec->id) {
            $query = cal_TaskConditions::getQuery();
            
            $query->where("#baseId = '{$rec->id}'");
            
            while ($recCond = $query->fetch()) {
                $arrCond[] = $recCond;
            }
            
            if (!empty($arrCond)) {
                foreach ($arrCond as $cond) {
                    // правим масив с всички изчислени времена
                    $calcTimeS[] = self::calculateTimeToStart($rec, $cond);
                }
                
                // взимаме и началното време на текущата задача,
                // ако има такова
                $timeStartRec = $rec->timeStart;
                
                if (!$timeStartRec) {
                    // в противен случай го слагаме 0
                     //$timeStartRec = $now;
                     //$timeStartRec = 0;
                } else {
                    // прибавяме го към масива
                    array_push($calcTimeS, $timeStartRec);
                }
                
                // най-малкото време е времето за стартирване на текущата задача
                $timeStart = min($calcTimeS);
            
            // ако не е зависима от други взимаме нейните начало и край
            } else {
                $timeStart = $rec->timeStart;
                $timeEnd = $rec->timeEnd;
                $timeDuration = $rec->timeDuration;
                
                if ($timeDuration && !$timeEnd) {
                    $timeEnd = dt::timestamp2Mysql(dt::mysql2timestamp($timeStart) + $timeDuration);
                } elseif (($timeDuration && $timeEnd) && !$timeStart) {
                    $timeStart = dt::timestamp2Mysql(dt::mysql2timestamp($timeEnd) - $timeDuration);
                }
            }
            
            // ако няма id, то имаме директно началото и края й
        } else {
            $timeStart = $rec->timeStart;
            $timeEnd = $rec->timeEnd;
        }
        
        // ако задачата няма начало и край
        if ($timeStart == null && $timeEnd == null && $rec->timeDuration == null) {
            $expStart = $now;
            $expEnd = $now;
        
        // ако задачата има начало
        // може да определим и края й
        } elseif ($timeStart && !$timeEnd) {
            $expStart = $timeStart;
            $expEnd = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) + $rec->timeDuration);
        
        // ако задачата има край
        // можем да кажем кога е началото й
        } elseif ($timeEnd && !$timeStart && !$rec->timeDuration) {
            $expEnd = $timeEnd;
            if ($rec->id) {
                $expStart = $rec->modifiedOn;
            }
            
            // ако има и начало и край
        // то очакваните начало и край са тези
        } elseif ($timeStart && $timeEnd) {
            $expStart = $timeStart;
            $expEnd = $timeEnd;
        } elseif (($rec->timeDuration && $timeStart) && !$timeEnd) {
            $expStart = $timeStart;
            $expEnd = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) + $rec->timeDuration);
        } elseif ($rec->timeDuration && (!$timeStart && !$timeEnd)) {
            $expStart = $now;
            $expEnd = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) + $rec->timeDuration);
        } elseif (($rec->timeDuration && $timeEnd) && !$timeStart) {
            $expStart = dt::timestamp2Mysql(dt::mysql2timestamp($expStart) - $rec->timeDuration);
            $expEnd = $timeEnd;
        }
        
        $rec->expectationTimeStart = $expStart;
        $rec->expectationTimeEnd = $expEnd;
        
        date_default_timezone_set($stringTz);
    }
    
    
    /**
     * Изчисляваме новото начало за стратиране на задачата
     * ако тя е зависима по време от някоя друга
     *
     * @param stdClass $rec
     * @param stdClass $recCond
     */
    public static function calculateTimeToStart($rec, $recCond)
    {
        // времето от което зависи новата задача е началото на зависимата задача
        // "timeCalc"
        $dependTimeStart = self::fetchField($recCond->dependId, 'expectationTimeStart');
        $dependTimeEnd = self::fetchField($recCond->dependId, 'expectationTimeEnd');
        $closedTime = self::fetchField($recCond->dependId, 'timeClosed');
        
        $now = dt::verbal2mysql();
        
        if (!$dependTimeStart) {
            $dependTimeStart = self::fetchField($recCond->dependId, 'timeActivated');
        }
        
        if (!$dependTimeEnd) {
            if (!$closedTime) {
                $dependTimeEnd = dt::timestamp2Mysql(dt::mysql2timestamp($dependTimeStart) + $recCond->timeDuration);
            } else {
                $dependTimeEnd = $closedTime;
            }
        }
        
        // ако имаме условие след началото на задачата
        if ($recCond->activationCond == 'afterTime') {
            // прибавяме отместването след началото
            $calcTime = dt::mysql2timestamp($dependTimeStart) + $recCond->distTime;
            $calcTimeStart = dt::timestamp2Mysql($calcTime);
        } elseif ($recCond->activationCond == 'beforeTime') {
            // в противен случай го вадим
            $calcTime = dt::mysql2timestamp($dependTimeStart) - $recCond->distTime;
            $calcTimeStart = dt::timestamp2Mysql($calcTime);
        } elseif ($recCond->activationCond == 'afterTimeEnd') {
            // прибавяме отместването в кря
            $calcTime = dt::mysql2timestamp($dependTimeEnd) + $recCond->distTime;
            $calcTimeStart = dt::timestamp2Mysql($calcTime);
        } else {
            // в противен случай го вадим
            $calcTime = dt::mysql2timestamp($dependTimeEnd) - $recCond->distTime;
            $calcTimeStart = dt::timestamp2Mysql($calcTime);
        }
        
        // ако задачата е безкрайна
        if (!$rec->timeStart) {
            $rec->timeCalc = $calcTimeStart;
            self::save($rec, 'timeCalc');
            
            // връщаме изчисленото време
            return $calcTimeStart;
            
            // в противен случай гледаме коя е най-голямата дата и нея взимаме
        }
        
        if ($rec->timeStart > $calcTimeStart) {
            $rec->timeCalc = $rec->timeStart;
            self::save($rec, 'timeCalc');
            
            return $rec->timeStart;
        }
        $rec->timeCalc = $calcTimeStart;
        self::save($rec, 'timeCalc');
        
        return $calcTimeStart;
    }
    
    
    public static function roundTime($time)
    {
        if (!isset($time) || !is_numeric($time)) {
            
            return;
        }
        
        $t = abs($time);
        
        $weeks = floor($t / (7 * 24 * 60 * 60));
        $days = floor(($t - $weeks * (7 * 24 * 60 * 60)) / (24 * 60 * 60));
        $hours = floor(($t - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60)) / (60 * 60));
        $minutes = floor(($t - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60) - $hours * 60 * 60) / 60);
        $secundes = floor(($t - $weeks * (7 * 24 * 60 * 60) - $days * (24 * 60 * 60) - $hours * 60 * 60 - $minutes * 60));
        
        if ($weeks > 0) {
            $res = round($time / 86400) * 86400;
            
            return $res;
        }
        
        if ($days > 0) {
            $res = round($time / 3600) * 3600;
            
            return $res;
        }
        
        if ($hours > 0 || $minutes > 0) {
            $res = round($time / 60) * 60;
            
            return $res;
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
    public static function on_AfterGetFieldForLetterHead($mvc, &$resArr, $rec, $row)
    {
        $resArr = arr::make($resArr);
        
        if ($row->progressBar || $row->progress) {
            $resArr['progressBar'] = array('name' => tr('Прогрес'), 'val' => '[#progressBar#] [#progress#]');
        }
        
        $resArr[$mvc->driverClassField] = array('name' => tr('Вид'), 'val' => "[#{$mvc->driverClassField}#]");
        
        $resArr['priority'] = array('name' => tr('Приоритет'), 'val' => '[#priority#]');
        
        if ($row->timeStart) {
            $resArr['timeStart'] = array('name' => tr('Начало'), 'val' => '[#timeStart#]');
        }
        
        if ($row->timeDuration) {
            $resArr['timeDuration'] = array('name' => tr('Продължителност'), 'val' => '[#timeDuration#]');
        }
        
        if ($row->timeEnd) {
            $resArr['timeEnd'] = array('name' => tr('Краен срок'), 'val' => '[#timeEnd#] [#remainingTime#]');
        }
        
        if ($row->workingTime) {
            $resArr['workingTime'] = array('name' => tr('Отработено време'), 'val' => '[#workingTime#]');
        }
        
        if ($row->afterTask) {
            $resArr['afterTask'] = array('name' => tr('Започване след задача'), 'val' => '[#afterTask#]');
        }
        
        if ($row->afterTaskProgress) {
            $resArr['afterTaskProgress'] = array('name' => tr('Прогрес на задачата'), 'val' => '[#afterTaskProgress#]');
        }
        
        
        if ($row->expectationTimeStart) {
            $resArr['expectationTimeStart'] = array('name' => tr('Очаквано начало'), 'val' => '[#expectationTimeStart#]');
        }
        
        if ($rec->timeStart) {
            unset($resArr['expectationTimeStart']);
        }
        
        if ($row->expectationTimeEnd) {
            $resArr['expectationTimeEnd'] = array('name' => tr('Очакван край'), 'val' => '[#expectationTimeEnd#]');
        }
        
        if ($rec->timeEnd) {
            unset($resArr['expectationTimeEnd']);
        }
        
        if (!$rec->timeStart && !$rec->timeEnd) {
            unset($resArr['expectationTimeStart']);
            unset($resArr['expectationTimeEnd']);
        }
        
        if ($row->assign) {
            if ($rec->assign && $rec->assignedBy) {
                $resArr['assign'] = array('name' => tr('Възложено'), 'val' => tr('на') . ' [#assign#] ' . tr('от') . ' [#assignedBy#] ' . tr('в') . ' [#assignedOn#]');
            } else {
                $resArr['assign'] = array('name' => tr('Възложено'), 'val' => tr('на') . ' [#assign#]');
            }
        }
    }
    
    
    /**
     * Преди записване на клонирания запис
     *
     * @param core_Mvc $mvc
     * @param object   $rec
     * @param object   $nRec
     *
     * @see plg_Clone
     */
    public function on_BeforeSaveCloneRec($mvc, $rec, $nRec)
    {
        unset($nRec->progress);
        unset($nRec->timeActivated);
        unset($nRec->workingTime);
        unset($nRec->timeCalc);
        $nRec->notifySent = 'no';
    }
    
    
    /**
     * Екшън за добавяне на нов сигнал в системата от външни потребители
     *
     * @return Redirect|ET
     */
    public function act_New()
    {
        $this->requireRightFor('new');
        
        $systemId = Request::get('systemId', 'int');
        
        expect($systemId);
        
        $debugFileHnd = null;
        
        // Качваме файла
        if ($isReportFromStream = Request::get('streamReport')) {
            if ($fData = Request::get('data')) {
                $fName = Request::get('fName');
                if (!$fName) {
                    $fName = 'debug';
                }
                $fName .= '.debug';
                
                $fData = gzuncompress($fData);
                
                if ($fData) {
                    $debugFileHnd = fileman::absorbStr($fData, 'Support', $fName);
                }
            }
        }
        
        // Ако има права за добавяне, директно се редиректва там
        if (!$isReportFromStream && $this->haveRightFor('add')) {
            $folderId = support_Systems::forceCoverAndFolder($systemId);
            
            if (doc_Folders::haveRightFor('single', $folderId)) {
                
                return new Redirect(array($this, 'add', 'folderId' => $folderId));
            }
        }
        
        if ($lg = Request::get('Lg')) {
            cms_Content::setLang($lg);
            core_Lg::push($lg);
        }
        
        // Подготовка на формата
        $form = $this->getForm();
        
        // Скриваме всички полета
        foreach ($this->fields as $fName => $dummy) {
            $form->setField($fName, 'input=none');
        }
        
        $interfaces = static::getAvailableDriverOptions();
        
        expect(!empty($interfaces), 'Няма налични опции');
        
        $form->setOptions($this->driverClassField, $interfaces);
        
        // Ако е наличен само един драйвер избираме него
        if ((count($interfaces) == 1) || $isReportFromStream) {
            $intfKey = key($interfaces);
            $form->setDefault($this->driverClassField, $intfKey);
            $form->setReadOnly($this->driverClassField);
        } else {
            $form->setField($this->driverClassField, 'input');
        }
        
        $form->input(null, true);
        
        // Подготвяме полетата от драйвера
        if ($form->rec->{$this->driverClassField}) {
            $Driver = cls::get($form->rec->{$this->driverClassField});
            
            $Driver->addFields($form);
            
            $Driver->prepareFieldForIssue($form);
        }
        
        $form->setField('title', 'silent, input=hidden');
        $form->setField('description', 'input, silent, mandatory');
        
        $form->input(null, true);
        $form->input();
        
        setIfNot($form->rec->title, '*Без заглавие*');
        
        if ($isReportFromStream || $form->isSubmitted()) {
            if ($isReportFromStream) {
                $form->rec->description = gzuncompress($form->rec->description);
                $form->rec->description = type_Varchar::escape($form->rec->description);
                
                if ($debugFileHnd) {
                    $form->rec->file = $debugFileHnd;
                }
            }
            
            $form->rec->state = 'active';
            $form->rec->activatedBy = (int) core_Users::getCurrent();
            
            if ($systemId) {
                $form->rec->folderId = support_Systems::forceCoverAndFolder($systemId);
            }
            
            cal_Tasks::save($form->rec);
            
            vislog_History::add('Изпращане на сигнал');
            
            $successMsg = 'Благодарим Ви за сигнала';
            
            if ($isReportFromStream) {
                echo 'OK';
                shutdown();
            }
            
            return followRetUrl(null, "|{$successMsg}", 'success');
        }
        
        $sTitle = '';
        if ($form->rec->{$this->driverClassField}) {
            $sTitle = $interfaces[$form->rec->{$this->driverClassField}];
        }
        if (!$sTitle) {
            $sTitle = 'Задача';
        }
        
        $form->title = str::mbUcfirst($sTitle) . ' към екипа за поддръжка на|* ' . '"|' . support_Systems::getTitleById($systemId) . '|*"';
        
        $form->toolbar->addSbBtn('Изпрати', 'save', 'id=save, ef_icon = img/16/ticket.png,title=Изпращане на сигнала');
        if (count(getRetUrl())) {
            $form->toolbar->addBtn('Отказ', getRetUrl(), 'id=cancel, ef_icon = img/16/close-red.png,title=Отказ');
        }
        $tpl = $form->renderHtml();
        
        // Поставяме шаблона за външен изглед
        Mode::set('wrapper', 'cms_page_External');
        
        if ($lg) {
            core_Lg::pop();
        }
        
        return $tpl;
    }
    
    
    /**
     * Връща тялото на имейла генериран от документа
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string
     *
     * @see email_DocumentIntf
     */
    public function getDefaultEmailBody($id, $forward = false)
    {
        $rec = $this->fetchRec($id);
        $Driver = $this->getDriver($id);
        
        $date = dt::mysql2verbal($rec->createdOn, 'd-M');
        $time = dt::mysql2verbal($rec->createdOn, 'H:i');
        
        $tpl = new ET(tr("|Благодаря за Вашето запитване|*, |получено на|* {$date} |в|* {$time} |чрез нашия уеб сайт|*."));
        
        $title = mb_strtolower($Driver->title);
        $fLetter = mb_substr($title, 0, 1);
        
        $sLetter = 'с';
        if ($fLetter == 'с' || $fLetter == 'з') {
            $sLetter = 'със';
        }
        
        $res = "Във връзка {$sLetter}|* " . mb_strtolower($Driver->title) . " |от|* {$date} |в|* {$time}";
        
        return tr($res);
    }
    
    
    /**
     * Интерфейсен метод
     *
     * @param int $id
     *
     * @return object
     *
     * @see doc_ContragentDataIntf
     */
    public static function getContragentData($id)
    {
        if (!$id) {
            
            return ;
        }
        $rec = self::fetch($id);
        
        $contrData = new stdClass();
        
        if ($rec->createdBy > 0) {
            $personId = crm_Profiles::fetchField("#userId = '{$rec->createdBy}'", 'personId');
            $contrData = crm_Persons::getContragentData($personId);
        }
        
        $Driver = self::getDriver($id);
        $Driver->prepareContragentData($rec, $contrData);
        
        return $contrData;
    }
    
    
    /**
     * Връща заглавието на имейла
     *
     * @param int  $id      - ид на документа
     * @param bool $forward
     *
     * @return string
     *
     * @see email_DocumentIntf
     */
    public function getDefaultEmailSubject($id, $forward = false)
    {
        $rec = $this->fetchRec($id);
        
        return tr('За') . ': ' . $rec->title;
    }
}
