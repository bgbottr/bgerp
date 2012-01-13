<?php


/**
 * Мениджър за съобщенията на сензорите
 *
 *
 * @category  bgerp
 * @package   sens
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Сензори
 */
class sens_MsgLog extends core_Manager
{
    
    
    /**
     * Необходими мениджъри
     */
    var $loadList = 'plg_RowTools, plg_Sorting,sens_Wrapper,
                      plg_RefreshRows';
    
    
    
    /**
     * Заглавие
     */
    var $title = 'Съобщения от сензорите';
    
    
    
    /**
     * На колко време ще се ъпдейтва листа
     */
    var $refreshRowsTime = 15000;
    
    
    
    /**
     * Права за запис
     */
    var $canWrite = 'sens, admin';
    
    
    
    /**
     * Права за четене
     */
    var $canRead = 'sens, admin';
    
    
    
    /**
     * Брой записи на страница
     */
    var $listItemsPerPage = 100;
    
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('sensorId', 'key(mvc=sens_Sensors, select=title, allowEmpty)', 'caption=Сензор');
        $this->FLD('message', 'varchar(255)', 'caption=Съобщение');
        $this->FLD('priority', 'enum(normal=Информация,warning=Предупреждение,alert=Аларма)', 'caption=Важност');
        $this->FLD('time', 'datetime', 'caption=Време');
    }
    
    
    
    /**
     * Добавя запис в логовете
     */
    function add($sensorId, $message, $priority)
    {
        $rec = new stdClass();
        $rec->sensorId = $sensorId;
        $rec->message = $message;
        $rec->priority = $priority;
        $rec->time = dt::verbal2mysql();
        
        sens_MsgLog::save($rec);
    }
    
    
    
    /**
     * Сортиране DESC - последния запис да е най-отгоре
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     * @param stdClass $data
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#time', 'DESC');
    }
    
    
    
    /**
     * Оцветяваме записите в зависимост от приоритета събитие
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    function on_AfterRecToVerbal($mvc, &$row, $rec)
    {
        $msgColors = array( 'normal' => '#ffffff',
            'warning' => '#fff0f0',
            'alert' => '#ffdddd'
        );
        // Променяме цвета на реда в зависимост от стойността на $row->statusAlert
        $row->ROW_ATTR['style'] .= "background-color: ". $msgColors[$rec->priority] . ";";
    }
    
    
    
    /**
     * Добавя филтър за съобщенията на сензорите
     */
    function on_AfterPrepareListFilter($mvc, $data)
    {
        
        $data->listFilter->showFields = 'sensorId,priority';
        
        $data->listFilter->toolbar->addSbBtn('Филтър');
        
        $data->listFilter->view = 'horizontal';
        
        $rec = $data->listFilter->input();
        
        if ($rec) {
            if($rec->sensorId) {
                $data->query->where("#sensorId = {$rec->sensorId}");
            }
            
            if($rec->priority) {
                $data->query->where("#priority = '{$rec->priority}'");
            }
        }
    }
}