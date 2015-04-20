<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за справка на движенията по каса
 *
 *
 * @category  bgerp
 * @package   acc
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class bank_AccountReportImpl extends acc_PeriodHistoryReportImpl
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'ceo, acc';
    
    
    /**
     * Заглавие
     */
    public $title = 'Финанси»Дневни обороти - б. сметка';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf';
    
    
    /**
     * Дефолт сметка
     */
    protected $defaultAccount = '503';
    
    
    /**
     * След подготовката на ембеднатата форма
     */
    public static function on_AfterPrepareEmbeddedForm($mvc, core_Form &$form)
    {
    	$bItemPosition = acc_Lists::getPosition($mvc->defaultAccount, 'bank_OwnAccRegIntf');
    	$currencyPosition = acc_Lists::getPosition($mvc->defaultAccount, 'currency_CurrenciesAccRegIntf');
    	 
    	$form->setField("ent{$bItemPosition}Id", 'caption=Б. сметка');
    	$form->setField("ent{$currencyPosition}Id", 'caption=Валута');
    	
    	// Слагаме избраната каса, ако има такава
    	if($bankAccount = bank_OwnAccounts::getCurrent('id', FALSE)){
    		$bankItemId = acc_Items::fetchItem('bank_OwnAccounts', $bankAccount)->id;
    		$form->setDefault("ent{$bItemPosition}Id", $bankItemId);
    	}
    }
    
    
    /**
     * Какви са полетата на таблицата
     */
    public static function on_AfterPrepareListFields($mvc, &$res, $data)
    {
    	$data->listFields['baseQuantity'] = 'Начално';
    	$data->listFields['blQuantity'] = 'Остатък';
    	$data->listFields['debitQuantity'] = 'Приход';
    	$data->listFields['creditQuantity'] = 'Разход';
    	
    	unset($data->listFields['baseAmount'],$data->listFields['debitAmount'],$data->listFields['creditAmount'],$data->listFields['blAmount']);
    }
}