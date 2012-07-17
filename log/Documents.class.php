<?php 


/**
 * История от събития, свързани с документите
 *
 * Събитията са изпращане по имейл, получаване, връщане, печат, разглеждане
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class log_Documents extends core_Manager
{
    
    
    /**
     * Заглавие на таблицата
     */
    var $title = "Лог на документи";
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, doc';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'no_one';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'no_one';
    
    
    /**
     * Кой има право да го види?
     */
    var $canView = 'admin, doc';
    
    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin, doc';
    
    
    /**
     * Необходими роли за оттегляне на документа
     */
    var $canReject = 'no_one';
    
    
    /**
     * Кой има право да го изтрие?
     */
    var $canDelete = 'no_one';
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'log_Wrapper,  plg_Created';
    
    
    /**
     * @todo Чака за документация...
     */
    var $listFields = 'createdBy, createdOn, action=Какво, containerId=Кое, dataBlob';
    
    var $listFieldsSet = array(
        self::ACTION_SEND  => 'createdBy=Потребител, createdOn=Дата, containerId=Кое, toEmail=До, receivedOn=Получено, returnedOn=Върнато',
        self::ACTION_PRINT => 'createdBy=Потребител, createdOn=Дата, containerId=Кое, action=Действие, seenOn=Видяно',
    );
    
    /**
     * Масов-кеш за историите на контейнерите по нишки
     *
     * @var array
     */
    protected static $histories = array();
    
    
    /**
     * Домейн на записите в кеша
     *
     * @see core_Cache
     */
    const CACHE_TYPE = 'thread_history';
    
    const ACTION_SEND    = 'send';
    const ACTION_RETURN  = '_returned';
    const ACTION_RECEIVE = '_received';
    const ACTION_OPEN    = 'open';
    const ACTION_PRINT   = 'print';
    const ACTION_DISPLAY = 'display';
    const ACTION_FAX     = 'fax';
    const ACTION_PDF     = 'pdf';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $actionsEnum = array(
            self::ACTION_SEND    . '=имейл',
            self::ACTION_RETURN  . '=връщане',
            self::ACTION_RECEIVE . '=получаване',
            self::ACTION_OPEN    . '=показване',
            self::ACTION_PRINT   . '=отпечатване',
            self::ACTION_DISPLAY . '=разглеждане',
            self::ACTION_FAX     . '=факс',
            self::ACTION_PDF     . '=PDF',
        );
        
        // Тип на събитието
        $this->FLD("action", 'enum(' . implode(',', $actionsEnum) . ')', "caption=Действие");
        
        // Нишка на документа, за който се отнася събитието
        $this->FLD('threadId', 'key(mvc=doc_Threads)', 'caption=Нишка');
        
        // Документ, за който се отнася събитието
        $this->FLD('containerId', 'key(mvc=doc_Containers)', 'caption=Контейнер');
        
        // MID на документа
        $this->FLD('mid', 'varchar', 'input=none,caption=Ключ,column=none');
        
        $this->FLD('parentId', 'key(mvc=log_Documents, select=action)', 'input=none,caption=Основание');
        
