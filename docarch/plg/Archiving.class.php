<?php


/**
 * Клас 'docarch_plg_Archiving' -Плъгин за архивиране на документи
 *
 *
 * @category  bgerp
 * @package   docarch
 *
 * @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class docarch_plg_Archiving extends core_Plugin
{
    /**
     * Добавя бутон за архивиране към единичния изглед на документа
     */
    public static function on_AfterPrepareSingleToolbar($mvc, $data)
    {
        if (!(core_Packs::isInstalled('docarch'))) {
            
            return;
        }
        $rec = &$data->rec;
        $arcivesArr = array();
        
        
        // има ли архиви дефинирани за документи от този клас , или за всякакви документи
        $docClassId = $mvc->getClassId();
        
        $documentContainerId = ($rec->containerId);
        
        $archQuery = docarch_Archives::getQuery();
        
        $archQuery->show('documents');
        
        $archQuery->likeKeylist('documents', $docClassId);
        
        $archQuery->orWhere('#documents IS NULL');
        
        if ($archQuery->count() > 0) {
            
            while ($arcives = $archQuery->fetch()) {
                $arcivesArr[] = $arcives->id;
            }
            
            // Има ли в тези архиви томове дефинирани да архивират документи, с отговорник текущия потребител
            $volQuery = docarch_Volumes::getQuery();
            
            $volQuery->in('archive', $arcivesArr);
            
            $currentUser = core_Users::getCurrent();
            
            $volQuery->where("#isForDocuments = 'yes' AND #inCharge = ${currentUser} AND #state = 'active'");
            
            //Архивиран ли е към настоящия момент този документ.
            $balanceDocMove = docarch_Movements::getBalanceOfDocumentMovies($documentContainerId);
            
            if(is_array($balanceDocMove)){
                
                foreach ($balanceDocMove as $val){
                    
                    $balanceMarker =($val->isInVolume != 0) ? false : true;
                    if ($balanceMarker === true) break; 
                }
                
            }
            
            $balanceMarker = boolval($balanceMarker || (is_null($balanceDocMove))); 
            
            //Ако документа в момента не е архивиран И има том който да отговатя на условията за него, показва бутон за архивиране
            if (($volQuery->count() > 0) && $balanceMarker){
                $data->toolbar->addBtn('Архивиране', array('docarch_Movements', 'Add', 'documentId' => $documentContainerId, 'ret_url' => true), 'ef_icon=img/16/archive.png,row=2');
            }
        }
    }
    
    
    /**
     * Показва допълнителни действие в doclog история
     *
     * @param core_Master $mvc
     * @param string|null $html
     * @param int         $containerId
     * @param int         $threadId
     */
    public static function on_RenderOtherSummary($mvc, &$html, $containerId, $threadId)
    {
        if (!(core_Packs::isInstalled('docarch'))) {
            
            return;
        }
        $html .= docarch_Movements::getSummary($containerId);
    }
}
