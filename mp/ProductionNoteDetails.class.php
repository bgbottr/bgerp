<?php


/**
 * Клас 'mp_ProductionNormDetails'
 *
 * Детайли на мениджър на детайлите на протокола за производство
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_ProductionNoteDetails extends deals_ManifactureDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола от производство';


    /**
     * Заглавие в единствено число
     */
    public $singleTitle = 'Продукт';
    
    
    /**
     * Име на поле от модела, външен ключ към мастър записа
     */
    public $masterKey = 'noteId';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_RowTools, plg_Created, mp_Wrapper, plg_RowNumbering, plg_AlignDecimals';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'ceo, mp';
    
    
    /**
     * Кой има право да променя?
     */
    public $canEdit = 'ceo, mp';
    
    
    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'ceo, mp';
    
    
    /**
     * Кой може да го изтрие?
     */
    public $canDelete = 'ceo, mp';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    public $listFields = 'productId, jobId, bomId, measureId, quantity, selfValue,amount';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Производство';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=mp_ProductionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
        
        $this->FLD('jobId', 'key(mvc=mp_Jobs)', 'input=none,caption=Задание');
        $this->FLD('bomId', 'key(mvc=techno2_Boms)', 'input=none,caption=Рецепта');
        
        $this->FLD('selfValue', 'double', 'caption=С-ст,input=hidden');
        $this->FNC('amount', 'double', 'caption=Сума');
        
        $this->setDbUnique('noteId,productId,classId');
    }
    
    
    /**
     * Изчисляване на сумата на реда
     */
    public static function on_CalcAmount($mvc, $rec)
    {
    	if(empty($rec->quantity) || empty($rec->selfValue)) return;
    	
    	$rec->amount = $rec->quantity * $rec->selfValue;
    }
    
    
    /**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     */
    public static function on_AfterInputEditForm(core_Mvc $mvc, core_Form $form)
    {
    	$rec = &$form->rec;
    	
    	// Да се показвали полети за себестойност
    	$showSelfvalue = TRUE;
    	
    	if($rec->productId){
    		
    		if(cls::get($rec->classId) instanceof techno2_SpecificationDoc){
    			
    			// Имали активно задание за артикула ?
    			if($jobId = techno2_SpecificationDoc::getLastActiveJob($rec->productId)->id){
    				$rec->jobId = $jobId;
    			}
    			
    			// Имали активна рецепта за артикула ?
    			if($bomRec = techno2_SpecificationDoc::getLastActiveBom($rec->productId)){
    				$rec->bomId = $bomRec->id;
    			}
    			
    			// Не показваме полето за себестойност ако активна рецепта и задание
    			if(isset($rec->jobId) && isset($rec->bomId)){
    				$showSelfvalue = FALSE;
    			}
    		}
    		
    		$masterValior = $mvc->Master->fetchField($form->rec->noteId, 'valior');
    		$form->setField('selfValue', "unit=" . acc_Periods::getBaseCurrencyCode($masterValior));
    		
    		// Скриваме полето за себестойност при нужда
    		if($showSelfvalue === FALSE){
    			$form->setField('selfValue', 'input=none');
    		} else {
    			$form->setField('selfValue', 'input,mandatory');
    		}
    	}
    	
    	if($form->isSubmitted()){
    		
    		// Ако трябва да показваме с-та, но не е попълнена сетваме грешка
    		if(empty($rec->selfValue) && $showSelfvalue === TRUE){
    			$form->setError('selfValue', 'Непопълнено задължително поле|* <b>С-ст</b>');
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
    public static function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
    	if(isset($rec->jobId)){
    		$row->jobId = "#" . cls::get('mp_Jobs')->getHandle($rec->jobId);
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    			$row->jobId = ht::createLink($row->jobId, array('mp_Jobs', 'single', $rec->jobId));
    		}
    	}
    	
    	if(isset($rec->bomId)){
    		$row->bomId = "#" . cls::get('techno2_Boms')->getHandle($rec->bomId);
    		if(!Mode::is('printing') && !Mode::is('text', 'xhtml')){
    			$row->bomId = ht::createLink($row->bomId, array('techno2_Boms', 'single', $rec->bomId));
    		}
    	}
    }
    
    
    /**
     * След преобразуване на записа в четим за хора вид.
     */
    protected static function on_AfterPrepareListRows($mvc, &$res)
    {
    	$rows = &$res->rows;
    	$recs = &$res->recs;
    
    	$hasBomFld = $hasJobFld = FALSE;
    	
    	if (count($recs)) {
    		foreach ($recs as $id=>$rec) {
    			$row = &$rows[$id];
    
    			$hasJobFld = !empty($rec->jobId) ? TRUE : $hasJobFld;
    			$hasBomFld = !empty($rec->bomId) ? TRUE : $hasBomFld;
    		}
    		 
    		if($hasJobFld === FALSE){
    			unset($res->listFields['jobId']);
    		}
    		
    		if($hasBomFld === FALSE){
    			unset($res->listFields['bomId']);
    		}
    	}
    }
}