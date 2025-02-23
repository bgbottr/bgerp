<?php


/**
 * Клас 'transsrv_plg_CreateAuction'
 * Плъгин даващ възможност на складови документи да генерират Търгове в трансбид
 *
 *
 * @category  bgerp
 * @package   transsrv
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class transsrv_plg_CreateAuction extends core_Plugin
{
    /**
     * След подготовка на тулбара на единичен изглед
     */
    public static function on_AfterPrepareSingleToolbar($mvc, &$data)
    {
        $rec = $data->rec;
        
        $d = $mvc->getLogisticData($rec);
       
        if ($systemId = remote_Authorizations::getSystemId(transsrv_Setup::get('BID_DOMAIN'))) {
            if ($mvc->haveRightFor('createauction', $rec)) {
                $d = $mvc->getLogisticData($rec);
                $d['maxWeight'] = $d['totalWeight'];
                $d['maxVolume'] = $d['totalVolume'];
                $d = base64_encode(gzcompress(json_encode($d)));
                
                //$url = array('transbid_Auctions', 'Add', 'd' => $d);
                $url = remote_Authorizations::getRemoteUrl($systemId, array('transbid_Auctions', 'Add', 'd' => $d));
                $data->toolbar->addBtn('Търг', $url, 'ef_icon = img/16/view.png,title=Създаване на търг в trans.bid,row=2');
            }
        }
    }
    
    
    /**
     * Изпълнява се след подготовката на ролите, които могат да изпълняват това действие
     */
    public static function on_AfterGetRequiredRoles($mvc, &$requiredRoles, $action, $rec = null, $userId = null)
    {
        if ($action == 'createauction' && isset($rec)) {
            if (!$mvc->haveRightFor('single', $rec) || (!in_array($rec->state, array('active', 'pending', 'draft')))) {
                $requiredRoles = 'no_one';
            } else {
                $requiredRoles = 'officer';
            }
        }
    }
}
