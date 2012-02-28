<?php
/**
 * Инструментален клас за работа с манипулатори на нишки
 * 
 * @category   BGERP 2.0
 * @package    email
 * @author     Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright  2006-2012 Experta OOD
 * @license    GPL 2
 * @since      v 0.1
 *
 */
class email_util_ThreadHandle
{
    
    /**
     * Поставя манипулатор в началото на стринг
     * 
     * Манипулатора не се добавя ако вече присъства в събджекта.
     *
     * @param string $str
     * @param string $handle
     * @return string
     */
    static function decorate($str, $handle)
    {
        // Добавяме манипулатора само ако го няма
        if (!in_array($handle, static::extract($str))) {
            $str = "<{$handle}> {$str}";
        }
        
        return $str;
    }

    
    /**
     * Премахва манипулатор на нишка
     *
     * @param string $str
     * @param string $handle
     * @return string
     */
    static function strip($str, $handle)
    {
        $handle = preg_quote($handle, '/');
        $str = preg_replace("/\s*<{$handle}>(\s?)\s*/", '$1', $str);
        
        return $str;
    }

    
    /**
     * Прилага просто криприране, така че да затрудни налучкването на манипулатор на нишка
     *
     * @param string $prefix
     * @return string
     */
    static function protect($prefix)
    {
        $handle = $prefix . str::getRand('AAA');
        $handle = strtoupper($handle);
        
        return $handle;
    }
    
    
    /**
     * Извлича всички кандидат-манипулатори на нишка
     *
     * @param string $str обикновено това е събждект на писмо
     * @return array масив от стрингове, които е възможно (от синтактична гледна точка) да са
     * 				 манипулатори на нишка.
     */
    static function extract($str)
    {
        $handles = array();
        
        if (preg_match_all('/<([a-z\d]{4,})>/i', $subject, $matches)) {
            $handles = arr::make($matches[1], TRUE);
        }
        
        return $handles;
    }
    
    
    static function makeMessageId($mid)
    {
        $myDomain = BGERP_DEFAULT_EMAIL_DOMAIN;
        
        return "<{$mid}@{$myDomain}.mid>";
    }
    
    static function extractMid($messageId)
    {
        $myDomain = preg_quote(BGERP_DEFAULT_EMAIL_DOMAIN, '/');
        $regex    = "/^<(.+)@{$myDomain}\.mid>$/";
        
        if (preg_match($regex, $messageId, $matches)) {
            $mid = $matches[1];
        }
        
        return $mid;
    }
}