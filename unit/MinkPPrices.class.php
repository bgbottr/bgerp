<?php


/**
 *  Клас  'unit_MinkPPrices' - PHP тестове за ценообразуване и ценоразписи
 *
 * @category  bgerp
 * @package   tests
 *
 * @author    Pavlinka Dainovska <pdainovska@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class unit_MinkPPrices extends core_Manager
{
    /**
     * Стартира последователно тестовете от MinkPPrices
     */
    //http://localhost/unit_MinkPPrices/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            
            return;
        }
        
        $res = '';
        $res .= '<br>'.'MinkPPrices';
        $res .= '  1.'.$this->act_EditPriceList();
        $res .= '  2.'.$this->act_AddPriceList();
        $res .= '  3.'.$this->act_AddPriceListDoc();
        $res .= '  4.'.$this->act_SetCustomerPriceList();
        $res .= '  5.'.$this->act_AddCustomerPriceList();
        
        return $res;
    }
    
    
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        
        //$browser->start('http://localhost/');
        $host = unit_Setup::get('DEFAULT_HOST');
        $browser->start($host);
        
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        sleep(2);
        
        return $browser;
    }
    
    
    /**
     * 1. Редакция на ценова политика
     */
    //http://localhost/unit_MinkPPrices/EditPriceList/
    public function act_EditPriceList()
    {
        // Логване
        $browser = $this->SetUp();
        
        // Отваряне на Ценова политика "Каталог"
        $browser->click('Всички');
        $browser->click('Проекти');
        $browser->click('Ценови политики');
        $browser->press('Папка');
        $browser->click('Ценова политика "Каталог"');
        
        //Задаване на цена
        $browser->press('Стойност');
        $browser->setValue('productId', 'Плик 7 л');
        $browser->setValue('price', '0.6');
        $enddate = strtotime('+10 Days');
        $browser->setValue('validUntil[d]', date('d-m-Y', $enddate));
        $browser->press('Запис');
        if (strpos($browser->gettext(), '0,60 BGN с ДДС')) {
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешно заредена цена', 'warning');
        }
        
        //Задаване на цена без ДДС на артикул от ДДС група 9%
        $browser->press('Стойност');
        $browser->setValue('productId', 'Артикул ДДС 9');
        $browser->setValue('price', '10');
        $browser->setValue('validUntil[d]', null);
        $browser->setValue('vat', 'no');
        $browser->press('Запис');
        
        //Задаване на цена - марж
        $browser->press('Продуктов марж');
        $browser->setValue('productId', 'Труд');
        $browser->setValue('targetPrice', '17,28');
        $enddate = strtotime('+30 Days');
        $browser->setValue('validUntil[d]', date('d-m-Y', $enddate));
        $browser->press('Запис');
        if (strpos($browser->gettext(), '[Себестойност] + 44,00')) {
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешно заредена цена по марж', 'warning');
        }
        
        //Задаване на групов марж
        $browser->press('Групов марж');
        $browser->setValue('groupId', 'Ценова група » Промоция');
        $browser->setValue('discount', '11');
        $fromdate = strtotime('+3 Days');
        $browser->setValue('validFrom[d]', date('d-m-Y', $fromdate));
        $browser->setValue('validFrom[t]', '10:00');
        $enddate = strtotime('+33 Days');
        $browser->setValue('validUntil[d]', date('d-m-Y', $enddate));
        $browser->setValue('validUntil[t]', '18:30');
        $browser->press('Запис');
        if (strpos($browser->gettext(), '[Себестойност] + 11,00')) {
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешно зареден групов марж', 'warning');
        }
    }
    
    
    /**
     * 2. Добавяне на ценова политика (от папка на проект)
     */
    //http://localhost/unit_MinkPPrices/AddPriceList/
    public function act_AddPriceList()
    {
        // Логване
        $browser = $this->SetUp();
        
        // Отваряне на папка Ценова политика
        $browser->click('Всички');
        $browser->click('Проекти');
        $browser->click('Ценови политики');
        $browser->press('Папка');
        $browser->press('Ценова политика');
        
        //Добавяне на ценова политика
        $browser->setValue('title', 'Ценова политика 2017');
        $browser->setValue('parent', 'Каталог');
        $browser->setValue('discountCompared', 'Каталог');
        $browser->setValue('significantDigits', '4');
        $browser->setValue('defaultSurcharge', '7');
        $browser->setValue('minSurcharge', '15');
        $browser->setValue('maxSurcharge', '19');
        $browser->press('Запис');
        
        if (strpos($browser->gettext(), 'Надценка по подразбиране 7,00')) {
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешна надценка', 'warning');
        }
    }
    
    
    /**
     * 3. Създаване на ценоразпис от папка на проект
     */
    //http://localhost/unit_MinkPPrices/AddPriceListDoc/
    public function act_AddPriceListDoc()
    {
        // Логване
        $browser = $this->SetUp();
        
        //създаване на ценоразпис 
        $browser->click('Всички');
        $browser->click('Други проекти');
        $browser->press('Справка');
        $browser->setValue('driverClass', 'Продажби » Ценоразпис');
        $browser->press('Refresh');   
        $browser->setValue('policyId', 'Ценова политика 2017');
        $browser->press('Запис');
        
        //if (strpos($browser->gettext(), 'Труд час 18,48960')) {
        if (strpos($browser->gettext(), '18,48960')) {
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешен ценоразпис 1', 'warning');
        }
        
        //if (strpos($browser->gettext(), 'Артикул ДДС 9 	бр.	11,66300')) {
        if (strpos($browser->gettext(), '11,66300')) {
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешен ценоразпис 2', 'warning');
        }
    }
    
    
    /**
     * 4. Избор на ценова политика за клиент
     */
    //http://localhost/unit_MinkPPrices/SetCustomerPriceList/
    public function act_SetCustomerPriceList()
    {
        // Логване
        $browser = $this->SetUp();
        
        // Отваряне на корицата на клиент
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->click('Търговия');
        
        // Избор на ценова политика "Каталог" за клиента
        $browser->click('Избор на ценова политика');
        $fromdate = strtotime('+1 Day');
        $browser->setValue('validFrom[d]', date('d-m-Y', $fromdate));
        $browser->setValue('validFrom[t]', '08:00');
        $browser->press('Запис');
    }
    
    
    /**
     * 5. Добавяне на ценова политика в папка на клиент; ценоразпис
     */
    //http://localhost/unit_MinkPPrices/AddCustomerPriceList/
    public function act_AddCustomerPriceList()
    {
        // Логване
        $browser = $this->SetUp();
        
        // Отваряне на корицата на клиент
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма с локация';
        $browser->click($Company);
        $browser->click('Търговия');
        
        // Създаване на ценова политика за клиента
        $browser->click('Избор на ценова политика');
        $browser->press('Нови правила');
        $browser->setValue('folderId', 'Фирма с локация - България');
        $browser->press('Напред');
        $browser->setValue('title', 'Ценова политика за Фирма с локация');
        $browser->setValue('parent', 'Ценова политика 2017');
        $browser->setValue('discountCompared', 'Каталог');
        $browser->setValue('defaultSurcharge', '3');
        $browser->press('Чернова');
        
        //Отваряне на папката на клиента
        $browser->click($Company);
         
        $browser->press('Нов');
        
        // Създаване на ценоразпис в папката на клиента
        $browser->press('Справка');
        
        $browser->setValue('source', 'Ценоразпис');
        $browser->press('Refresh');  
        //$browser->setValue('driverClass', 'Продажби » Ценоразписи');
        $browser->setValue('policyId', 'Ценова политика за Фирма с локация');
        $browser->press('Запис');
        
        if (strpos($browser->gettext(), '0,66126')) {	
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешен ценоразпис 1', 'warning');
        }
        
        //Проверка за ДДС 9%
        if (strpos($browser->gettext(), '12,01289')) {
        } else {
            
            return unit_MinkPbgERP::reportErr('Грешен ценоразпис 2', 'warning');
        }
    }
}
