<?php
/**
 *  Изглед за фактурата
 */
defIfNot('INV_LAYOUT', 'sales/tpl/SingleLayoutInvoice');

/**
 * Изглед на header-а на оферта
 */
defIfNot('QUOTE_LAYOUT', 'Letter');


/**
 * Максимален срок за бъдещи цени с които да работи офертата
 */
defIfNot('SALE_MAX_FUTURE_PRICE', type_Time::SECONDS_IN_MONTH);


/**
 * Максимален срок за минали цени с които да работи офертата
 */
defIfNot('SALE_MAX_PAST_PRICE', type_Time::SECONDS_IN_MONTH * 2);


/**
 * Покупки - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   sales
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class sales_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'sales_Sales';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Продажби на продукти и стоки";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'INV_LAYOUT' => array ("enum(sales/tpl/SingleLayoutInvoice=Основен изглед,sales/tpl/SingleLayoutInvoice2=Изглед за писмо)", 'caption=Изглед за фактурата->Шаблон'),
			'QUOTE_LAYOUT' => array ("enum(Normal=Основен изглед,Letter=Изглед за писмо)", 'caption=Изглед за оферта->Шаблон'),
			'SALE_MAX_FUTURE_PRICE' => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Продажби->Ценови период в бъдещето'),
			'SALE_MAX_PAST_PRICE' => array("time(uom=months,suggestions=1 месец|2 месеца|3 месеца)", 'caption=Продажби->Ценови период в миналото'),
	);
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
            'sales_Invoices',
            'sales_InvoiceDetails',
            'sales_Sales',
            'sales_SalesDetails',
        	'sales_Routes',
        	'sales_SalesLastPricePolicy',
        	'sales_Quotations',
        	'sales_QuotationsDetails',
    		'sales_SaleRequests',
    		'sales_SaleRequestDetails',
    		//'sales_ClosedDeals',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'sales';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Продажби', 'sales_Sales', 'default', "sales, ceo"),
        );

        
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res .= bgerp_Menu::remove($this);
        
        return $res;
    }
}
