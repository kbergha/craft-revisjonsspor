<?php

/**
 * Craft Revisjonsspor config
 * Copy this file to the config-folder of your project.
 * Rename the file revisjonsspor.php
 *
 * Enabled: Are you sure? Default is false
 * If you use getenv or similar for the enabled config, remember that false !== "false" and true !== "true".
 *
 * Category: This is a "tag", that all log messages will have if provided. Default is "audit"
 *
 * The default from Craft/Yii is "application"
 * If you use ELK-stack or similar, change to something unique to help you filter / search for
 * audit events in the log. Especially useful if you are logging to JSON.
 *
 * Level: The level. Yes. See constants under https://www.yiiframework.com/doc/api/2.0/yii-log-logger
 * Default is Logger::LEVEL_INFO
 *
 */
return [
    'enabled' => true,
    'category' => 'audit',
    'level' => Craft::getLogger()::LEVEL_INFO
];
