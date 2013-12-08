<?php



/**
 * Клас  'cat_type_Weight' 
 * Тип за Тегло, приема стойности от рода "5 кг" и ги конвертира до основната еденица
 *
 *
 * @category  bgerp
 * @package   cat
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class cat_type_Weight extends cat_type_Uom {
    
	
	/**
	 * Параметър по подразбиране
	 */
	function init($params = array())
    {
    	$this->params['unit'] = 'kg';
    }
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($val)
    {
    	$val = parent::fromVerbal_($val);
    	
    	if($val === FALSE){
    		$this->error = "Моля въведете валидна мярка за тегло";
            
            return FALSE;
    	}
    	
    	return $val;
    }
}