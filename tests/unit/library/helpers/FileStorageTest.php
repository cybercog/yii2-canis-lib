<?php

namespace infiniteunit\library\helpers;

use Yii;
use infiniteunit\TestCase;

class FileStorageTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockApplication();
    }

    public function testTempFile()
    {
        $this->mockApplication();
        $tmp = Yii::$app->fileStorage->getTempFile(false, null);
        $this->assertTrue($tmp && file_exists($tmp));

        $tmp = Yii::$app->fileStorage->getTempFile(false, 'test');
        $this->assertTrue(substr($tmp, -4) === 'test' && !file_exists($tmp), $tmp.' does not have correct file extension (test !== '.substr($tmp, -4).')');
    }
}
