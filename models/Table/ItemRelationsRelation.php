<?php
/**
 * Item Relations
 *
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Item Relations relations table class.
 *
 * @package ItemRelations
 */
class Table_ItemRelationsRelation extends Omeka_Db_Table
{

    protected $_alias = 'item_relations_relations';
    
    /**
     * Finds all item relations by subject item ID.
     * 
     * @return array
     */
    public function findBySubjectItemId($subjectItemId)
    {
        $db = $this->getDb();
        $select = $this->getSelect()
                       ->join(array('item_relations_properties' => $db->ItemRelationsProperty), 
                              'item_relations_relations.property_id = item_relations_properties.id', 
                              array('property_vocabulary_id' => 'vocabulary_id', 
                                    'property_local_part' => 'local_part', 
                                    'property_label' => 'label', 
                                    'property_description' => 'description'))
                       ->join(array('item_relations_vocabularies' => $db->ItemRelationsVocabulary), 
                              'item_relations_properties.vocabulary_id = item_relations_vocabularies.id', 
                              array('vocabulary_namespace_prefix' => 'namespace_prefix'))
                       ->where('item_relations_relations.subject_item_id = ?', (int) $subjectItemId);
        return $this->fetchObjects($select);
    }
    
    /**
     * Finds all item relations by object item ID.
     * 
     * @return array
     */
    public function findByObjectItemId($objectItemId)
    {
        $db = $this->getDb();
        $select = $this->getSelect()
                       ->join(array('item_relations_properties' => $db->ItemRelationsProperty), 
                              'item_relations_relations.property_id = item_relations_properties.id', 
                              array('property_vocabulary_id' => 'vocabulary_id', 
                                    'property_local_part' => 'local_part', 
                                    'property_label' => 'label', 
                                    'property_description' => 'description'))
                       ->join(array('item_relations_vocabularies' => $db->ItemRelationsVocabulary), 
                              'item_relations_properties.vocabulary_id = item_relations_vocabularies.id', 
                              array('vocabulary_namespace_prefix' => 'namespace_prefix'))
                       ->where('item_relations_relations.object_item_id = ?', (int) $objectItemId);
        return $this->fetchObjects($select);
    }
}
