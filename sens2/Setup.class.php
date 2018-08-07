<?php


/**
 * class sens2_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани със сензорите
 *
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens2_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * От кои други пакети зависи
     */
    public $depends = '';
    
    
    /**
     * Начален контролер на пакета за връзката в core_Packs
     */
    public $startCtr = 'sens2_Indicators';
    
    
    /**
     * Начален екшън на пакета за връзката в core_Packs
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Мониторинг на сензори и оборудване';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'sens2_Indicators',
        'sens2_DataLogs',
        'sens2_Controllers',
        'sens2_Scripts',
        'sens2_ScriptActions',
        'sens2_ScriptDefinedVars',
        'sens2_IOPorts',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'sens';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.4, 'Мониторинг', 'Сензори', 'sens2_Indicators', 'default', 'sens, ceo,admin'),
    );
    
    
    /**
     * Дефинирани класове, които имат интерфейси
     */
    public $defClasses = array(
        'sens2_reports_DataLog',
        'sens2_MockupDrv',
        'sens2_ServMon',
        'sens2_ScriptActionAssign',
        'sens2_ScriptActionSignal',
        'sens2_ScriptActionSMS',
        'sens2_ScriptActionNotify',
        'sens2_ioport_AI',
        'sens2_ioport_DI',
        'sens2_ioport_DO',
        'sens2_ioport_AO',
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $rec = new stdClass();
        $rec->systemId = 'sens2_UpdateIndications';
        $rec->description = 'Взима данни от активни сензори';
        $rec->controller = 'sens2_Controllers';
        $rec->action = 'Update';
        $rec->period = 1;
        $rec->offset = 0;
        $rec->timeLimit = 55;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'sens2_RunScripts';
        $rec->description = 'Изпълнява всички скриптове';
        $rec->controller = 'sens2_Scripts';
        $rec->action = 'RunAll';
        $rec->period = 1;
        $rec->delay = 15;
        $rec->timeLimit = 30;
        $html .= core_Cron::addOnce($rec);
        
        $rec = new stdClass();
        $rec->systemId = 'sens2_RunScripts2';
        $rec->description = 'Изпълнява всички скриптове';
        $rec->controller = 'sens2_Scripts';
        $rec->action = 'RunAll';
        $rec->period = 1;
        $rec->delay = 45;
        $rec->timeLimit = 30;
        $html .= core_Cron::addOnce($rec);
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    public function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
