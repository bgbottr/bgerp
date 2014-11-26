<?php


/**
 * Колко секунди да се кешира съдържанието за не PowerUsers
 */
defIfNot('ESHOP_BROWSER_CACHE_EXPIRES', 3600);


/**
 *
 */


/**
 * class cat_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с продуктите
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class eshop_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версията на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'eshop_Groups';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Web каталог";
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'eshop_Groups',
            'eshop_Products',
    		'migrate::migrateDrivers'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'eshop';
 
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.50, 'Сайт', 'Е-маг', 'eshop_Groups', 'default', "ceo, eshop"),
        );
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
            'ESHOP_BROWSER_CACHE_EXPIRES' => array ('time', 'caption=Кеширане в браузъра->Време'),
	);

    
    /**
     * Инсталиране на пакета
     */
    function install()
    {
        $html = parent::install();
        
        // Кофа за снимки
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('eshopImages', 'Илюстрации в емаг', 'jpg,jpeg,png,bmp,gif,image/*', '3MB', 'user', 'every_one');
        
        return $html;
    }
    
           
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Миграция от старите към новите драйвери
     */
    private function migrateDrivers()
    {
    	$dId = cat_GeneralProductDriver::getClassId();
    	 
    	$pQuery = eshop_Products::getQuery();
    	$pQuery->where("#coDriver IS NOT NULL");
    	while($pRec = $pQuery->fetch()){
    		$pRec->coDriver = $dId;
    		eshop_Products::save($pRec, 'coDriver');
    	}
    }
}
