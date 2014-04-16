<?php
/**
 * library/db/behaviors/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */

namespace infinite\db\behaviors;

class Model extends \yii\base\Behavior
{
    public function safeAttributes()
    {
        return [];
    }
}
