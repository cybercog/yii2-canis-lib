<?php
namespace canis\db;

use canis\db\behaviors\SearchTerm;

trait ActiveRecordRegistryTrait
{
    use SearchTerm;

    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            'Registry' => [
                'class' => 'canis\db\behaviors\Registry',
            ],
            'Relatable' => [
                'class' => 'canis\db\behaviors\Relatable',
            ],
            'ActiveAccess' => [
                'class' => 'canis\db\behaviors\ActiveAccess',
            ],
            'Roleable' => [
                'class' => 'canis\db\behaviors\Roleable',
            ],
        ]);
    }
}
