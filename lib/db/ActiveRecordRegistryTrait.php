<?php
namespace teal\db;

use teal\db\behaviors\SearchTerm;

trait ActiveRecordRegistryTrait
{
    use SearchTerm;

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'Registry' => [
                'class' => 'teal\db\behaviors\Registry',
            ],
            'Relatable' => [
                'class' => 'teal\db\behaviors\Relatable',
            ],
            'ActiveAccess' => [
                'class' => 'teal\db\behaviors\ActiveAccess',
            ],
            'Roleable' => [
                'class' => 'teal\db\behaviors\Roleable',
            ],
        ]);
    }
}
