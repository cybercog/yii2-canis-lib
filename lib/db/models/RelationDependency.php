<?php

namespace canis\db\models;

use Yii;

/**
 * RelationDependency is the model class for table "relation_dependency".
 *
 * @property string $id
 * @property string $parent_relation_id
 * @property string $child_relation_id
 * @property Relation $childRelation
 * @property Relation $parentRelation
 *
 * @author Jacob Morrison <email@ofjacob.com>
 */
class RelationDependency extends \canis\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'relation_dependency';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_relation_id', 'child_relation_id'], 'required'],
            [['parent_relation_id', 'child_relation_id'], 'integer'],
            [['parent_relation_id', 'child_relation_id'], 'unique', 'targetAttribute' => ['parent_relation_id', 'child_relation_id'], 'message' => 'The combination of Parent Relation ID and Child Relation ID has already been taken.'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'parent_relation_id' => 'Parent Relation ID',
            'child_relation_id' => 'Child Relation ID',
        ];
    }

    /**
     * Get child relation.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getChildRelation()
    {
        return $this->hasOne(Yii::$app->classes['Relation'], ['id' => 'child_relation_id']);
    }

    /**
     * Get parent relation.
     *
     * @return \yii\db\ActiveQuery
     */
    public function getParentRelation()
    {
        return $this->hasOne(Yii::$app->classes['Relation'], ['id' => 'parent_relation_id']);
    }

    /**
     * Get dependency.
     *
     * @return [[@doctodo return_type:getDependencyId]] [[@doctodo return_description:getDependencyId]]
     */
    public function getDependencyId()
    {
        return $this->parent_relation_id . '.' . $this->child_relation_id;
    }
}
