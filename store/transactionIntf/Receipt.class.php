<?php
/**
 * Помощен клас-имплементация на интерфейса acc_TransactionSourceIntf за класа store_Receipts
 *
 * @category  bgerp
 * @package   sales
 * @author    Ivelin Dimov <ivelin_pdimov@abv.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * 
 * @see acc_TransactionSourceIntf
 *
 */
class store_transactionIntf_Receipt
{
    /**
     * 
     * @var purchase_Purchases
     */
    public $class;
    
    
    /**
     * Генериране на счетоводните транзакции, породени от складова разписка
     * Заприхождаване на артикул: Dt:602 или Dt:302 или Dt:321
     *
     *    Dt: 602 - Разходи за външни услуги  (Услуги) - за услугите
     *	  Dt: 302 - Суровини и материали 	  (Склад, Суровини и Материали) - за вложимите продукти
     *	  Dt: 321 - Стоки и Продукти 		  (Склад, Стоки и Продукти) - за всички останали складируеми продукти
     *
     *    Ct: 401 - Задължения към доставчици (Доставчик, Валути)
     *
     *    Цените, по които се заприхождават продуктите от с/ка 321 са според зададената стратегия 
     *
     * @param int|object $id първичен ключ или запис на покупка
     * @return object NULL означава, че документа няма отношение към счетоводството, няма да генерира
     *                счетоводни транзакции
     * @throws core_exception_Expect когато възникне грешка при генерирането на транзакция               
     */
    public function getTransaction($id)
    {
        $entries = array();
        
        $rec = $this->fetchShipmentData($id);
            
        // Всяка СР трябва да има поне един детайл
        if (count($rec->details) > 0) {
                
            if($rec->storeId){
            	// Записите от тип 2 (заприхождаване)
            	$entries = $this->getDeliveryPart($rec);
            }
        }
        
        $transaction = (object)array(
            'reason'  => 'Складова разписка #' . $rec->id,
            'valior'  => $rec->valior,
            'entries' => $entries, 
        );
        
        return $transaction;
    }
    
    
    /**
     * Финализиране на транзакцията
     */
    public function finalizeTransaction($id)
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->state = 'active';
        
        if ($this->class->save($rec)) {
            $this->class->invoke('Activation', array($rec));
        }
        
        // Нотификация към пораждащия документ, че нещо във веригата му от породени документи се е променило.
        if ($origin = $this->class->getOrigin($rec)) {
            $rec = new core_ObjectReference($this->class, $rec);
            $origin->getInstance()->invoke('DescendantChanged', array($origin, $rec));
        }
    }
    
    
    /**
     * Помощен метод за извличане на данните на СР - мастър + детайли
     * 
     * Детайлите на СР (продуктите) са записани в полето-масив 'details' на резултата 
     * 
     * @param int|object $id първичен ключ или запис на СР
     * @param object запис на СР (@see store_Receipts)
     */
    protected function fetchShipmentData($id)
    {
        $rec = $this->class->fetchRec($id);
        
        $rec->details = array();
        
        if (!empty($rec->id)) {
            // Извличаме детайлите на покупката
            $detailQuery = store_ReceiptDetails::getQuery();
            $detailQuery->where("#receiptId = '{$rec->id}'");
            $rec->details  = array();
            
            while ($dRec = $detailQuery->fetch()) {
                $rec->details[] = $dRec;
            }
        }
        
        return $rec;
    }
    
    
    /**
     * Помощен метод - генерира доставната част от транзакцията за покупка
     * Вкарване на стоката в склада (в някои случаи)
     *
     *	  Dt: 602 - Разходи за външни услуги  (Услуги) - за услугите
     *	  Dt: 302 - Суровини и материали 	  (Склад, Суровини и Материали) - за вложимите продукти
     *	  Dt: 321 - Стоки и Продукти 		  (Склад, Стоки и Продукти) - за всички останали складируеми продукти
     *
     *    Ct: 401 - Задължения към доставчици (Доставчик, Валути)
     *    
     * @param stdClass $rec
     * @return array
     */
    protected function getDeliveryPart($rec)
    {
        $entries = array();
        
        expect($rec->storeId, 'Генериране на експедиционна част при липсващ склад!');
        $currencyRate = $this->getCurrencyRate($rec);
        $currencyCode = ($rec->currencyId) ? $rec->currencyId : $this->class->fetchField($rec->id, 'currencyId');
        $currencyId   = currency_Currencies::getIdByCode($currencyCode);
        
        foreach ($rec->details as $detailRec) {
        	$pInfo = cat_Products::getProductInfo($detailRec->productId);
        	
        	// Ако е складируем, дебит 602
        	if(!isset($pInfo->meta['canStore'])) {
        		$debit = array(
                    '602', // Сметка "602. Разходи за външни услуги"
                        array($detailRec->classId, $detailRec->productId), // Перо 1 - Артикул
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                );
        	} else {
        		
        		// Ако е вложим дебит 302 иначе 321
        		$debitAccId = (isset($pInfo->meta['canConvert'])) ? '302' : '321';
        		
        		$debit = array(
                    $debitAccId, // Сметка "302. Суровини и материали" или Сметка "321. Стоки и Продукти"
                        array('store_Stores', $rec->storeId), // Перо 1 - Склад
                    	array($detailRec->classId, $detailRec->productId),  // Перо 2 - Артикул
                    'quantity' => $detailRec->quantity, // Количество продукт в основната му мярка
                );
        	}
        	
        	$entries[] = array(
        		 'amount' => $detailRec->amount * $currencyRate,
        		 'debit'  => $debit,
	             'credit' => array(
	                   '401', // Сметка "401. Задължения към доставчици (Доставчик, Валути)"
                       array($rec->contragentClassId, $rec->contragentId), // Перо 1 - Доставчик
                       array('currency_Currencies', $currencyId),          // Перо 2 - Валута
                    'quantity' => $detailRec->amount, // "брой пари" във валутата на продажбата
	             ),
	        );
        }
        
        return $entries;
    }
    
    
    /**
     * Курс на валутата на покупката към базовата валута за периода, в който попада продажбата
     * 
     * @param stdClass $rec запис на покупка
     * @return float
     */
    protected function getCurrencyRate($rec)
    {
        return currency_CurrencyRates::getRate($rec->valior, $rec->currencyId, NULL);
    }
}