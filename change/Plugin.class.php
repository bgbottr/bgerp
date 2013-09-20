<?php


/**
 * Клас 'change_Plugin' - Плъгин за променя само на избрани полета
 *
 * @category  vendors
 * @package   change
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class change_Plugin extends core_Plugin
{
    
	
	/**
     * След дефиниране на полетата на модела
     * 
     * @param core_Mvc $mvc
     */
    public static function on_AfterDescription(core_Mvc $mvc)
    {
        // Ако няма добавено поле за версия
        if (!$mvc->fields['version']) {
            
            // Добавяме
            $mvc->FLD('version', 'varchar', 'caption=Версия,input=none,width=100%');
        }
        
        // Ако няма добавено поле за подверсия
        if (!$mvc->fields['subVersion']) {
            
            // Добавяме
            $mvc->FLD('subVersion', 'int', 'caption=Подверсия,input=none');
        }
    }
    
    
    /**
     * Добавя бутони за контиране или сторниране към единичния изглед на документа
     */
    function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        // Ако не е затворено или не е чернов
        if ($data->rec->state != 'closed' && $data->rec->state != 'draft') {

            // Права за промяна
            $canChange = $mvc->haveRightFor('changerec', $data->rec);
            
            // Ако има права за промяна
            if ($canChange) {
                $changeUrl = array(
                    $mvc,
                    'changeFields',
                    $data->rec->id,
                    'ret_url' => array($mvc, 'single', $data->rec->id),
                );
                
                // Добавяме бутона за промяна
                $data->toolbar->addBtn('Промяна', $changeUrl, 'id=conto,order=20', 'ef_icon = img/16/to_do_list.png');    
            }
        }
    }
    
    
	/**
     *  
     */
    public static function on_BeforeAction($mvc, &$tpl, $action)
    {
        // Ако екшъна не е changefields, да не се изпълнява
        if (strtolower($action) != 'changefields') return ;
        
        // Ако има права за едитване
        $mvc->requireRightFor('edit');
        
        // Ако има права за промяна
        $mvc->requireRightFor('changerec');
        
        // Вземаме формата към този модел
        $form = $mvc->getForm();
        
        // Вземаме всички позволени полета
        $allowedFieldsArr = static::getAllowedFields($form);
        
        // Очакваме да има зададени полета, които ще се променят
        expect(count($allowedFieldsArr));
        
        // Полетата, които ще записваме в лога
        $fieldsArrLogSave = $allowedFieldsArr;
        
        // Дабавяме версията
        $allowedFieldsArr['version'] = 'version';
        
        // Полетата, които ще се показва
        $fieldsArrShow = $allowedFieldsArr;
        
        // Добавяме подверсията
        $allowedFieldsArr['subVersion'] = 'subVersion';
        
        // Стринга, за инпутване
        $inputFields = implode(',', $allowedFieldsArr);
        
        // Добавяме и id
        $inputFields .= ',id';
        
        // Въвеждаме полетата
        $form->input($inputFields, 'silent');
        
        // Очакваме да има такъв запис
        expect($rec = $mvc->fetch($form->rec->id));
        
        // Очакваме потребителя да има права за съответния запис
        $mvc->requireRightFor('single', $rec);

        // Генерираме събитие в AfterInputEditForm, след въвеждането на формата
        $mvc->invoke('AfterInputEditForm', array($form));
        
        // URL' то където ще се редиректва
        $retUrl = getRetUrl();
        
        // Ако няма такова URL, връщаме към single' а
        $retUrl = ($retUrl) ? ($retUrl) : array($mvc, 'single', $form->rec->id);
        
        // Ако формата е изпратена без грешки
        if($form->isSubmitted()) {
            
            // Ако сме променили версията и подверсията
            if ($form->rec->version != $rec->version) {
                
                // Подверсията
                $subVersion = 0;
                
                // Ако има id
                if ($rec->id) {
                    
                    // Вземаме последните подверсии за съответнате версии
                    $lastSubVersionsArr = change_Log::getLastSubVersionsArr($mvc, $rec->id);
                }
                
                // Ако я има съответната версия
                if ($lastSubVersionsArr[$form->rec->version]) {
                    
                    // Вземаме подверсията
                    $subVersion = $lastSubVersionsArr[$form->rec->version];
                }
            } else {
                
                // Подверсията
                $subVersion = $rec->subVersion;
            }
            
            // Увеличаваме подверсията
            $subVersion++;
            
            // Добавяме подверсията
            $form->rec->subVersion = $subVersion;
            
            // Извикваме фунцкията, за да дадем възможност за добавяне от други хора
            $mvc->invoke('AfterInputChanges', array($rec, $form->rec));
            
            // Записваме промени
            $mvc->save($form->rec, $allowedFieldsArr);
            
            // Записваме лога на промените
            $savedRecsArr = change_Log::create($mvc->className, $fieldsArrLogSave, $rec, $form->rec);
            
            // Извикваме фунцкия, след като запишем
            $mvc->invoke('AfterSaveLogChange', array($savedRecsArr));
            
            // Редиректваме
            return redirect($retUrl);
        }
        
        // Ако няма грешки
        if (!$form->gotErrors()) {
            
            // Обхождаме стария запис
            foreach ((array)$rec as $key => $value) {
                
                // Ако е в полетата, които ще се променята
                if (!$allowedFieldsArr[$key]) continue;
                
                // Добавяме старта стойност
                $form->rec->{$key} = $value;
            }    
        }

        // Задаваме да се показват само полетата, които ни интересуват
        $form->showFields = $fieldsArrShow;
        
        // Добавяме бутоните на формата
        $form->toolbar->addSbBtn('Запис', 'save', 'ef_icon = img/16/disk.png');
        $form->toolbar->addBtn('Отказ', $retUrl, 'ef_icon = img/16/close16.png');
        
        // Титлата на документа
        $title = $mvc->getDocumentRow($form->rec->id)->title;
        
        // Титлата на формата
        $form->title = "Промяна на|*: <i>{$title}</i>";

        // Рендираме изгледа
        $tpl = $mvc->renderWrapping($form->renderHtml());
        
        return FALSE;
    }
    
    
    /**
     * 
     */
    public static function on_AfterPrepareSingle($mvc, $data)
    {
        // id на класа
        $classId = core_Classes::getId($mvc);
        
        // Масив с най - новата и най - старата версия
        $selVerArr = change_Log::getFirstAndLastVersion($classId, $data->rec->id);
        
        // Вземаме формата
        $form = $mvc->getForm();
        
        // Вземаме всички полета, които могат да се променят
        $allowedFieldsArr = (array)static::getAllowedFields($form);
        
        // Обхождаме полетата
        foreach ($allowedFieldsArr as $allowedField) {
            
            // Резултта
            $res = NULL;
            
            // Първата версия
            $first = NULL;
            
            // Последната версия
            $last = NULL;
            
            // Вземаме стойността за съответното поле, за първата версия
            $first = change_Log::getVerbalValue($classId, $data->rec->id, $selVerArr['first'], $allowedField);
            
            // Ако няма такъв запис, прескачаме
            if ($first === FALSE) continue;
            
            // Ако има последна версия
            if ($selVerArr['last']) {
                
                // Ако последната версия е последния вариант
                if ($selVerArr['last'] == change_Log::LAST_VERSION_STRING) {
                    
                    // Вземаме текста
                    $last = $data->row->$allowedField;
                } else {
                    
                    // Вземаме стойността за съответното поле, за последната версия
                    $last = change_Log::getVerbalValue($classId, $data->rec->id, $selVerArr['last'], $allowedField);
                }
                
                // Ако няма такъв запис, прескачаме
                if ($last === FALSE) continue;
                
                // Вземаме разликата има
                $res = lib_Diff::getDiff($first, $last);
                
            } else {
                
                // Резултата да е избраната
                $res = $first;
            }
            
            // Заместваме резултата в съответното поле
            $data->row->$allowedField = $res;
        }
        
        // Последна версия
        $data->row->LastVersion = change_Log::getVersionStr($data->row->version, $data->row->subVersion, FALSE);
        
        // Първата избрана версия
        $data->row->FirstSelectedVersion = $selVerArr['first'];
        
        // Ако последната версия е последния вариант
        if ($selVerArr['last'] == change_Log::LAST_VERSION_STRING) {
            
            // Последната избрана версия
            $data->row->LastSelectedVersion = $data->row->LastVersion;
        } else {
            
            // Последната избрана версия
            $data->row->LastSelectedVersion = $selVerArr['last'];
        }
    }
    
    
    /**
     * Връща масив с всички полета, които ще се променят
     * 
     * @param core_Form $form
     * 
     * return array $allowedFieldsArr
     */
    static function getAllowedFields($form)
    {
        // Масива, който ще връщаме
        $allowedFieldsArr = array();
        
        // Обхождаме всички полета
        foreach ($form->fields as $field => $filedClass) {
            
            // Ако могат да се променят
            if ($filedClass->changable) {
                
                // Добавяме в масива
                $allowedFieldsArr[$field] = $field;
            }
        }
        
        return $allowedFieldsArr;
        
    }
    
    
	/**
     * Извиква се след въвеждането на данните от Request във формата ($form->rec)
     * 
     * @param core_Mvc $mvc
     * @param core_Form $form
     */
    public static function on_AfterInputEditForm($mvc, &$form)
    {
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            // Ако редактраиме записа
            if ($id = $form->rec->id) {
                
                // Вземаме записа
                $rec = $mvc->fetch($id);
                
                // Ако състоянието не е чернова
                if ($rec->state != 'draft') {
                    
                    // Вземаме всички, полета които могат да се променят
                    $allowedFieldsArr = static::getAllowedFields($form);
                    
                    // Масив с полетата, които не са се променили
                    $noChangeArr = array();
                    
                    // Обхождаме полетта
                    foreach ((array)$allowedFieldsArr as $field) {
                        
                        // Ако има променя
                        if ($form->rec->$field != $rec->$field) {
                            
                            // Вдигаме флага
                            $haveChange = TRUE;
                        } else {
                            
                            // Добавяме в масива
                            $noChangeArr[] = $field;
                        }
                    }
                    
                    // Ако няма промени
                    if (!$haveChange) {
                        
                        // Сетваме грешка
                        $form->setError($noChangeArr, 'Нямате промена');
                    }
                }
            }
        }
        
        // Ако формата е изпратена успешно
        if ($form->isSubmitted()) {
            
            if (!$form->rec->id) {
                $form->rec->version = 0;
                $form->rec->subVersion = 1;
            }
        }
    }
}
