<?php
require_once 'Table_ItemRelationsProperty.php';
class ItemRelationsProperty extends Omeka_Record_AbstractRecord
{
    public $id;
    public $vocabulary_id;
    public $local_part;
    public $label;
    public $description;
}
