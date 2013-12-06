<?php



/**
 * Клас  'cat_type_Size' 
 * Тип за Размер, приема стойности от рода "5 м" и ги конвертира до основната еденица
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
class cat_type_Size extends cat_type_Uom {
    
    
    /**
     * Конвертира от вербална стойност
     */
    function fromVerbal_($val)
    {
    	$this->params['unit'] = 'm';
    	
    	$val = parent::fromVerbal_($val);
    	
    	if($val === FALSE){
    		$this->error = "Моля въведете валидна мярка за размер";
            
            return FALSE;
    	}
    	
    	return $val;
    }
    
    
	/**
     * Рендира HTML инпут поле
     */
    function renderInput_($name, $value = '', &$attr = array())
    {
    	if($value && empty($this->error)){
    		$value = cat_UoM::smartConvert($value, 'm', FALSE);
    	}
        
        return ht::createTextInput($name, $value, $attr);
    }
    
    
    /**
     * Форматира числото в удобна за четене форма
     */
    function toVerbal_($val)
    {
    	$this->params['unit'] = 'm';
    	
    	return parent::toVerbal_($val);
    }
}