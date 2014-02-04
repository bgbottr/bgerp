<?php


/**
 * Версия на JS компонента
 */
defIfNot('AUTOSIZE_VERSION', 'v1.18.4');


/**
 *
 */
defIfNot('AUTOSIZE_MAX_ROWS_WIDE', '600');


/**
 *
 */
defIfNot('AUTOSIZE_MAX_ROWS_NARROW', '400');


/**
 * Клас 'jqdatepick_Setup' -
 *
 *
 * @category  vendors
 * @package   autosize
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class autosize_Setup extends core_ProtoSetup 
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = '';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = '';
    
    
    /**
     * Описание на модула
     */
    var $info = "Височина според съдържанието за textarea";
    
    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(
        
           'AUTOSIZE_VERSION' => array ('enum(1=v1.18.4)', 'mandatory, caption=Версията на програмата->Версия'),
           'AUTOSIZE_MAX_ROWS_WIDE' => array ('int', 'mandatory, caption=Максимален брой редове->Десктоп режим'),
           'AUTOSIZE_MAX_ROWS_NARROW' => array ('int', 'mandatory, caption=Максимален брой редове->Мобилен режим'),
             );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Инсталираме клавиатурата към password полета
        $html .= $Plugins->installPlugin('Редове на текст', 'autosize_Plugin', 'type_Richtext', 'private');
        
        return $html;
    }
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
    	$html = parent::deinstall();
    	
        // Зареждаме мениджъра на плъгините
        $Plugins = cls::get('core_Plugins');
        
        // Премахваме от type_Date полета
        $Plugins->deinstallPlugin('autosize_Plugin');
        $html .= "<li>Премахнати са всички инсталации на 'autosize_Plugin'";
        
        return $html;
    }
}