//         $this->FLD('baseParentId', 'key(mvc=log_Documents, select=action)', 'input=none,caption=Основание');
        
        // Допълнителни обстоятелства, в зависимост от събитието (в PHP serialize() формат)
        $this->FLD("dataBlob", "blob", 'caption=Обстоятелства,column=none,oldFieldName=data');
        
        $this->FNC('data', 'text', 'input=none');
        $this->FNC('seenOnTime', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('receivedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('returnedOn', 'datetime(format=smartTime)', 'input=none');
        $this->FNC('seenFromIp', 'ip', 'input=none');
        
        $this->setDbIndex('containerId');
        $this->setDbUnique('mid');
    }
    
    
    function on_CalcData($mvc, $rec)
    {
        $rec->data = @unserialize($rec->dataBlob);
        if (empty($rec->data)) {
            $rec->data = new StdClass();
        }
    }
    

    function on_CalcReceivedOn($mvc, $rec)
    {
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->receivedOn)) {
			$rec->receivedOn = $rec->data->receivedOn;
		}
    }
    

    function on_CalcReturnedOn($mvc, $rec)
    {
		if ($rec->action == static::ACTION_SEND && !empty($rec->data->returnedOn)) {
			$rec->returnedOn = $rec->data->returnedOn;
		}
    }
    
    
    /**
     * Добавя запис в историята на документ
     * 
     * @param string $action
     * @param int    $cid key(mvc=doc_Containers)
     * @param int    $parentId key(mvc=log_Documents)
     * @param mixed  $details
     * @return string|boolean MID на новосъздадения запис или FALSE при неуспех
     */
    public static function add($action, $cid, $parentId = NULL, $details = NULL)
    {
        bp('deprecated');
        $tid = doc_Containers::fetchField($cid, 'threadId');
        
        // Валидация на $parentId - трябва да е ключ на запис в историята или NULL
        expect(!isset($parentId) || static::fetch($parentId));
        

        // Създаваме нов запис 
        $rec = new stdClass();
        
        $rec->action      = $action;
        $rec->containerId = $cid;
        $rec->threadId    = $tid;
        $rec->parentId    = $parentId;
        $rec->details     = serialize($details);
        
        if (!in_array($action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN))) {
            $rec->mid = static::generateMid();
        }
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е отпечатал документа) и
         *             createdOn (кога е станало това)
         */
        
        if (static::save($rec)) {
            return $rec->mid;
        }
        
        return FALSE;
        
    }
    
    public static function saveAction($actionData)
    {
        $rec = (object)array_merge((array)static::getAction(), (array)$actionData);
        
        if (empty($rec->parentId)) {
            if (($parentAction = static::getAction(-1)) && !empty($parentAction->id) ) {
                $rec->parentId = $parentAction->id;
            }
        }
        
        expect($rec->containerId && $rec->action);
        
        if (empty($rec->threadId)) {
            expect($rec->threadId = doc_Containers::fetchField($rec->containerId, 'threadId'));
        }

        if (!in_array($rec->action, array(self::ACTION_DISPLAY, self::ACTION_RECEIVE, self::ACTION_RETURN))) {
            $rec->mid = static::generateMid();
        }
        
        /*
         * Забележка: plg_Created ще попълни полетата createdBy (кой е отпечатал документа) и
         *             createdOn (кога е станало това)
         */
        
        if (static::save($rec)) {
            static::getAction()->id = $rec->id;
            
            return $rec->mid;
        }
        
        return FALSE;
    }
    
    
    public static function pushAction($actionData)
    {
        Mode::push('action', (object)$actionData);
    }
    
    
    public static function popAction()
    {
        return Mode::pop('action');
    }

    
    public static function getAction($offset = 0)
    {
        return Mode::get('action', $offset);
    }

    
    public static function hasAction()
    {
        return Mode::get('action');
    }
    
    
    /**
     * Достъпност на документ от не-идентифицирани посетители
     * 
     * @param int $cid key(mvc=doc_Containers)
     * @param string $mid
     * @return object|boolean запис на модела или FALSE
     */
    public static function fetchHistoryFor($cid, $mid)
    {
        $rec = static::fetch(array("#mid = '[#1#]'", $mid));
        
        if (!$rec) {
            $rec = FALSE;
        }
        
        if ($rec && $rec->containerId != $cid) {
            $doc = doc_Containers::getDocument($cid);
            
            //$linkedDocs = $doc->getLinkedDocuments($rec->containerId);
            
            if (!isset($linkedDocs[$cid])) {
                // Временно не правим нищо, докато не реализираме getLinkedDocuments()
                // $rec = FALSE;
            }
        }
        
        return $rec;
    }


    public static function returned($mid, $date = NULL)
    {
        if (!($sendRec = static::fetch(array("#mid = '[#1#]' AND #action = '" . static::ACTION_SEND . "'", $mid)))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->returnedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }

        if (!isset($date)) {
            $date = dt::now();
        }
        
        expect(is_object($sendRec->data), $sendRec);
    
        $sendRec->data->returnedOn = $date;
    
        static::save($sendRec);
    
        $retRec = (object)array(
            'action' => static::ACTION_RETURN,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($retRec);
    
        // Нотификация за връщането на писмото до изпращача му
        bgerp_Notifications::add(
            'Върнати писма', // съобщение
            array('doc_Containers', 'list', 'threadId'=>$sendRec->threadId, 'containerId'=>$sendRec->containerId), // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
    
        return TRUE;
    }


    public static function received($mid, $date = NULL)
    {
        if (!($sendRec = static::fetch(array("#mid = '[#1#]' AND #action = '" . static::ACTION_SEND . "'", $mid)))) {
            // Няма изпращане с такъв MID
            return FALSE;
        }
    
        if (!empty($sendRec->data->receivedOn)) {
            // Връщането на писмото вече е било отразено в историята; не правим нищо
            return TRUE;
        }
    
        if (!isset($date)) {
            $date = dt::now();
        }

        expect(is_object($sendRec->data), $sendRec);
        
        $sendRec->data->receivedOn = $date;
    
        static::save($sendRec);
    
        $rcvRec = (object)array(
            'action' => static::ACTION_RECEIVE,
            'containerId' => $sendRec->containerId,
            'threadId'    => $sendRec->threadId,
            'parentId'    => $sendRec->id
        );
    
        static::save($rcvRec);
    
        // Нотификация за връщането на писмото до изпращача му
        bgerp_Notifications::add(
            'Получени писма', // съобщение
            array('doc_Containers', 'list', 'threadId'=>$sendRec->threadId, 'containerId'=>$sendRec->containerId), // URL
            $sendRec->createdBy, // получател на нотификацията
            'alert' // Важност (приоритет)
        );
    
        return TRUE;
    }
    

    /**
     * Случаен уникален идентификатор на документ
     *
     * @return string
     */
    protected static function generateMid()
    {
        do {
            $mid = str::getRand('Aaaaaaaa');
        } while (static::fetch("#mid = '{$mid}'", 'id'));
    
        return $mid;
    }
    
    static function on_BeforeSave($mvc, &$id, $rec)
    {
        if (empty($rec->data)) {
            $rec->dataBlob = NULL;
        } else {
            if (is_array($rec->data)) {
                $rec->data = (object)$rec->data;
            }
        
            $rec->dataBlob = serialize($rec->data);
        }
    }
    
    
    /**
     * Изпълнява се след всеки запис в модела
     *
     * @param log_Documents $mvc
     * @param int $id key(mvc=log_Documents)
     * @param stdClass $rec запис на модела, който е бил записан в БД
     */
    static function on_AfterSave($mvc, $id, $rec)
    {
        expect($rec->threadId);
        
        // Изчистваме кешираната история на треда, понеже тя току-що е била променена.
        $mvc::removeHistoryFromCache($rec->threadId);
    }
    
    
    /**
     * Подготовка на историята на цяла нишка
     *
     * Данните с историята на треда се кешират, така че многократно извикване с един и същ
     * параметър няма негативен ефект върху производителността.
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array ключ е contanerId, стойност - историята на този контейнер
     */
    protected static function prepareThreadHistory($threadId)
    {
        if (!isset(static::$histories[$threadId])) {
            $cacheKey = static::getHistoryCacheKey($threadId);
        
            if (($history = core_Cache::get(static::CACHE_TYPE, $cacheKey)) === FALSE) {
                // Историята на този тред я няма в кеша - подготвяме я и я записваме в кеша
                $history = static::buildThreadHistory($threadId);
                core_Cache::set(static::CACHE_TYPE, $cacheKey, $history, '2 дена');
            }
            
            static::$histories[$threadId] = $history;
        }
        
        return static::$histories[$threadId];
    }
    
    
    /**
     * Изтрива от кеша записана преди история на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     */
    static function removeHistoryFromCache($threadId)
    {
        $cacheKey = static::getHistoryCacheKey($threadId);
        
        core_Cache::remove(static::CACHE_TYPE, $cacheKey);
    }
    
    /**
     * Ключ, под който се записва историята на нишка в кеша
     *
     * @see core_Cache
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return string
     */
    protected static function getHistoryCacheKey($threadId)
    {
        return $threadId;
    }
    
    /**
     * Преизчислява историята на нишка
     *
     * @param int $threadId key(mvc=doc_Threads)
     * @return array масив с ключ $containerId (на контейнерите от $threadId, за които има запис
     *                  в историята) и стойности - обекти (stdClass) със следната структура:
     *
     *  ->summary => array(
     *         [ACTION1] => брой,
     *         [ACTION2] => брой,
     *         ...
     *     )
     *         
     *  ->containerId - контейнера, чиято история се съдържа в обекта (за удобство)
     */
    protected static function buildThreadHistory($threadId)
    {
        static::log('Регенериране на историята на нишка', $threadId, 3);
        
        $query = static::getQuery();
        $query->where("#threadId = {$threadId}");
        $query->orderBy('#createdOn');
        
        $data = array();   // Масив с историите на контейнерите в нишката
        while ($rec = $query->fetch()) {
            $data[$rec->containerId]->summary[$rec->action] += 1;
            $data[$rec->containerId]->containerId = $rec->containerId;
        }
        
        return $data;
    }
    
    
    /**
     * Подготвя историята на един контейнер
     *
     * @param int $containerId key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Threads)
     */
    protected static function prepareContainerHistory($containerId, $threadId)
    {
        $threadHistory = static::prepareThreadHistory($threadId);
        
        return $threadHistory[$containerId];
    }

    
    /**
     * @todo Чака за документация...
     */
    public static function renderSummary($data)
    {
        static $wordings = NULL;
        
        if (empty($data->summary)) {
            return '';
        }
        
        if (!isset($wordings)) {
            $wordings = array(
                static::ACTION_SEND    => array('изпращане', 'изпращания'),
                static::ACTION_RECEIVE => array('получаване', 'получавания'),
                static::ACTION_RETURN  => array('връщане', 'връщания'),
                static::ACTION_PRINT   => array('отпечатване', 'отпечатвания'),
                static::ACTION_OPEN   => array('показване', 'показвания'),
            );
        }
        
        $html = '';
        
        foreach ($data->summary as $action=>$count) {
            $actionVerbal = $action;
            if (isset($wordings[$action])) {
                $actionVerbal = $wordings[$action][intval($count > 1)];
            }
            
            $link = ht::createLink(
                "<b>{$count}</b> <span>{$actionVerbal}</span>", 
                array(get_called_class(), 'list', 'containerId'=>$data->containerId)
            );
            $html .= "<li class=\"action {$action}\">{$link}</li>";
        }
        
        $html = "<ul class=\"history summary\">{$html}</ul>";
        
        return $html;
    }
    
    
    /**
     * Шаблон (ET) съдържащ историята на документа в този контейнер.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET
     * @deprecated
     */
    public static function getHistory($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        return static::renderHistory($data);
    }
    
    
    /**
     * Шаблон (ET) съдържащ обобщената историята на документа в този контейнер.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET
     */
    public static function getSummary($containerId, $threadId)
    {
        $data = static::prepareContainerHistory($containerId, $threadId);
        
        return static::renderSummary($data);
    }
    
    
    /**
     * Шаблон, съдържащ потребителите и датите, в които документа е бил видян след споделяне.
     *
     * @param int $container key(mvc=doc_Containers)
     * @param int $threadId key(mvc=doc_Thread) нишката,в която е контейнера
     * @return core_ET NULL ако документа не е споделен с никого
     */
    public static function getSharingHistory($containerId, $threadId)
    {
        // Цялата история на документа
        $history = static::prepareContainerHistory($containerId, $threadId);
        
        // С кого е бил споделен този документ?
        $sharedWith = doc_Containers::getShared($containerId);
        
        if ($sharedWith) {
            $sharedWith = type_Keylist::toArray($sharedWith);
        } else {
            $sharedWith = array();
        }
        
        if (count($history->recs)) {
            foreach ($history->recs as $rec) {
                if ($rec->action == 'viewed') {
                    $sharedWith[$rec->createdBy] = $rec->createdOn;
                }
            }
        }
        
        if (count($sharedWith)) {
            $tpl = new core_ET(static::renderSharedHistory($sharedWith));
        } else {
            $tpl = NULL;
        }
        
        return $tpl;
    }
    
    
    /**
     * Помощен метод: рендира историята на споделянията и вижданията
     *
     * @param array $sharedWith масив с ключ ИД на потребител и стойност - дата
     * @return string
     */
    static function renderSharedHistory($sharedWith)
    {
        expect(count($sharedWith));
        
        $first = TRUE;
        $html = '';
        
        $html = array();
        
        foreach ($sharedWith as $userId => $seenDate) {
            $userRec = core_Users::fetch($userId);
            $nick = mb_convert_case(core_Users::getVerbal($userRec, 'nick'), MB_CASE_TITLE, "UTF-8");
            
            if ($userId == $seenDate) {
                $html[] = $nick;
            } else {
                $seenDate = mb_strtolower(core_DateTime::mysql2verbal($seenDate, 'smartTime'));
                $html[] = "<span style='color:black;'>" . $nick . "</span>({$seenDate})";
            }
        }
        
        return implode(', ', $html);
    }
    
    
    /**
     * 
     * @param log_Documents $mvc
     * @param core_Query $query
     */
    static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $mvc->restrictListedActions($data->query);
    }
    
    
    /**
     * @param core_Query $query
     */
    function restrictListedActions($query)
    {
        switch (static::getCurrentSubset()) {
            case static::ACTION_SEND:
                $query->where(sprintf("#action = '%s' OR #action = '%s'", static::ACTION_SEND, static::ACTION_FAX));
                break;
            case static::ACTION_PRINT:
                $query->where(sprintf("#action = '%s' OR #action = '%s'", static::ACTION_PRINT, static::ACTION_PDF));
                break;
        }
    }
    
    
    static function getCurrentSubset()
    {
        if (!$action = Request::get('action')) {
            $action = static::ACTION_SEND;
        }
        
        expect($action == static::ACTION_SEND || $action == static::ACTION_PRINT);
        
        return $action;
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListRows(log_Documents $mvc, $data)
    {
        switch ($mvc::getCurrentSubset()) {
            case $mvc::ACTION_SEND:
                $mvc->currentTab = 'Изпращания';
                $mvc::prepareSendSubset($data);
                break;
            case $mvc::ACTION_PRINT:
                $mvc->currentTab = 'Отпечатвания';
                $mvc::preparePrintSubset($data);
                break;
            default:
                expect(FALSE);
        }

        $data->listFields = arr::make($mvc->listFieldsSet[$mvc::getCurrentSubset()], TRUE);
        
        if (Request::get('containerId', 'int') && isset($data->listFields['containerId'])) {
            unset($data->listFields['containerId']);
        }
    }
    
    
    static function prepareSendSubset($data)
    {
        $rows = $data->rows;
        $recs = $data->recs;
        
        if (empty($data->recs)) {
            return;
        }

        foreach ($recs as $i=>$rec) {
            $row = $rows[$i];
        
            if (!$data->doc) {
                $row->containerId = ht::createLink($row->containerId, array(get_called_class(), 'list', 'containerId'=>$rec->containerId));
            }

            $row->toEmail   = $rec->data->to;
			$row->receivedOn = static::getVerbal($rec, 'receivedOn');
			$row->returnedOn = static::getVerbal($rec, 'returnedOn');
        }
    }
    
    
    static function preparePrintSubset($data)
    {
        $rows = $data->rows;
        $recs = $data->recs;
        
        if (empty($data->recs)) {
            return;
        }
        
        foreach ($recs as $i=>$rec) {
            $row = $rows[$i];
        
            if (!$data->doc) {
                $row->containerId = ht::createLink($row->containerId, array(get_called_class(), 'list', 'containerId'=>$rec->containerId));
            }

            $open = static::ACTION_OPEN;
            $row->seenOn = '';
            if (is_array($rec->data->{$open})) {
                foreach($rec->data->{$open} as $hr) {
                    $rec->seenOnTime = $hr['on'];
                    $rec->seenFromIp = $hr['ip'];
                    $row->seenOn .= '<tr>';
                    $row->seenOn .= '<td>' . static::getVerbal($rec, 'seenOnTime') . '</td>';
                    $row->seenOn .= '<td>' . static::getVerbal($rec, 'seenFromIp') . '</td>';
                    $row->seenOn .= '</tr>';
                }
            }
            
            if (!empty($row->seenOn)) {
                $row->seenOn = "<table>{$row->seenOn}</table>";
            }
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListFields($mvc, $data)
    {
        if ($containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            unset($data->listFields['containerId']);
            $data->query->where("#containerId = {$containerId}");
            $data->doc = doc_Containers::getDocument($containerId, 'doc_DocumentIntf');
        }
        
        $data->query->orderBy('#createdOn', 'DESC');
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterPrepareListTitle($mvc, $data)
    {
        if (!$containerId = Request::get('containerId', 'key(mvc=doc_Containers)')) {
            $data->title = "История";
        }
    }
    

    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTitle(log_Documents $mvc, &$tpl, $data)
    {
        /* @var $doc doc_DocumentIntf */
        $doc = $data->doc;
        
        if ($doc) {
            $row = $doc->getDocumentRow();
            $tpl = new ET('<div class="listTitle">' . $doc->getLink() . '</div>');
        }
    }
    
    
    /**
     * @todo Чака за документация...
     */
    static function on_AfterRenderListTable($mvc, &$tpl, $data)
    {
        if ($data->doc) {
            $tpl->append($data->doc->getDocumentBody());
        }
    }
    
    
    /**
     * Връща cid' а на документа от URL.
     * 
     * Проверява URL' то дали е от нашата система.
     * Проверява дали cid' а и mid'а си съвпадат.
     * Ако открие записа на документа проверява дали има родител.
     * Ако има родител връща cid'а на родителя. 
     * 
     * @param URL $url - URL от системата, в който ще се търси
     * 
     * @return integer $cid - Container id на документа
     */
    static function getDocumentCidFromURL($url)
    {
        // Проверяваме дали URL' то е от нашата система
        if (!static::isOurURL($url)) {
            
            return ;
        }
        
        // Вземаме cid'a и mid' а от URL' то
        $cidAndMidArr = static::getCidAndMidFromUrl($url);
        
        // Ако няма cid или мид
        if (!count($cidAndMidArr)) {
            
            return ;
        }
        
        // Вземам записа за съответния документ в лога
        $rec = log_Documents::fetchHistoryFor($cidAndMidArr['cid'], $cidAndMidArr['mid']);
        
        // Ако няма запис - mid' а не е правилен
        if (!$rec) {
            
            return ;
        }
        
        // Ако записа има parentId
        if ($rec->parentId) {
            
            // Задаваме cid'a да е containerId' то на родителския документ
            $cid = log_Documents::fetchField($rec->parentId, 'containerId');
        } else {
            
            $cid = $rec->containerId;
        }
        
        return $cid;
    }
    
    
    /**
     * Проверява подаденото URL далу е от системата.
     * 
     * @param URL $url - Линка, който ще се проверява
     * 
     * @return boolean - Ако открие съвпадение връща TRUE
     */
    static function isOurURL($url)
    {
        // Изчистваме URL' то от празни символи
        $url = str::trim($url);
        
        // Ако открием търсенто URL в позиция 0
        if (stripos($url, core_App::getBoot(TRUE)) === 0) {
            return TRUE;
        }
        
        return FALSE;
    }
    
    
    /**
     * Връща cid' а и mid' а от подаденото URL
     * 
     * @param URL $URL - Линка, в който ще се търси
     * 
     * @return array $res - Масив с ['cid'] и ['mid']
     */
    static function getCidAndMidFromUrl($url)
    {
        $bootUrl = core_App::getBoot(TRUE);
        
        // Ескейпваме името на директорията. Също така, допълнително ескейпваме и '/'
        $bootUrlEsc = preg_quote($bootUrl, '/');
        
        // Шаблон за намиране на mid'a и cid'а в URL
        // Шаблона работи само ако:
        // Класа е L
        // Екшъна е B или S
        // Веднага след тях следва ?m= за мида
        $pattern = "/(?'boot'{$bootUrlEsc}{1})\/(?'ctr'[L]{1})\/(?'act'[B|S]{1})\/(?'cid'[^\/]+)\/\?m\=(?'mid'[^$]+)/i";

        // Проверявама дали има съвпадение
        preg_match($pattern, $url, $matches);
        
        $res = array();
        
        // Ако намери cid и mid
        if (($matches['cid']) && ($matches['mid'])) {
            
            $res['cid'] = $matches['cid'];
            $res['mid'] = $matches['mid'];
        }

        return $res;
    }
}
