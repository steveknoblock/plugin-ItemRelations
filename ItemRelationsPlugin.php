<?php
/**
 * Item Relations
 * Upgraded 2013 by Steve Knoblock
 * @copyright Copyright 2008-2013 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */


// helper functions
// todo
require_once dirname(__FILE__) . '/helpers/ItemRelationsFunctions.php';

/**
 * Item Relations plugin.
 */
class ItemRelationsPlugin extends Omeka_Plugin_AbstractPlugin
{
    /**
     * @var array Hooks for the plugin.
     */
    protected $_hooks = array(
    	'install',
    	'uninstall',
    	'upgrade',
        'config',
		'config_form',
        'define_acl',
    	'initialize',
		'after_save_item',
        'admin_items_show_sidebar',
 		'public_items_show',
        'html_purifier_form_submission',
    );


    /**
     * @var array Filters for the plugin.
     */
    
    protected $_filters = array(
    	'admin_items_form_tabs',
    	'admin_navigation_main',
        'search_record_types',
        'page_caching_whitelist',
        'page_caching_blacklist_for_record'
    );
	
	
    /**
     * @var array Options and their default values.
     */
    protected $_options = array(
        'item_relations_public_items_show' => null,
        'item_relations_relation_format' => null
    );


	// Configuration defaults.
    const DEFAULT_PUBLIC_ITEMS_SHOW = 1;
    const DEFAULT_RELATION_FORMAT = 'prefix_local_part';


    /**
     * Install the plugin.
     */
    public function hookInstall()
    {

        // Create tables.
        $db = $this->_db;

	/*
	print "CALLING: getTableName('ItemRelationsVocabulary')<br>";
	$tmp = $db->getTableName('ItemRelationsVocabulary');
	print 'RESULT: '. $tmp .'<br>';
	print "CALLING: getTableName('ItemRelationsProperty')<br>";
	$tmp = $db->getTableName('ItemRelationsProperty');
	print  'RESULT: '. $tmp .'<br>';
	*/

        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->ItemRelationsVocabulary` (
         	`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(100) NOT NULL,
            `description` text,
            `namespace_prefix` varchar(100) NOT NULL,
            `namespace_uri` varchar(200) DEFAULT NULL,
            `custom` BOOLEAN NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);

