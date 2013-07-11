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

    protected $_alias = 'irir';
    
    /**
     * Finds all item relations by subject item ID.
     * 
     * @return array
     */
    public function findBySubjectItemId($subjectItemId)
    {
        $db = _helper->db->getDb();
        $select = $this->getSelect()
                       ->join(array('irp' => $db->ItemRelationsProperty), 
                              'irir.property_id = irp.id', 
                              array('property_vocabulary_id' => 'vocabulary_id', 
                                    'property_local_part' => 'local_part', 
                                    'property_label' => 'label', 
                                    'property_description' => 'description'))
                       ->join(array('irv' => $db->ItemRelationsVocabulary), 
                              'irp.vocabulary_id = irv.id', 
                              array('vocabulary_namespace_prefix' => 'namespace_prefix'))
                       ->where('irir.subject_item_id = ?', (int) $subjectItemId);
        return $this->fetchObjects($select);
    }
    
    /**
     * Finds all item relations by object item ID.
     * 
     * @return array
     */
    public function findByObjectItemId($objectItemId)
    {
        $db = _helper->db->getDb();
        $select = $this->getSelect()
                       ->join(array('irp' => $db->ItemRelationsProperty), 
                              'irir.property_id = irp.id', 
                              array('property_vocabulary_id' => 'vocabulary_id', 
                                    'property_local_part' => 'local_part', 
                                    'property_label' => 'label', 
                                    'property_description' => 'description'))
                       ->join(array('irv' => $db->ItemRelationsVocabulary), 
                              'irp.vocabulary_id = irv.id', 
                              array('vocabulary_namespace_prefix' => 'namespace_prefix'))
                       ->where('irir.object_item_id = ?', (int) $objectItemId);
        return $this->fetchObjects($select);
    }
}
