<?php



/**
 * Клас 'csv_Lib' - Пакет за работа с CSV файлове
 *
 *
 * @category  vendors
 * @package   csv
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class csv_Lib
{
    /**
     * Импортира CSV файл в указания модел
     */
    static function import($mvc, $file, $fields = array(), $defaults = array(), $format = array())
    {   
        // Дефолт стойностите за форматирането по подразбиране
        setIfNot($format['length'], 0);
        setIfNot($format['delimiter'], ',');
        setIfNot($format['enclosure'], '"');
        setIfNot($format['escape'], '\\');
        setIfNot($format['skip'], '#');
        
        $firstRow = TRUE; 
        $res    = (object) array('created' => 0, 'updated' => 0, 'skipped' =>0);
        $fields = arr::make($fields);

        $fromZero = !$mvc->fetch("1=1");
        
        $path = getFullPath($file);

        expect(($handle = fopen($path, "r")) !== FALSE);

        while (($data = fgetcsv($handle, $format['length'], $format['delimiter'], $format['enclosure'], $format['escape'])) !== FALSE) {

            // Пропускаме празните линии
            if(!count($data) || (count($data) == 1 && trim($data[0]) == '')) continue;

            // Пропускаме редовете със знака указан в $skip
            if($data[0]{0} == $format['skip']) continue;

            // Ако не са указани полетата, вземаме ги от първия ред
            if($firstRow && !count($fields)) {
                foreach($data as $f) {
                    $fields[] = $f;
                }
                
                $firstRow = FALSE;
            } else {
                // Вкарваме данните
                if($defaults) {
                    $rec = (object)$defaults;
                } else {
                    $rec = new stdClass();
                }
                
                foreach($fields as $i => $f) {
                    $rec->{$f} = $data[$i];
                }
                
                // Обработка на записа преди импортиране
				$mvc->invoke('BeforeImportRec', array(&$rec));
				
                // Ако таблицата се попълва от нулата, само се добавят редове
                if($fromZero) {
                    $mvc->save($rec);
                    $res->created++;
                    continue;
                }
                
                $conflictFields = array();

                if(!$mvc->isUnique($rec, $conflictFields, $exRec)) {
                    $rec->id = $exRec->id;
                    $flagUpdate = TRUE;
                } else {
                    $res->created++;
                    $flagUpdate = FALSE;
                }
                
                // По подразбиране записът е добавен от системния потребител
                setIfNot($rec->createdBy, -1);
				
                // Ако нямаме запис с посочените уникални стойности, вкарваме новия
                $mvc->save($rec);
                
                if($flagUpdate) {
                    $res->skipped++;
                    $rec = $mvc->fetch($rec->id);
                    foreach($fields as $i => $f) {
                        if($rec->{$f} != $exRec->{$f}) {
                            $res->updated++;
                            $res->skipped--;
                            break;
                        }
                    }
                }
            }
        }
            
        fclose($handle);

        $res->html = self::cntToVerbal($res, $mvc->className);
        
        return $res;
    }


    /**
     * Функция, която импортира еднократно даден csv файл в даден модел
     */
    static function importOnce($mvc, $file, $fields = array(), $defaults = array(), $format = array(), $delete = FALSE)
    {
        // Пътя до файла с данните
        $filePath = getFullPath($file);
        
        // Името на променливата, в която се записва хеша на CSV файла
        $param = 'csvFile' . preg_replace('/[^a-z0-9]+/', '_', $file);
        
        // Хеша на CSV данните
        $hash = md5_file($filePath);

        list($pack,) = explode('_', $mvc->className);
        
        // Конфигурация на пакета 'lab'
        $conf = core_Packs::getConfig($pack);

        $cntObj = new stdClass();
        
        try {
            $confHash = $conf->{$param};
        } catch (core_exception_Expect $e) {
            $confHash = NULL;
        }
        
        if($confHash != $hash) {
            
            // Изтриваме предишното съдържание на модела, ако е сетнат $delete
            if($delete) {
                $mvc->db->query("TRUNCATE TABLE `{$mvc->dbTableName}`");
            }
            
            $cntObj = self::import($mvc, $file, $fields, $defaults, $format);
            
            // Записваме в конфигурацията хеша на последния приложен csv файл
            core_Packs::setConfig($pack, array($param => $hash));
        } else {
            $cntObj = (object) array('created' => 0, 'updated' => 0, 'skipped' =>0, 'html' => "\n<li>Пропуснато импортиране в {$mvc->className}, защото няма промяна в CSV файла</li>");
        }

        return $cntObj;
    }


    /**
     * Импортира съдържанието на посочения CSV файл, когато той е променян
     * Преди импортирането изпразва таблицата, 
     */
    static function importOnceFromZero($mvc, $file, $fields = array(), $defaults = array(), $format = array())
    {
        return self::importOnce($mvc, $file, $fields, $defaults, $format, TRUE);
    }


    /**
     * Връща html вербално представяне на резултата от ::import(...)
     */
    static function cntToVerbal($cntObj, $place = NULL)
    {
        $res = '';
        
        if($place) {
            $place = " в {$place}";
        }

        if($cntObj->created) {
            $res .= "\n<li style='color:green;'>Създадени са {$cntObj->created} записа{$place}</li>";
        }
            
        if($cntObj->updated) {
            $res .= "\n<li style='color:#600;'>Обновени са {$cntObj->updated} записа{$place}</li>";
        }
            
        if($cntObj->skipped) {
            $res .= "\n<li>Пропуснати са {$cntObj->skipped} записа{$place}</li>";
        }

        return $res;
    }



    /**
     * Създава csv
     */
    static function createCsv($recs, core_FieldSet $fieldSet, $listFields = NULL, $mode = array())
    {
        $mode = arr::make($mode, TRUE);
        
    	// ще вземем конфигурационните константи
    	$conf = core_Packs::getConfig('csv');
    	
        if(isset($listFields)) {
            $listFields = arr::make($listFields, TRUE);
        } else {
            $fieldsArr = $fieldSet->selectFields("");
            $listFields = array();
            foreach ($fieldsArr as $name => $fld) {
                $listFields[$fld->name] = $fld->caption;
            }
        }
    	
    	$exportCnt = core_Setup::get('EF_MAX_EXPORT_CNT', TRUE);
    	if(count($recs) > $exportCnt) {
    		redirect(array($this, 'list'), FALSE, "|Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
    	}
    	
    	if (is_array($listFields)) {
    	    $firstRow = '';
        	foreach ($listFields as $fld => $caption) {
        	   
        	    if (!$listFields[$fld]) {
        	        $listFields[$fld] = $fld;
        	        $caption = $fld;
        	    }
        	    
        	    if (preg_match('/\\r|\\n|,|"/', $caption)) {
        	        $caption = '"' . str_replace('"', '""', $caption) . '"';
        	    }
        	    
        	    $firstRow .= ($firstRow ? $conf->CSV_DELIMITER : '') . $caption;
        	   
        	}
    	}
    	
    	if(!isset($listFields)) {
    	   //$doc = doc_Threads::getFirstDocument(getCurrentUrl()['threadId']);
    	   //$d = doc_Containers::getDocumentByHandle(getCurrentUrl()['docId']);
    	   bp();
    	}

        foreach($recs as $rec) {
            
            // Всеки нов ред в началото е празен
            $rCsv = '';

            foreach ($fieldSet->fields as $name => $field) { 

                // Пропускаме не-посочените в $listFields полета
                if(is_array($listFields) && !isset($listFields[$name])) continue;

                // Вземаме типа
				$type = $field->type;
	                 
	            if ($type instanceof type_Key) {
	                	
	    			Mode::push('text', 'plain');
	    			$value = $fieldSet->getVerbal($rec, $name);
	    			Mode::pop('text');
	    				
	    		} elseif($type instanceof type_Double) {
	    				
	    			$type->params['decPoint'] = $conf->CSV_DELIMITER_DECIMAL_SING;
	    			$type->params['thousandsSep'] = '';
     
                    Mode::push('text', 'plain');
                    //$value = $this->mvc->getVerbal($rec, $name);
                    $value = $type->toVerbal($rec->{$name});

                    Mode::pop('text');
	    				
	    		} elseif($type instanceof type_Date) {
	    				
	    			if ($conf->CSV_FORMAT_DATE == 'dot') {
	    				$value = dt::mysql2verbal($rec->{$name}, 'd.m.Y');
	    			} else {
	    				$value = dt::mysql2verbal($rec->{$name}, 'm/d/y');
	    			}
	    				
	    		} elseif($type instanceof type_Richtext && $mode['text'] == 'plain') {
	    				
                    Mode::push('text', 'plain');
	    			
                    $value = $this->mvc->getVerbal($rec, $name);
	    			
                    Mode::pop('text');
	    
	    		} else {
	    			$value = $rec->{$name};
	    		}
	            
                // Ако не генерираме html премахваме таговете
                if($mode['text'] == 'xhtml' && !($type instanceof type_Richtext)) {
	    		    $value = strip_tags($value);
                }
	    			
	            // Ескейпваме - твърдо с "
	            if (preg_match('/\\r|\\n|,|"/', $value)) {
	            	$value = '"' . str_replace('"', '""', $value) . '"';
	            }
	                
	            if (strpos($value, "&nbsp;")){
	            	$value = str_replace('&nbsp;', '', $value);
	            }
	             
	            $rCsv .= ($rCsv ? $conf->CSV_DELIMITER : '') . $value;
	            
        	}
            
            /* END за всяка колона */
            $csv .= $rCsv . "\n";
        }
        
        if (isset ($firstRow) && $mode['columns'] != 'none') {
            $csv = $firstRow . "\n" . $csv;
        }
       
        return $csv;
    }

    
    /**
     * Връща масив с данните от csv-то
     * @param string $csvData - csv данни
     * @param char $delimiter - разделител
     * @param char $enclosure - ограждане
     * @param string $firstRow - първи ред данни или имена на колони
     * @return array $rows - масив с парсирани редовете на csv-то
     */
    public static function getCsvRows($csvData, $delimiter = FALSE, $enclosure = FALSE, $firstRow)
    {
    	// ще вземем конфигурационните константи
    	$conf = core_Packs::getConfig('csv');
    	
    	if (!isset($delimiter)) {
    		$delimiter = $conf->CSV_DELIMITER;
    	}
    	
    	if (!isset($enclosure)) {
    		$enclosure = $conf->CSV_ENCLOSURE;
    	}
    	
    	$textArr = explode(PHP_EOL, trim($csvData));
    
    	foreach($textArr as $line){
    		$arr = str_getcsv($line, $delimiter, $enclosure);
    		$arr = iconv('utf-8', $conf->CSV_ENCODING, $arr);
    		
    		array_unshift($arr, "");
    		unset($arr[0]);
    		$rows[] = $arr;
    	}
    
    	if($firstRow == 'columnNames'){
    		unset($rows[0]);
    	}
    
    	return $rows;
    }
}