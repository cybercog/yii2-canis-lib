<?php
/**
 * library/db/models/Group.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db\models;

class Group extends \infinite\db\ActiveRecord
{
    public static function tableName()
    {
        return 'group';
    }

    public static function queryBehaviors()
    {
        return [
            'Access' => [
                'class' => '\infinite\db\behaviors\Access'
            ]
        ];
    }
}
