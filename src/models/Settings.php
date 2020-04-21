<?php

namespace kbergha\revisjonsspor\models;

use craft\base\Model;
use yii\log\Logger;

class Settings extends Model
{
    public $enabled = false;
    public $category = 'audit';
    public $level = Logger::LEVEL_INFO;
    public $frontend = false;
    public $backend = true;
    public $properties = false;
}
