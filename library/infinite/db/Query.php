<?php
/**
 * library/db/ActiveRecord.php
 *
 * @author Jacob Morrison <jacob@infinitecascade.com>
 * @package infinite
 */


namespace infinite\db;

class Query extends \yii\db\Query
{
    const EVENT_BEFORE_QUERY = 'beforeQuery';
    
	use QueryTrait;
}