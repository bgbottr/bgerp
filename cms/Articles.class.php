<?php



/**
 * Публични статии
 *
 *
 * @category  bgerp
 * @package   cms
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class cms_Articles extends core_Master
{
    
    
    /**
     * Заглавие
     */
    var $title = "Публични статии";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_State2, plg_RowTools, plg_Printing, cms_Wrapper, plg_Sorting, plg_Vid';


    /**
     * Полета, които ще се показват в листов изглед
     */
   // var $listFields = ' ';
    
     
    
    /**
     * Кой може да пише?
     */
    var $canWrite = 'cms,admin';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'cms,admin';
    
 
    
    /**
     * Интерфейси, поддържани от този мениджър
     */
    var $interfaces = array(
        // Интерфейс на всички счетоводни пера, които представляват контрагенти
        'cms_ContentSourceIntf',
    );

    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('level', 'order', 'caption=Номер,mandatory');
        $this->FLD('menuId', 'key(mvc=cms_Content,select=menu)', 'caption=Меню,mandatory,silent');
        $this->FLD('title', 'varchar', 'caption=Заглавие,mandatory,width=100%');
        $this->FLD('body', 'richtext', 'caption=Текст,column=none');
    }

    
    /**
     *  Задава подредбата
     */
    function on_BeforePrepareListRecs($mvc, $res, $data)
    {
        $data->query->orderBy('#menuId,#level');
    }


    
    /**
     * Подготвя някои полета на формата
     */
    function on_AfterPrepareEditForm($mvc, $data)
    {
        $cQuery = cms_Content::getQuery();
        
        $selfClassId = core_Classes::fetchIdByName($mvc->className);
        $cQuery->where("#source = {$selfClassId}");
        while($cRec = $cQuery->fetch()) {
            $options[$cRec->id] = $cRec->menu;
        } 

        $data->form->setOptions('menuId', $options);
    }



    function getContentUrl($menuId)
    {
        $query = self::getQuery();
        $query->where("#menuId = {$menuId}");
        $query->orderBy("#level");

        $rec = $query->fetch("#menuId = {$menuId} AND #body != ''");

        if($rec) {
            return toUrl(array($this, 'Article', $rec->id));
        } else {
            return toUrl(array($this, 'Article', 'menuId' => $menuId));
        }
    }


    function act_Article()
    {   
        Mode::set('wrapper', 'cms_tpl_Page');

        $id = Request::get('id', 'int');
        
        if(!$id) { 
            $menuId = Request::get('menuId', 'int');
            expect($menuId);
            $query = self::getQuery();
            $query->where("#menuId = {$menuId}");
            $query->orderBy("#createdOn=DESC");
            $rec = $query->fetch();
        } else {
            // Ако има, намира записа на страницата
            $rec = $this->fetch($id);
        }
        
        if($rec) {

            $menuId = $rec->menuId;

            $lArr = explode('.', $this->getVerbal($rec, 'level'));

            $content = new ET('[#1#]', $this->getVerbal($rec, 'body'));

            $title   = $this->getVerbal($rec, 'title') . " » ";


            $content->append($title, 'PAGE_TITLE');
        }
        
        Mode::set('cms_MenuId', $menuId);

        if(!$content) $content = new ET();

        // Подготвя навигацията
        $query = self::getQuery();
        $query->where("#menuId = {$menuId}");
        $query->orderBy("#level");

        $navTpl = new ET();

        while($rec1 = $query->fetch()) {
            
            $lArr1 = explode('.', $this->getVerbal($rec1, 'level'));

            if($lArr1[2] && (($lArr[0] != $lArr1[0]) || ($lArr[1] != $lArr1[1]))) continue;

            $title = $this->getVerbal($rec1, 'title');

            $class = ($rec->id == $rec1->id) ? $class = 'sel_page' : '';
 
            if($lArr1[2]) {
                $class .= ' level3';
            } elseif($lArr1[1]) {
                $class .= ' level2';
            } elseif($lArr1[0]) {
                $class .= ' level1';
            }

            $navTpl->append("<div class='nav_item {$class}'>");
            if(trim($rec1->body)) {
                $navTpl->append(ht::createLink($title, array($this, 'Article', $rec1->vid ? $rec1->vid : $rec1->id)));
            } else {
               $navTpl->append($title);
            }
            
            if($this->haveRightFor('edit', $rec1)) {
                $navTpl->append('&nbsp;');
                $navTpl->append(ht::createLink( '<img src=' . sbf("img/16/edit.png") . ' width="12" height="12">', 
                    array($this, 'Edit', $rec1->id, 'ret_url' => array($this, 'Article', $rec1->id)))); 
            }

            $navTpl->append("</div>");

        }
        
        if($this->haveRightFor('add')) {
            $navTpl->append( "<div style='padding:2px; border:solid 1px #ccc; background-color:#eee; margin-top:10px;font-size:0.7em'>");
            $navTpl->append(ht::createLink( tr('+ добави страница'), array($this, 'Add', 'menuId' => $menuId, 'ret_url' => array($this, 'Article', 'menuId' => $menuId))));
            $navTpl->append( "</div>");
        }


        $content->append($navTpl, 'NAVIGATION');

        $content->replace($title, 'META_KEYWORDS');

        return $content;

    }
    
    
 }