<?php
namespace infinite\db;

use infinite\db\behaviors\SearchTerm;

trait ActiveRecordRegistryTrait
{
    use SearchTerm;

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'Registry' => [
                'class' => 'infinite\db\behaviors\Registry',
            ],
            'Relatable' => [
                'class' => 'infinite\db\behaviors\Relatable',
            ],
            'ActiveAccess' => [
                'class' => 'infinite\db\behaviors\ActiveAccess',
            ],
            'Roleable' => [
                'class' => 'infinite\db\behaviors\Roleable',
            ],
        ]);
    }
}
