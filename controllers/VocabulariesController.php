<?php
class ItemRelations_VocabulariesController extends Omeka_Controller_AbstractActionController
{
    public function indexAction()
    {   
        $this->_helper->redirector('browse');
        return;
    }
    
    public function browseAction()
    {
        $vocabularies = $this->_helper->db->getTable('ItemRelationsVocabulary')->findAllCustomFirst();
        
        $this->view->vocabularies = $vocabularies;
    }
    
    public function showAction()
    {
        $vocabularyId = $this->_getParam('id');
        
         $vocabulary = $this->_helper->db->getTable('ItemRelationsVocabulary')->find($vocabularyId);
        
         $properties = $this->_helper->db->getTable('ItemRelationsProperty')->findByVocabularyId($vocabularyId);
        
        
        $this->view->vocabulary = $vocabulary;
        $this->view->properties = $properties;
    }
    
    public function editAction()
    {
        $vocabularyId = $this->_getParam('id');
        
        // Only custom vocabularies can be edited.
        $vocabulary =  $this->_helper->db->getTable('ItemRelationsVocabulary')->find($vocabularyId);
        if (!$vocabulary->custom) {
            $this->redirect->gotoSimple('browse');
        }
        
        // Handle edit vocabulary form.
        if ($this->_getParam('submit_edit_vocabulary')) {
            
            $this->_handleEditVocabularyForm($vocabularyId);
            
            // Redirect to browse.
            $this->_helper->flashMessenger('The vocabulary was successfully edited.');
            $this->_helper->redirector('browse');
        }
        
        $properties = $this->_helper->db->getTable('ItemRelationsProperty')->findByVocabularyId($vocabularyId);
        $this->view->properties = $properties;
    }
    
    protected function _handleEditVocabularyForm($vocabularyId)
    {
        // Edit existing properties.
        $propertyDescriptions = $this->_getParam('property_description');
        foreach ($propertyDescriptions as $propertyId => $propertyDescription) {
            $property = $this->_helper->db->getTable('ItemRelationsProperty')->find($propertyId);
            $property->description = $propertyDescription;
            $property->save();
        }
        
        // Add new properties.
        $newPropertyLabels = $this->_getParam('new_property_label');
        $newPropertyDescriptions = $this->_getParam('new_property_description');
        foreach ($newPropertyLabels as $key => $newPropertyLabel) {
            $newPropertyLabel = trim($newPropertyLabel);
            $newPropertyDescription = trim($newPropertyDescriptions[$key]);
            
            // Labels are required.
            if (!$newPropertyLabel) {
                continue;
            }
            
            // Labels must be unique.
            if ($this->_helper->db->getTable('ItemRelationsProperty')->findByLabel($newPropertyLabel)) {
                continue;
            }
            
            $newProperty = new ItemRelationsProperty;
            $newProperty->vocabulary_id = $vocabularyId;
            $newProperty->local_part = ''; // cannot be NULL
            $newProperty->label = $newPropertyLabel;
            $newProperty->description = $newPropertyDescription;
            $newProperty->save();
        }
        
        // Delete existing properties.
        $propertyDeletes = $this->_getParam('property_delete');
        foreach ($propertyDeletes as $propertyId => $propertyDelete) {
            if ($propertyDelete) {
                $this->_helper->db->getTable('ItemRelationsProperty')->find($propertyId)->delete();
            }
        }
    }
}
