<?php
class ItemRelations_IndexController extends Omeka_Controller_AbstractActionController
{
    public function indexAction()
    {        
        
        $this->_helper->redirector('browse', 'vocabularies');
        
        return;
    }
    

    public function editAction()
    {
		print "editAction()";
        // Get the requested page.
        $page = $this->_helper->db->findById();
        $this->view->form = $this->_getForm($page);
        $this->_processPageForm($page, 'edit');
        
    }
}
