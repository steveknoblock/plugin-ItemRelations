<?php
/**
 * Item Relations
 *
 * @copyright Copyright 2008-2012 Roy Rosenzweig Center for History and New Media
 * @license http://www.gnu.org/licenses/gpl-3.0.txt GNU GPLv3
 */

/**
 * The Item Relations page record class.
 *
 * @package ItemRelations
 */
class ItemRelationsRelation extends Omeka_Record_AbstractRecord
{
    public $id;
    public $subject_item_id;
    public $property_id;
    public $object_item_id;
}