        $sql = "
        CREATE TABLE IF NOT EXISTS `$db->ItemRelationsProperty` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `vocabulary_id` int(10) unsigned NOT NULL,
            `local_part` varchar(100) NOT NULL,
            `label` varchar(100) DEFAULT NULL,
            `description` text,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);    
              
         $sql = "
        CREATE TABLE IF NOT EXISTS `$db->ItemRelationsRelation` (
             `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `subject_item_id` int(10) unsigned NOT NULL,
            `property_id` int(10) unsigned NOT NULL,
            `object_item_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
        $db->query($sql);


        // Install the formal vocabularies and their properties.
        $formalVocabularies = include 'formal_vocabularies.php';
        
        
        foreach ($formalVocabularies as $formalVocabulary) {

            $vocabulary = new ItemRelationsVocabulary;
            $vocabulary->name = $formalVocabulary['name'];
            $vocabulary->description = $formalVocabulary['description'];
            $vocabulary->namespace_prefix = $formalVocabulary['namespace_prefix'];
            $vocabulary->namespace_uri = $formalVocabulary['namespace_uri'];
            $vocabulary->custom = 0;
            $vocabulary->save();
            
            $vocabularyId = $db->lastInsertId();
			
            foreach ($formalVocabulary['properties'] as $formalProperty) {
            
                $property = new ItemRelationsProperty;
                
                $property->vocabulary_id = $vocabularyId;
                $property->local_part = $formalProperty['local_part'];
                $property->label = $formalProperty['label'];
                $property->description = $formalProperty['description'];
                $property->save();
            }
        }

        
        // Install a custom vocabulary.
        $customVocabulary = new ItemRelationsVocabulary;
        $customVocabulary->name = 'Custom';
        $customVocabulary->description = 'Custom vocabulary containing relations defined for this Omeka instance.';
        $customVocabulary->namespace_prefix = ''; // cannot be NULL
        $customVocabulary->namespace_uri = null;
        $customVocabulary->custom = 1;
        $customVocabulary->save();

        $this->_installOptions();

    }
    
    
    /**
     * Uninstall the plugin.
     */
    public function hookUninstall()
    {        
	    $db = $this->_db;

        // Drop the vocabularies table.
        $sql = "DROP TABLE IF EXISTS `$db->ItemRelationsVocabulary`";
        $db->query($sql);

 		// Drop the properties table.
        $sql = "DROP TABLE IF EXISTS `$db->ItemRelationsProperty`";
        $db->query($sql);

        // Drop the relations table.
        $sql = "DROP TABLE IF EXISTS `$db->ItemRelationsRelation`";
        $db->query($sql);

        $this->_uninstallOptions();
    }
    
        
   /**
     * Display the plugin configuration form.
     */
    public static function hookConfigForm()
    {
        $publicAppendToItemsShow = get_option('item_relations_public_items_show');
        if (null == $publicAppendToItemsShow) {
            $publicAppendToItemsShow = self::DEFAULT_PUBLIC_ITEMS_SHOW;
        }
        
        $relationFormat = get_option('item_relations_relation_format');
        if (null == $relationFormat) {
            $relationFormat = self::DEFAULT_RELATION_FORMAT;
        }
        require dirname(__FILE__) . '/config_form.php';
    }
    
    
    /**
     * Handle the plugin configuration form.
     * 
     * @param array $params
     */
    public static function hookConfig()
    {
  	
  	// modeled on SimplePages handler
	//  set_option('simple_pages_filter_page_content', (int)(boolean)$_POST['simple_pages_filter_page_content']);
 
 	// Set options
        set_option('item_relations_public_items_show', 
        (int)(boolean)$_POST['item_relations_public_items_show']);
        set_option('item_relations_relation_format', 
                   $_POST['item_relations_relation_format']);
    }
    
    
    /**
     * Upgrade the plugin.
     *
     * @param array $args contains: 'old_version' and 'new_version'
     */
   	public function hookUpgrade($args)
    {
    //unimplemented
    /*
    	What changes between 1.0 and this version?
    	
    	name of relations table
    */
    /* code example from SimplePages plugin
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];
        $db = $this->_db;
         if ($oldVersion < '2.0') {
         
            $db->query("ALTER TABLE `$db->SimplePagesPage` DROP `add_to_public_nav`");
            delete_option('simple_pages_home_page_id');
            
          	$sql = "ALTER TABLE `$db->SimplePagesPage` ADD INDEX ( `is_published` )";
            $db->query($sql);    
            
        	$sql = "ALTER TABLE `$db->SimplePagesPage` ADD `parent_id` INT UNSIGNED NOT NULL ";
            $db->query($sql);
        */
            
    }
    
    
   /**
     * Add the translations.
     */
    public function hookInitialize()
    {
       // unimplemented
    }


    /**
     * Define the ACL.
     * 
     * @param Omeka_Acl
     */
    public function hookDefineAcl($args)
    {
    
        $acl = $args['acl'];
        
        $indexResource = new Zend_Acl_Resource('ItemRelations_Index');
        $pageResource = new Zend_Acl_Resource('ItemRelations_Relation');
        $acl->add($indexResource);
        $acl->add($pageResource);

        $acl->allow(array('super', 'admin'), array('ItemRelations_Index', 'ItemRelations_Relation'));
        $acl->allow(null, 'ItemRelations_Relation', 'show');
        $acl->deny(null, 'ItemRelations_Relation', 'show-unpublished');
        
    }
    
    
    /**
     * Filter the 'text' field of the simple-pages form, but only if the 
     * 'simple_pages_filter_page_content' setting has been enabled from within the
     * configuration form.
     * 
     * @param array $args Hook args, contains:
     *  'request': Zend_Controller_Request_Http
     *  'purifier': HTMLPurifier
     */
    public function hookHtmlPurifierFormSubmission($args)
    {
       // unimplemented
    }
    
    
    /**
     * Display item relations on the public items show page.
     */
    public static function hookPublicAppendToItemsShow()
    {
        if ('1' == get_option('item_relations_public_items_show')) {
            $item = get_current_record('item');
            item_relations_display_relations($item);
        }
    }
   
   
	/**
	 * Display item relations.
	 * Todo This is a helper function from 1.x version, remove or do whatever is recommended for functions mixed into the plugin hooks and filters.
	 * @param Item $item
	 */
	protected function item_relations_display_relations(Item $item)
	{
		$subjectRelations = ItemRelationsPlugin::prepareSubjectRelations($item);
		$objectRelations = ItemRelationsPlugin::prepareObjectRelations($item);
		include 'public_items_show.php';
	}


    /**
     * Display item relations on the public items show page.
     */  
    public function hookPublicItemsShow() {
		if ('1' == get_option('item_relations_public_items_show')) {
            $item = get_current_record('item');
            $this->item_relations_display_relations($item);
            
            /*
                $subjectRelations = ItemRelationsPlugin::prepareSubjectRelations($item);
    			$objectRelations = ItemRelationsPlugin::prepareObjectRelations($item);
    			include 'public_items_show.php';
    		*/
        }    
    }


    /**
     * Add the Item Relations link to the admin main navigation.
     * 
     * @param array Navigation array.
     * @return array Filtered navigation array.
     */
    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array(
            'label' => __('Item Relations'),
            'uri' => url('item-relations'),
            'resource' => 'ItemRelations_Index',
            'privilege' => 'index'
        );
        return $nav;
    }
    
    

    /**
     * Add ItemRelationsRelation as a searchable type.
     */
    public function filterSearchRecordTypes($recordTypes)
    {
        $recordTypes['ItemRelationsRelation'] = __('Item Relations');
        return $recordTypes;
    }


    /**
     * Specify the default list of urls to whitelist
     * 
     * @param $whitelist array An associative array urls to whitelist, 
     * where the key is a regular expression of relative urls to whitelist 
     * and the value is an array of Zend_Cache front end settings
     * @return array The whitelist
     */
    public function filterPageCachingWhitelist($whitelist)
    {
        // unimplemented
        
    }

    /**
     * Add pages to the blacklist
     * 
     * @param $blacklist array An associative array urls to blacklist, 
     * where the key is a regular expression of relative urls to blacklist 
     * and the value is an array of Zend_Cache front end settings
     * @param $record
     * @param $args Filter arguments. contains:
     * - record: the record
     * - action: the action
     * @return array The blacklist
     */
    public function filterPageCachingBlacklistForRecord($blacklist, $args)
    {
       // unimplemented
    }
    
    
    
    /*************************************************************************/
    
   /**
     * Prepare subject item relations for display.
     * 
     * @param Item $item
     * @return array
     */
    public static function prepareSubjectRelations(Item $item)
    {
		
		//$db = get_db();
        //$subjects = $db->getTable('ItemRelationsItemRelation')->findBySubjectItemId($item->id);
        
        $subjects = get_db()->getTable('ItemRelationsRelation')->findBySubjectItemId($item->id);
        $subjectRelations = array();

        foreach ($subjects as $subject) {
            $subjectRelations[] = array('item_relation_id' => $subject->id, 
                                        'object_item_id' => $subject->object_item_id, 
                                        'object_item_title' => self::getItemTitle($subject->object_item_id), 
                                        'relation_text' => self::getRelationText($subject->vocabulary_namespace_prefix, 
                                        $subject->property_local_part, 
                                        $subject->property_label), 
                                        'relation_description' => $subject->property_description);
        }
        return $subjectRelations;
    }
    
    /**
     * Prepare object item relations for display.
     * 
     * @param Item $item
     * @return array
     */
    public static function prepareObjectRelations(Item $item)
    {
        $objects = get_db()->getTable('ItemRelationsRelation')->findByObjectItemId($item->id);
        $objectRelations = array();
        foreach ($objects as $object) {
            $objectRelations[] = array('item_relation_id' => $object->id, 
                                       'subject_item_id' => $object->subject_item_id, 
                                       'subject_item_title' => self::getItemTitle($object->subject_item_id), 
                                       'relation_text' => self::getRelationText($object->vocabulary_namespace_prefix, 
                                    	$object->property_local_part, 
                                        $object->property_label), 
                                       'relation_description' => $object->property_description);
        }
        return $objectRelations;
    }


	/**
     * Return a item's title.
     * 
     * @param int $itemId The item ID.
     * @return string
     */
    public static function getItemTitle($itemId)
    {
       //$title = item('Dublin Core', 'Title', array(), get_record_by_id('item', $id));
       $title = metadata(get_record_by_id('item', $itemId), array('Dublin Core', 'Title'), 'all');
       // rewrite this
        if (!trim($title[0])) {
            $title = $itemId;
        }
        return $title[0];
    }
    

    
    /**
     * Insert an item relation.
     * 
     * @param Item|int $subjectItem
     * @param int $propertyId
     * @param Item|int $objectItem
     * @return bool True: success; false: unsuccessful
     */
    public static function insertItemRelation($subjectItem, $propertyId, $objectItem)
    {

        // Only numeric property IDs are valid.
        if (!is_numeric($propertyId)) {
            return false;
        }
        
        // Set the subject item.
        if (!($subjectItem instanceOf Item)) {
            $subjectItem = get_db()->getTable('Item')->find($subjectItem);
        }
        
        // Set the object item.
        if (!($objectItem instanceOf Item)) {
            $objectItem = get_db()->getTable('Item')->find($objectItem);
        }
        
        // Don't save the relation if the subject or object items don't exist.
        if (!$subjectItem || !$objectItem) {
            return false;
        }
        
        $itemRelation = new ItemRelationsRelation;
        $itemRelation->subject_item_id = $subjectItem->id;
        $itemRelation->property_id = $propertyId;
        $itemRelation->object_item_id = $objectItem->id;
        $itemRelation->save();
        
        return true;
    }
    

    /**
     * Prepare special variables before saving the form.
     */
    protected function beforeSave($args)
    {
    	// unimplemented
		//debug("IN beforeSave");
    }
    
