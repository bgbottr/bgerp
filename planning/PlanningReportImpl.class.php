<?php



/**
 * Имплементация на 'frame_ReportSourceIntf' за направата 
 * на справка за планиране на производството
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Gabriela Petrova <gab4eto@gmai.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_PlanningReportImpl extends frame_BaseDriver
{
    
	
    /**
     * Кой може да избира драйвъра
     */
    public $canSelectSource = 'planning, ceo';
    
    
    /**
     * Заглавие
     */
    public $title = 'Планиране » Планиране на производството';
    
    
    /**
     * Кои интерфейси имплементира
     */
    public $interfaces = 'frame_ReportSourceIntf,bgerp_DealIntf';
    
    
    /**
     * Брой записи на страница
     */
    public $listItemsPerPage = 50;
    
    
    /**
     * Работен кеш
     */
    protected $cache = array();

    
    /**
     * Добавя полетата на вътрешния обект
     *
     * @param core_Fieldset $fieldset
     */
	public function addEmbeddedFields(core_Form &$form)
    {
    	$form->FLD('from', 'date', 'caption=Начало');
    	$form->FLD('to', 'date', 'caption=Край');
    	
    	$this->invoke('AfterAddEmbeddedFields', array($form));
    }
    
    
    /**
     * Подготвя формата за въвеждане на данни за вътрешния обект
     *
     * @param core_Form $form
     */
	public function prepareEmbeddedForm(core_Form &$form)
    {
    	
    }
    
    
    /**
     * Проверява въведените данни
     *
     * @param core_Form $form
     */
	public function checkEmbeddedForm(core_Form &$form)
    {
        	 
    	// Размяна, ако периодите са объркани
    	if(isset($form->rec->from) && isset($form->rec->to) && ($form->rec->from > $form->rec->to)) {
    		$mid = $form->rec->from;
    		$form->rec->from = $form->rec->to;
    		$form->rec->to = $mid;
    	}
    	
    	
    }
    
    
    /**
     * Подготвя вътрешното състояние, на база въведените данни
     *
     * @param core_Form $innerForm
     */
    public function prepareInnerState()
    {
    	$data = new stdClass();
    	$data->recs = array();
    	
        $data->rec = $this->innerForm;
       
        $query = sales_Sales::getQuery();
        $queryJob = planning_Jobs::getQuery();
        
        $this->prepareListFields($data);
        
        $query->where("#state = 'active' ");
        $queryJob->where("#state = 'active' OR #state = 'stopped' OR #state = 'wakeup'");
       
        
        // за всеки един активен договор за продажба
        while($rec = $query->fetch()) {
        	//
        	//$origin = doc_Threads::getFirstDocument($rec->threadId);
        	// взимаме информация за сделките
        	//$dealInfo = $origin->getAggregateDealInfo();

        	if ($rec->deliveryTime) {
        		$date = $rec->deliveryTime;
        	} else {
        		$date = $rec->valior;
        	}
        	
        	$id = $rec->id;
        	
        	if (sales_SalesDetails::fetch("#saleId = $rec->id") !== FALSE) {
        		$products[] = sales_SalesDetails::fetch("#saleId = $rec->id");
        		$p = sales_SalesDetails::fetch("#saleId = $rec->id");
                $productId= $p->productId;
        		$dates[$productId][$id] = $date;
        		
        	} else {
        		continue;
        	}
        	

        }
        
        foreach ($dates as $prd => $sal) {
        	//$sal = dt::mysql2timestamp($sal);
        	if(count($sal) > 1) {
        		$dateSale[$prd] = min($sal);
        		$dateSale[$prd] = dt::mysql2timestamp($dateSale[$prd]);
        	} else {
        		foreach ($sal as $d){
        			$dateSale[$prd] = dt::mysql2timestamp($d);
        		}
        	}
        	
        }
        
        //bp($dateSale,$products);

        // за всеки един продукт
        if(is_array($products)){
	    	foreach($products as $product) {
	    		// правим индекс "класа на продукта|ид на продукта"
	        	$index = $product->productId;
	        		
	        	if($product->deliveryTime) {
	        		$date = $product->deliveryTime;
	        	} else {
	        		$date = $rec->valior;
	        	}
	        	
	        	if ($product->quantityDelivered >= $product->quantity) continue;
	        	
		        	
	        	// ако нямаме такъв запис,
	        	// го добавяме в масив
		        if(!array_key_exists($index, $data->recs)){
		        		
			    	$data->recs[$index] = 
			        		(object) array ('id' => $product->productId,
					        				'quantity'	=> $product->quantity,
					        				'quantityDelivered' => $product->quantityDelivered,
			        						'dateSale' => $dateSale[$product->productId],
					        				'sales' => array($product->saleId));
		        		
		        // в противен случай го ъпдейтваме
		        } else {
		        		
			    	$obj = &$data->recs[$index];
			        $obj->quantity += $product->quantity;
			        $obj->quantityDelivered += $product->quantityDelivered;
			        $obj->dateSale = $dateSale[$product->productId];
			        $obj->sales[] = $product->saleId;
		        		
		        }
	        }
        }

        while ($recJobs = $queryJob->fetch()) {
        	$indexJ = $recJobs->productId;
        	$dateJob[$recJobs->productId][$recJobs->id] = $recJobs->dueDate;
        	 
        	if ($recJobs->quantityProduced >= $recJobs->quantity) continue;
        	// ако нямаме такъв запис,
        	// го добавяме в масив
        	if(!array_key_exists($indexJ, $data->recs)){
        		$data->recs[$indexJ] =
        		(object) array ('id' => $recJobs->productId,
        				'quantityJob'	=> $recJobs->quantity,
        				'quantityProduced' => $recJobs->quantityProduced,
        				'date' => $recJobs->dueDate,
        				'jobs' => array($recJobs->id));

        		// в противен случай го ъпдейтваме
        	} else {

        		$obj = &$data->recs[$indexJ];
        		$obj->quantityJob += $recJobs->quantity;
        		$obj->quantityProduced += $recJobs->quantityProduced;
        		$obj->date =  $recJobs->dueDate;
        		$obj->jobs[] = $recJobs->id;

        	}
        }

    	
    	foreach ($dateJob as $prdJ => $job) {
        	if(count($job) > 1) {
        		$data->recs[$prdJ]->date = min($job);
        		$data->recs[$prdJ]->date = dt::mysql2timestamp($data->recs[$prdJ]->date);
        	} else {
        		foreach ($job as $dJ){
        			$data->recs[$prdJ]->date = dt::mysql2timestamp($dJ);
        		}
        	}
        }
        
        arr::order($data->recs, 'date');
        arr::order($data->recs, 'dateSale');
        
        
        for ($dt = 0; $dt <= count($data->recs); $dt++) {
        	if ($data->recs[$dt]->date) {
        		$data->recs[$dt]->date = dt::timestamp2Mysql($data->recs[$dt]->date);
        	}
        	
        	if ($data->recs[$dt]->dateSale) {
        		$data->recs[$dt]->dateSale = dt::timestamp2Mysql($data->recs[$dt]->dateSale);
        	}
        }

        return $data;
    }
    
    
    /**
     * След подготовката на показването на информацията
     */
    public static function on_AfterPrepareEmbeddedData($mvc, &$res)
    {
    	// Подготвяме страницирането
    	$data = $res;
        
        $pager = cls::get('core_Pager',  array('pageVar' => 'P_' .  $mvc->EmbedderRec->that,'itemsPerPage' => $mvc->listItemsPerPage));
        $pager->itemsCount = count($data->recs);
        $data->pager = $pager;
        
        if(count($data->recs)){
          
            foreach ($data->recs as $id => $rec){
				if(!$pager->isOnPage()) continue;
                
                $row = $mvc->getVerbal($rec);
                $data->rows[$id] = $row;
               
                if ($rec->sales) { 
                	foreach($rec->sales as $sale) {
                		$idS = 'sales=' . $sale;
                	}
                } elseif($rec->jobs){
                	foreach ($rec->jobs as $job) { 
                		$idS = 'job='. $job;
                	}
                	
                } 
                

                $data->rows[$id]->ordered = $row->quantity . "<br><span style='color:#0066FF'>{$row->quantityJob}</span>";
                $data->rows[$id]->delivered = $row->quantityDelivered . "<br><span style='color:#0066FF'>{$row->quantityProduced}</span>";
                $data->rows[$id]->dt = $row->dateSale . "<br><span style='color:#0066FF'>{$row->date}</span>";
                
                // Задаваме уникален номер на контейнера в който ще се реплейсва туултипа
                $data->rec->id ++;
                $unique = $data->rec->id;
                
                $tooltipUrl = toUrl(array('sales_Sales', 'ShowInfo', $idS, 'unique' => $unique), 'local');
               
                $arrow = ht::createElement("span", array('class' => 'anchor-arrow tooltip-arrow-link', 'data-url' => $tooltipUrl), "", TRUE);
                $arrow = "<span class='additionalInfo-holder'><span class='additionalInfo' id='info{$unique}'></span>{$arrow}</span>";
   
                if (isset($data->rows[$id]->quantityToDeliver) && isset($data->rows[$id]->quantityToProduced)) {
                	$data->rows[$id]->toDelivered = "{$arrow}&nbsp;" . $data->rows[$id]->quantityToDeliver . "<br>{$arrow}&nbsp;<span style='color:#0066FF'>{$data->rows[$id]->quantityToProduced}</span>";
                } elseif (isset($data->rows[$id]->quantityToDeliver)) {
                	$data->rows[$id]->toDelivered = "{$arrow}&nbsp;" . $data->rows[$id]->quantityToDeliver;
                } else {
                	$data->rows[$id]->toDelivered = "<br>{$arrow}&nbsp;<span style='color:#0066FF'>{$data->rows[$id]->quantityToProduced}</span>";
                }
            }
        }

        $res = $data;
    }
    

    /**
     * Връща шаблона на репорта
     * 
     * @return core_ET $tpl - шаблона
     */
    public function getReportLayout_()
    {
    	$tpl = getTplFromFile('planning/tpl/PlanningReportLayout.shtml');
    	
    	return $tpl;
    }
    
    
    /**
     * Рендира вградения обект
     *
     * @param stdClass $data
     */
    public function renderEmbeddedData($data)
    {
    	if(empty($data)) return;
    	 
    	$tpl = $this->getReportLayout();
    	$tpl->replace($this->title, 'TITLE');
    
    	$form = cls::get('core_Form');
    
    	$this->addEmbeddedFields($form);
    
    	$form->rec = $data->rec;
    	$form->class = 'simpleForm';
    
    	$tpl->prepend($form->renderStaticHtml(), 'FORM');
    
    	$tpl->placeObject($data->rec);

    	$f = cls::get('core_FieldSet');

    	$f->FLD('id', 'varchar');
    	$f->FLD('quantity', 'int');
    	$f->FLD('quantityDelivered', 'int');
    	$f->FLD('quantityToDeliver', 'int');
    	$f->FLD('dateSale', 'date');
    	$f->FLD('sales', 'richtext');
    	$f->FLD('quantityJob', 'int');
    	$f->FLD('quantityProduced', 'int');
    	$f->FLD('quantityToProduced', 'int');
    	$f->FLD('date', 'date');
    	$f->FLD('jobs', 'richtext');

    	$table = cls::get('core_TableView', array('mvc' => $f));

    	$tpl->append($table->get($data->rows, $data->listFields), 'CONTENT');
    	
    	if($data->pager){
    	     $tpl->append($data->pager->getHtml(), 'PAGER');
    	}
    
    	return  $tpl;
    }

    
    /**
     * Подготвя хедърите на заглавията на таблицата
     */
    protected function prepareListFields_(&$data)
    {
    
        $data->listFields = array(
                'id' => 'Име (код)',
        		'ordered' => 'Продажба / Производство->|*<small>Поръчано</small>',
        		'delivered' => "Продажба / Производство->|*<small>Доставено<br><span style='color:#0066FF'>Произведено</small></span>",
        		'toDelivered' => "Продажба / Производство->|*<small>За доставяне<br><span style='color:#0066FF'>За производство</small><span>",
        		'dt' => 'Продажба / Производство->|*<small>Дата</small>',
                //'quantity' => 'Продажба->|*<small>Поръчано</small>',
                //'quantityDelivered' => 'Продажба->|*<small>Доставено</small>',
                //'quantityToDeliver' => 'Продажба->|*<small>За доставяне</small>',
                //'dateSale' => 'Продажба->|*<small>Дата</small>',
                //'sales' => 'По продажба',
        		//'quantityJob' => 'Производство->|*<small>Поръчано</small>',
        		//'quantityProduced' => 'Производство->|*<small>Произведено</small>',
        		//'quantityToProduced' => 'Производство->|*<small>За производство</small>',
        		//'date' => 'Производство->|*<small>Дата</small>',
        		//'jobs' => 'По задание'
        		);
        
    }

       
    /**
     * Вербалното представяне на ред от таблицата
     */
    private function getVerbal($rec)
    {
    	$RichtextType = cls::get('type_Richtext');
        $Date = cls::get('type_Date');
		$Int = cls::get('type_Int');
		
		if ($rec->quantityDelivered && $rec->quantity) {
			$toDeliver = abs($rec->quantityDelivered - $rec->quantity);
		} elseif ($rec->quantityDelivered !== 0 || $rec->quantityDelivered == NULL) {
			$toDeliver = $rec->quantity;
		} else {
			$toDeliver = '';
		}
		
		if ($rec->quantityProduced && $rec->quantityJob) {
			$toProduced = abs($rec->quantityProduced - $rec->quantityJob);
		} elseif ($rec->quantityProduced !== 0 || $rec->quantityProduced == NULL) {
			$toProduced = $rec->quantityJob;
		} else {
			$toProduced = '';
		}

        $row = new stdClass();
        
        $row->id = cat_Products::getShortHyperlink($rec->id);
    	$row->quantity = $Int->toVerbal($rec->quantity);
    	$row->quantityDelivered = $Int->toVerbal($rec->quantityDelivered);
    	$row->quantityToDeliver = $Int->toVerbal($toDeliver);
    	
    	$row->dateSale = $Date->toVerbal($rec->dateSale);
    		
    	for($i = 0; $i <= count($rec->sales)-1; $i++) {
    		
    		$row->sales .= "#".sales_Sales::getHandle($rec->sales[$i]) .",";
    	}
    	$row->sales = $RichtextType->toVerbal(substr($row->sales, 0, -1));
    		
    	$row->quantityJob = $Int->toVerbal($rec->quantityJob);
    	$row->quantityProduced = $Int->toVerbal($rec->quantityProduced);
    	$row->quantityToProduced = $Int->toVerbal($toProduced);
    	$row->date = $Date->toVerbal($rec->date);
    		
    	for($j = 0; $j <= count($rec->jobs)-1; $j++) { 

    		$row->jobs .= "#".planning_Jobs::getHandle($rec->jobs[$j]) .","; 
    	}
		$row->jobs = $RichtextType->toVerbal(substr($row->jobs, 0, -1));
		
		
        return $row;
    }
      
      
	/**
     * Скрива полетата, които потребител с ниски права не може да вижда
     *
     * @param stdClass $data
     */
	public function hidePriceFields()
    {
    	$innerState = &$this->innerState;
      		
      	unset($innerState->recs);
    }
      
      
	/**
     * Коя е най-ранната дата на която може да се активира документа
     */
	public function getEarlyActivation()
    {
    	$activateOn = "{$this->innerForm->to} 23:59:59";
      	  	
      	return $activateOn;
	}

	public function showInfo()
	{
		$tooltipUrl = toUrl(array('acc_Items', 'showItemInfo', $id, 'unique' => $unique), 'local');
		
		return $tooltipUrl;
	}

     /**
      * Ако имаме в url-то export създаваме csv файл с данните
      *
      * @param core_Mvc $mvc
      * @param stdClass $rec
      */
     /*public function exportCsv()
     {

         $exportFields = $this->getExportFields();

         $conf = core_Packs::getConfig('core');

         if (count($this->innerState->recs) > $conf->EF_MAX_EXPORT_CNT) {
             redirect(array($this), FALSE, "Броят на заявените записи за експорт надвишава максимално разрешения|* - " . $conf->EF_MAX_EXPORT_CNT, 'error');
         }

         $csv = "";

         foreach ($exportFields as $caption) {
             $header .= "," . $caption;
         }

         
         if(count($this->innerState->recs)) {
			foreach ($this->innerState->recs as $id => $rec) {

				if($this->innerState->bShowQuantities || $this->innerState->rec->groupBy){
					
					
					$baseQuantity += $rec->baseQuantity;
					$baseAmount += $rec->baseAmount;
					$debitQuantity += $rec->debitQuantity;
					$debitAmount += $rec->debitAmount;
					$creditQuantity += $rec->creditQuantity;
					$creditAmount += $rec->creditAmount;
					$blQuantity += $rec->blQuantity;
					$blAmount += $rec->blAmount;

				} 
				
				$rCsv = $this->generateCsvRows($rec);

				
				$csv .= $rCsv;
				$csv .=  "\n";
		
			}

			$row = new stdClass();
			
			$row->flag = TRUE;
			$row->baseQuantity = $baseQuantity;
			$row->baseAmount = $baseAmount;
			$row->debitQuantity = $debitQuantity;
			$row->debitAmount = $debitAmount;
			$row->creditQuantity = $creditQuantity;
			$row->creditAmount = $creditAmount;
			$row->blQuantity = $blQuantity;
			$row->blAmount = $blAmount;
			
			foreach ($row as $fld => $value) {
				$value = frame_CsvLib::toCsvFormatDouble($value);
				$row->{$fld} = $value;
			}
		
		
			$beforeRow = $this->generateCsvRows($row);

			$csv = $header . "\n" . $beforeRow. "\n" . $csv;
	    } 

        return $csv;
    }*/


    /**
     * Ще се експортирват полетата, които се
     * показват в табличния изглед
     *
     * @return array
     */
    /*protected function getExportFields_()
    {

        $exportFields = $this->innerState->listFields;
        
        foreach ($exportFields as $field => $caption) {
        	$caption = str_replace('|*', '', $caption);
        	$caption = str_replace('->', ' - ', $caption);
        	
        	$exportFields[$field] = $caption;
        }
        
        return $exportFields;
    }*/
    
    
    /**
	 * Ще направим row-овете в CSV формат
	 *
	 * @return string $rCsv
	 */
	/*protected function generateCsvRows_($rec)
	{
	
		$exportFields = $this->getExportFields();

		$rec = frame_CsvLib::prepareCsvRows($rec);
	
		$rCsv = '';
		
		$res = count($exportFields); 
		
		foreach ($rec as $field => $value) {
			$rCsv = '';
			
			if ($res == 11) {
				$zeroRow = "," . 'ОБЩО' . "," .'' . "," .'';
			} elseif ($res == 10 || $res == 9 || $res == 8 || $res == 7) {
				$zeroRow = "," . 'ОБЩО' . "," .'';
			} elseif ($res <= 6) {
				$zeroRow = "," . 'ОБЩО';
			}
			
			foreach ($exportFields as $field => $caption) {
					
				if ($rec->{$field}) {
	
					$value = $rec->{$field};
					$value = html2text_Converter::toRichText($value);
					// escape
					if (preg_match('/\\r|\\n|,|"/', $value)) {
						$value = '"' . str_replace('"', '""', $value) . '"';
					}
					$rCsv .= "," . $value;
					
					if($rec->flag == TRUE) {
						
						$zeroRow .= "," . $value;
						$rCsv = $zeroRow;
					}
	
				} else {
					
					$rCsv .= "," . '';
				}
			}
		}
		
		return $rCsv;
	}*/

}