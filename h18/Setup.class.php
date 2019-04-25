<?php



/**
 * Потребител
 */
defIfNot('H18_BGERP_USER', 'root');


/**
 * Парола
 */
defIfNot('H18_BGERP_PASS', '321');

/**
 * База
 */
defIfNot('H18_BGERP_DATABASE', 'bgerp');


/**
 * Адрес на хост
 */
defIfNot('H18_BGERP_HOST', 'localhost');



/**
 * Клас 'H18_Setup' - Начално установяване на пакета 'H18'
 *
 *
 * @category  bgerp
 * @package   H18
 * @author    Dimitar Minekov<mitko@extrapack.com>
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class h18_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Описание на модула
     */
    public $info = "Достъп до таблиците изисквани по наредба 18";
    
    
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array (
        
        'H18_BGERP_USER'     => array ('varchar', 'caption=Данни за базата->Потребител'),
        'H18_BGERP_DATABASE' => array ('varchar', 'caption=Данни за базата->Име на база'),
        'H18_BGERP_PASS'     => array ('password', 'caption=Данни за базата->Парола'),
        'H18_BGERP_HOST'     => array ('varchar', 'caption=Данни за базата->Хост'),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
//        'h18_CashRko',
//        'h18_CashPko'
    );
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(1.91, 'Система', 'H18', 'h18_CashRko', 'default', "admin"),
    );
    
    /**
     * Инсталиране на пакета
     */
    function install()
    {

        $html = parent::install();
        
        
        return $html;
    }
    

    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
}
