<?php
/**
 * craft-revisjonsspor plugin for Craft CMS 3.x
 *
 * Audit trail - audit logging in Craft CMS
 *
 * @link      https://github.com/kbergha
 * @copyright Copyright (c) 2020 Knut Erik Berg-Hansen
 */

namespace kbergha\revisjonsspor;

// use kbergha\revisjonsspor\twigextensions\RevisjonssporTwigExtension;

use Craft;
use craft\base\Plugin;
use craft\services\Plugins;
use kbergha\revisjonsspor\models\Settings;

use kbergha\revisjonsspor\services\EventListener;
use yii\base\Event;
use yii\web\User;
use yii\web\UserEvent;

/**
 * Craft plugins are very much like little applications in and of themselves. We’ve made
 * it as simple as we can, but the training wheels are off. A little prior knowledge is
 * going to be required to write a plugin.
 *
 * For the purposes of the plugin docs, we’re going to assume that you know PHP and SQL,
 * as well as some semi-advanced concepts like object-oriented programming and PHP namespaces.
 *
 * https://docs.craftcms.com/v3/extend/
 *
 * @author    Knut Erik Berg-Hansen
 * @package   Revisjonsspor
 * @since     1.0.0-alpha
 * @property  EventListener $listener
 *
 */
class Revisjonsspor extends Plugin
{
    // Static Properties
    // =========================================================================

    /**
     * Static property that is an instance of this plugin class so that it can be accessed via
     * Revisjonsspor::$plugin
     *
     * @var Revisjonsspor
     */
    public static $plugin;

    // Public Properties
    // =========================================================================

    /**
     * To execute your plugin’s migrations, you’ll need to increase its schema version.
     *
     * @var string
     */
    public $schemaVersion = '1.0.0-alpha';

    /**
     * Set to `true` if the plugin should have a settings view in the control panel.
     *
     * @var bool
     */
    public $hasCpSettings = false;

    /**
     * Set to `true` if the plugin should have its own section (main nav item) in the control panel.
     *
     * @var bool
     */
    public $hasCpSection = false;

    // Public Methods
    // =========================================================================

    /**
     * Set our $plugin static property to this class so that it can be accessed via
     * Revisjonsspor::$plugin
     *
     * Called after the plugin class is instantiated; do any one-time initialization
     * here such as hooks and events.
     *
     * If you have a '/vendor/autoload.php' file, it will be loaded for you automatically;
     * you do not need to load it in your init() method.
     *
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;

        $this->setComponents([
            'listener' => EventListener::class
        ]);

        // Add in our Twig extensions
        // Craft::$app->view->registerTwigExtension(new RevisjonssporTwigExtension());

        // Check request.
        $request = Craft::$app->getRequest();

        // @todo: Handle site request if user is logged in?
        // @todo: Consider what to do with preview / live preview requests as well.
        if ($this->getSettings()->enabled === true &&
            $request->isCpRequest === true &&
            $request->isConsoleRequest === false
        ) {
            Event::on(
                Plugins::class,
                Plugins::EVENT_AFTER_LOAD_PLUGINS,
                function () {
                    // All plugins loaded. Party time!
                    $this->listener->addEventListeners();
                }
            );
        }

        Craft::info(
            Craft::t(
                'revisjonsspor',
                '{name} plugin loaded',
                ['name' => $this->name]
            ),
            __METHOD__
        );
    }

    // Protected Methods
    // =========================================================================

    protected function createSettingsModel()
    {
        return new Settings();
    }
}
