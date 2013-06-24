<?php
class ItemRelations_IndexController extends Omeka_Controller_AbstractActionController
{
    public function indexAction()
    {        
        
        $this->_helper->redirector('browse', 'vocabularies');
        
        return;
    }
}
