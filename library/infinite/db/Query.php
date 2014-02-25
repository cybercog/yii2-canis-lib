<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;
use infinite\base\ComponentTrait;

class Query extends \yii\db\Query
{
    const EVENT_BEFORE_QUERY = 'beforeQuery';
    
	use QueryTrait;
    use ComponentTrait;
}