/*
original
   
    public static function afterSaveFormRecord($record, $post)
    {
        $db = get_db();
        
        if (!($record instanceof Item)) {
            return;
        }
        
        // Save item relations.
        foreach ($post['item_relations_property_id'] as $key => $propertyId) {
            
            $insertedItemRelation = self::insertItemRelation(
                $record, 
                $propertyId, 
                $post['item_relations_item_relation_object_item_id'][$key]
            );
            if (!$insertedItemRelation) {
                continue;
            }
        }
        
        // Delete item relations.
        if (isset($post['item_relations_item_relation_delete'])) {
            foreach ($post['item_relations_item_relation_delete'] as $itemRelationId) {
                $itemRelation = $db->getTable('ItemRelationsItemRelation')->find($itemRelationId);
                // When an item is related to itself, deleting both relations 
                // simultaniously will result in an error. Prevent this by 
                // checking if the item relation exists prior to deletion.
                if ($itemRelation) {
                    $itemRelation->delete();
                }
            }
        }
    }
*/

   /**
     * Save the item relations after saving an item add/edit form.
     * 
     * @param Omeka_Record $record
     * @param array $post
     */
    public function hookAfterSaveItem($args)
    {
    //debug("IN hookAfterSaveItem()");

    	$record = $args['record'];
    	$post = $args['post'];
    
        $db = get_db();
        
        if (!($record instanceof Item)) {
            return;
        }
        
        // Save item relations.
        foreach ($post['item_relations_property_id'] as $key => $propertyId) {
            
            $insertedItemRelation = self::insertItemRelation(
                $record, 
                $propertyId, 
                $post['item_relations_item_relation_object_item_id'][$key]
            );
            if (!$insertedItemRelation) {
                continue;
            }
        }
        
        // Delete item relations.
        if (isset($post['item_relations_item_relation_delete'])) {
            foreach ($post['item_relations_item_relation_delete'] as $itemRelationId) {
                $itemRelation = $db->getTable('ItemRelationsRelation')->find($itemRelationId);
                // When an item is related to itself, deleting both relations 
                // simultaniously will result in an error. Prevent this by 
                // checking if the item relation exists prior to deletion.
                if ($itemRelation) {
                    $itemRelation->delete();
                }
            }
        }
    }


    /**
     * Display item relations on the admin items show page.
     * 
     * @param Item $item
     */
   
    public function hookAdminItemsShowSidebar($args)
    {
    	$view = $args['view'];
    	$item = $args['item'];
        $subjectRelations = self::prepareSubjectRelations($item);
        $objectRelations = self::prepareObjectRelations($item);
        
        $html = '';
        $html .= '<div class="info-panel">';
        $html .= '<h2>Item Relations</h2>';
        $html .= '<div>';
        if (!$subjectRelations && !$objectRelations) {
        	$html .= '<p>This item has no relations.</p>';
        	} else {
        		foreach ($subjectRelations as $subjectRelation) {
        	$html .= '<ul>';
        	$html .= '<li>This Item '. $subjectRelation['relation_text'];
        	$html .= ' <a href="'. url('items/show/' . $subjectRelation['object_item_id']) .'">'. $subjectRelation['object_item_title'] .'</a>';
        	$html .= '</li>';
        		}
        		foreach ($objectRelations as $objectRelation) {
        	$html .= '<ul>';
        	$html .= '<li>This Item '. $subjectRelation['relation_text'];
        	$html .= '<a href="'. url('items/show/' . $objectRelation['subject_item_id']) .'">'. $objectRelation['subject_item_title'] .'</a>';
        	$html .= $objectRelation['relation_text'];
        	$html .= 'This Item</li>';
        		}
        	$html .= '</ul>';
		}
		$html .= '</div>';
        $html .= '</div>';
        echo $html;
    }
    
    
    
    /**
     * Add the "Item Relations" tab to the admin items add/edit page.
     * 
     * @return array
     */
    public static function filterAdminItemsFormTabs($tabs, $args)
    {

    	$item = $args['item'];

        $formSelectProperties = self::getFormSelectProperties();
        $subjectRelations = self::prepareSubjectRelations($item);
        $objectRelations = self::prepareObjectRelations($item);
        
        ob_start();
        include 'item_relations_form.php';
        $content = ob_get_contents();
        ob_end_clean();
        
        $tabs['Item Relations'] = $content;
        return $tabs;
    }
   
    
    /**
     * Add the "Item Relations" tab to the admin navigation.
     * 
     * @param array $nav
     * @return array
     */
    public static function adminNavigationMain($nav)
    {
        $nav['Item Relations'] = uri('item-relations');
        return $nav;
    }
    
    /**
     * Display the item relations form on the admin advanced search page.
     */
    public static function adminAppendToAdvancedSearch()
    {
        $formSelectProperties = self::getFormSelectProperties();
        include 'advanced_search_form.php';
    }
    
    /**
     * Filter for an item relation after search page submission.
     * 
     * @param Omeka_Db_Select $select
     * @param array $params
     * @return Omeka_Db_Select
     */
    public static function itemBrowseSql($select, $params)
    {
        if (is_numeric($_GET['item_relations_property_id'])) {
            $db = get_db();
            // Set the field on which to join.
            if ($_GET['item_relations_clause_part'] == 'subject') {
                $onField = 'subject_item_id';
            } else {
                $onField = 'object_item_id';
            }
            $select->join(array('item_relations_relations' => $db->ItemRelationsItemRelation), 
                          "item_relations_relations.$onField = i.id", 
                          array())
                   ->where('item_relations_relations.property_id = ?', $_GET['item_relations_property_id']);
        }
        return $select;
    }
    
    /**
     * Add custom fields to the item batch edit form.
     */
    public static function adminAppendToItemsBatchEditForm()
    {
        $formSelectProperties = self::getFormSelectProperties();
?>
<fieldset id="item-fields" style="width: 70%; margin-bottom:2em;">
<legend>Items Relation</legend>
<table>
    <thead>
    <tr>
        <th>Subjects</th>
        <th>Relation</th>
        <th>Object</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>These Items</td>
        <td><?php echo get_view()->formSelect('custom[item_relations_property_id]', null, array('multiple' => false), $formSelectProperties); ?></td>
        <td>Item ID <?php echo get_view()->formText('custom[item_relations_item_relation_object_item_id]', null, array('size' => 8)); ?></td>
    </tr>
    </tbody>
</table>
</fieldset>
<?php
    }
    
    /**
     * Process the item batch edit form.
     * 
     * @param Item $item
     * @param array $custom
     */
    public static function itemsBatchEditCustom($item, $custom)
    {
        self::insertItemRelation($item, 
                                 $custom['item_relations_property_id'], 
                                 $custom['item_relations_item_relation_object_item_id']);
    }
    
    /**
     * Prepare an array for formSelect().
     * 
     * @return array
     */
    public static function getFormSelectProperties()
    {

		// SimplePages example
        // $pages = get_db()->getTable('SimplePagesPage')->findAll();
        $properties = get_db()->getTable('ItemRelationsProperty')->findAllWithVocabularyData();

        $formSelectProperties = array('' => 'Select below...');
        
        foreach ($properties as $property) {
            $optionValue = self::getRelationText($property->vocabulary_namespace_prefix, 
                                                 $property->local_part, 
                                                 $property->label);
            $formSelectProperties[$property->vocabulary_name][$property->id] = $optionValue;
        }
        return $formSelectProperties;
    }
    

    
    /**
     * Return an item relation's relation/predicate text, determined by the 
     * relation format configuration.
     * 
     * @param string $namespacePrefix
     * @param string $localPart
     * @param string $label
     * @return string
     */
    public static function getRelationText($namespacePrefix, $localPart, $label)
    {
        $hasPrefixLocalPart = (bool) $namespacePrefix && (bool) $localPart;
        $hasLabel = (bool) $label;
        
        switch (get_option('item_relations_relation_format')) {
            case 'prefix_local_part';
                $relationText = $hasPrefixLocalPart ? "$namespacePrefix:$localPart" : $label;
                break;
            case 'label':
                $relationText = $hasLabel ? $label: "$namespacePrefix:$localPart";
                break;
            default:
                $relationText = '[unknown]';
        }
        
        return $relationText;
    }
    

    

    
    
}