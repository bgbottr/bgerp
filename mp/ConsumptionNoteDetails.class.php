<?php


/**
 * Клас 'mp_ConsumptionNormDetails'
 *
 * Детайли на мениджър на детайлите на протокола за влагане
 *
 * @category  bgerp
 * @package   mp
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class mp_ConsumptionNoteDetails extends deals_ManifactureDetail
{
    
    
    /**
     * Заглавие
     */
    public $title = 'Детайли на протокола за влагане';


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
    public $listFields = 'productId, measureId, quantity';
    
        
    /**
     * Активен таб
     */
    public $currentTab = 'Протоколи->Влагане';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    public $rowToolsField = 'RowNumb';
    
    
    /**
     * Какви продукти да могат да се избират в детайла
     *
     * @var enum(canManifacture=Производими,canConvert=Вложими)
     */
    protected $defaultMeta = 'canConvert';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('noteId', 'key(mvc=mp_ConsumptionNotes)', 'column=none,notNull,silent,hidden,mandatory');
        
        parent::setDetailFields($this);
        
        // Само вложими продукти
        $this->setDbUnique('noteId,productId,classId');
    }
}
