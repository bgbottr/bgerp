<?php


/**
 * Клас 'planning_ConsumptionNotes' - Документ за Протокол за влагане в производството
 *
 * @category  bgerp
 * @package   planning
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class planning_ConsumptionNotes extends deals_ManifactureMaster
{
    /**
     * Заглавие
     */
    public $title = 'Протоколи за влагане в производство';
    
    
    /**
     * Име на документа в бързия бутон за добавяне в папката
     */
    public $buttonInFolderTitle = 'Влагане';
    
    
    /**
     * Абревиатура
     */
    public $abbr = 'Mcn';
    
    
    /**
     * Поддържани интерфейси
     */
    public $interfaces = 'acc_TransactionSourceIntf=planning_transaction_ConsumptionNote';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools2, store_plg_StoreFilter, deals_plg_SaveValiorOnActivation, planning_Wrapper, acc_plg_DocumentSummary, acc_plg_Contable,
                    doc_DocumentPlg, plg_Printing, plg_Clone, deals_plg_SetTermDate, plg_Sorting,deals_plg_EditClonedDetails,cat_plg_AddSearchKeywords, plg_Search';
    
    
    /**
     * Полета от които се генерират ключови думи за търсене (@see plg_Search)
     */
    public $searchFields = 'storeId,note';
    
    
    /**
     * Кой има право да чете?
     */
    public $canConto = 'ceo,planning,store';
    
    
    /**
     * Кой може да го прави документа чакащ/чернова?
     */
    public $canPending = 'ceo,planning,store';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,planning,store';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,planning,store';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo,planning,store';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo,planning,store';
    
    
    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Протокол за влагане в производство';
    
    
    /**
     * Файл за единичния изглед
     */
    public $singleLayoutFile = 'planning/tpl/SingleLayoutConsumptionNote.shtml';
    
    
    /**
     * Файл за единичния изглед в мобилен
     */
    public $singleLayoutFileNarrow = 'planning/tpl/SingleLayoutConsumptionNoteNarrow.shtml';
    
    
    /**
     * Групиране на документите
     */
    public $newBtnGroup = '3.5|Производство';
    
    
    /**
     * Детайл
     */
    public $details = 'planning_ConsumptionNoteDetails';
    
    
    /**
     * Кой е главния детайл
     *
     * @var string - име на клас
     */
    public $mainDetail = 'planning_ConsumptionNoteDetails';
    
    
    /**
     * Записите от кои детайли на мениджъра да се клонират, при клониране на записа
     *
     * @see plg_Clone
     */
    public $cloneDetails = 'planning_ConsumptionNoteDetails';
    
    
    /**
     * Хипервръзка на даденото поле и поставяне на икона за индивидуален изглед пред него
     */
    public $rowToolsSingleField = 'title';
    
    
    /**
     * Икона на единичния изглед
     */
    public $singleIcon = 'img/16/produce_in.png';
    
    
    /**
     * Поле за филтриране по дата
     */
    public $filterDateField = 'createdOn, valior,deadline,modifiedOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        parent::setDocumentFields($this);
        $this->FLD('departmentId', 'key(mvc=planning_Centers,select=name,allowEmpty)', 'caption=Ц-р на дейност,before=note');
        $this->FLD('useResourceAccounts', 'enum(yes=Да,no=Не)', 'caption=Детайлно влагане->Избор,notNull,default=yes,maxRadio=2,before=note');
    }
    
    
    /**
     * Преди показване на форма за добавяне/промяна
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        $data->form->setDefault('useResourceAccounts', planning_Setup::get('CONSUMPTION_USE_AS_RESOURCE'));
        
        $folderCover = doc_Folders::getCover($data->form->rec->folderId);
        if ($folderCover->isInstanceOf('planning_Centers')) {
            $data->form->setDefault('departmentId', $folderCover->that);
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
        $row->useResourceAccounts = ($rec->useResourceAccounts == 'yes') ? 'Артикулите ще бъдат вкарани в производството по артикули' : 'Артикулите ще бъдат вложени в производството сумарно';
        $row->useResourceAccounts = tr($row->useResourceAccounts);
        
        if (isset($rec->departmentId)) {
            $row->departmentId = planning_Centers::getHyperlink($rec->departmentId, true);
        }
    }
    
    
    /**
     * След подготовка на тулбара на единичен изглед
     */
    protected static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = &$data->rec;
        
        if ($rec->state == 'active' && planning_ReturnNotes::haveRightFor('add', (object) array('originId' => $rec->containerId, 'threadId' => $rec->threadId))) {
            $data->toolbar->addBtn('Връщане', array('planning_ReturnNotes', 'add', 'originId' => $rec->containerId, 'storeId' => $rec->storeId, 'ret_url' => true), null, 'ef_icon = img/16/produce_out.png,title=Връщане на артикули от производството');
        }
    }
    
    
    /**
     * Какво да е предупреждението на бутона за контиране
     *
     * @param int    $id         - ид
     * @param string $isContable - какво е действието
     *
     * @return NULL|string - текста на предупреждението или NULL ако няма
     */
    public function getContoWarning_($id, $isContable)
    {
        $rec = $this->fetchRec($id);
        $dQuery = planning_ConsumptionNoteDetails::getQuery();
        $dQuery->where("#noteId = {$id}");
        $dQuery->show('productId, quantity');
        
        $warning = deals_Helper::getWarningForNegativeQuantitiesInStore($dQuery->fetchAll(), $rec->storeId, $rec->state);
        
        return $warning;
    }
}
