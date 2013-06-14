<?php
class hr_DataWrapper extends hr_Wrapper
{
function on_AfterRenderWrapping($mvc, &$tpl)
    {
        $tabs = cls::get('core_Tabs', array('htmlClass' => 'alphabet'));

        $tabs->TAB('hr_ContractTypes', 'Шаблони');
        $tabs->TAB('hr_NKPD', 'НКПД');
        $tabs->TAB('hr_NKID', 'НКИД');

        $tpl = $tabs->renderHtml($tpl, $mvc->className);
        $mvc->currentTab = 'Данни >> Шаблони';
    }
}