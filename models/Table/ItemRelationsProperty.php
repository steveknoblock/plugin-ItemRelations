<?php

class Table_ItemRelationsProperty extends Omeka_Db_Table
{
    //protected $_alias = $this->getTableAlias();
    
    //'item_relations_properties';
    
    /**
     * Find properties by vocabulary.
     * 
     * @param int $id The vocabulary ID.
     * @return array
     */
    public function findByVocabularyId($id)
    {
        $select = $this->getSelect();
        
        $select->where('vocabulary_id = ?', (int) $id)
               ->order('id');
        
        return $this->fetchObjects($select);
    }
    
    /**
     * Find all properties with their vocabulary data.
     * 
     * @return array
     */
    public function findAllWithVocabularyData()
    {

        $db = $this->getDb();
        $select = $this->getSelect();
        
        $select->join(array('item_relations_vocabularies' => $db->ItemRelationsVocabulary), 
                      'item_relations_properties.vocabulary_id = item_relations_vocabularies.id', 
                      array('vocabulary_name' => 'name', 
                            'vocabulary_description' => 'description', 
                            'vocabulary_namespace_prefix' => 'namespace_prefix', 
                            'vocabulary_namespace_uri' => 'namespace_uri'))
               ->order('custom DESC');
        
        return $this->fetchObjects($select);
    }
    
    public function findByLabel($label)
    {
        $select = $this->getSelect();
        
        $select->where('label = ?', $label);
        
        return $this->fetchObjects($select);
    }
}
