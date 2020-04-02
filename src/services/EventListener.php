<?php

namespace kbergha\revisjonsspor\services;

use kbergha\revisjonsspor\Revisjonsspor;
use yii\base\Component;
use yii\base\Event;
use yii\web\User;
use yii\web\UserEvent;
use craft\events\ElementEvent;
use craft\services\Elements;
use craft\base\ElementInterface;
use craft\base\Element;
use craft\helpers\ElementHelper;
use Craft;

class EventListener extends Component
{
    public function addEventListeners() {

        /*
         * User events
         */
        Event::on(
            User::class,
            User::EVENT_AFTER_LOGIN,
            function(UserEvent $event) {
                $this->onLogin(User::EVENT_AFTER_LOGIN);
            }
        );

        Event::on(
            User::class,
            User::EVENT_BEFORE_LOGOUT,
            function(UserEvent $event) {
                $this->onLogout(User::EVENT_BEFORE_LOGOUT);
            }
        );


        /*
         * Elements events (Assets, Users, Entries, Categories +++)
         */

        // Elements
        Event::on(
            Elements::class,
            Elements::EVENT_AFTER_SAVE_ELEMENT,
            function(ElementEvent $event) {
                $this->onSaveElement(Elements::EVENT_AFTER_SAVE_ELEMENT, $event->element, $event->isNew);
            }
        );

        Event::on(
            Elements::class,
            Elements::EVENT_BEFORE_DELETE_ELEMENT,
            function(ElementEvent $event) {
                $this->onDeleteElement(Elements::EVENT_BEFORE_DELETE_ELEMENT, $event->element);
            }
        );


        /*
         * Some other events
         * */
    }

    public function onLogin($eventName)
    {
        $properties = $this->getDefaultProperties($eventName);
        $message = 'User '.$properties['userName'].' (id: '.$properties['userId'].') logged in';
        $this->log($message, $properties);
    }

    public function onLogout($eventName)
    {
        $properties = $this->getDefaultProperties($eventName);
        $message = 'User '.$properties['userName'].' (id: '.$properties['userId'].') is logging out';
        $this->log($message, $properties);
    }

    public function onSaveElement($eventName, Element $element, $isNew = false)
    {
        // Don't log drafts/revisions and propagating/resaving elements
        if (ElementHelper::isDraftOrRevision($element) || $element->propagating || $element->resaving) {
            return false;
        }

        $properties = $this->getDefaultProperties($eventName);
        $properties['type'] = $element->displayName();
        $properties['elementId'] = $element->getSourceId();
        $properties['elementUid'] = $element->getSourceUid();
        $properties['class'] = get_class($element);
        $properties['isNew'] = $isNew;

        $status = 'saved';
        if ($isNew === true) {
            $status = 'created';
        }

        $this->log('Element of type "'.$element->displayName(). '" (id: '.$properties['elementId'].') was '.$status.' by user '.$properties['userName'].' (id: '.$properties['userId'].')', $properties);
    }


    public function onDeleteElement($eventName, Element $element)
    {
        // Don't log drafts/revisions
        if (ElementHelper::isDraftOrRevision($element)) {
            return false;
        }

        $properties = $this->getDefaultProperties($eventName);
        $properties['type'] = $element->displayName();
        $properties['elementId'] = $element->getSourceId();
        $properties['elementUid'] = $element->getSourceUid();
        $properties['class'] = get_class($element);

        $this->log('Element of type "'.$element->displayName(). '" (id: '.$properties['elementId'].') was deleted by user '.$properties['userName'].' (id: '.$properties['userId'].')', $properties);
    }


    /**
     * @return array
     */
    protected function getDefaultProperties($eventName) : array
    {
        $app = Craft::$app;
        $request = $app->getRequest();

        $properties = [
            'event' => $eventName,
            'userId' => null,
            'userName' => null,
            'ip' => $request->getUserIP(),
            'ua' => $request->getUserAgent(),
            'path' => $request->getFullPath(),
            'frontend' => $request->isSiteRequest,
            'controlPanel' => $request->isCpRequest,
            'siteId' => $app->getSites()->currentSite->id
        ];

        $user = $app->getUser();
        if ($user->getIsGuest() === false) {
            $identity = $user->getIdentity();
            $properties['userId'] = $identity->id;
            $properties['userName'] = $identity->username;
        }

        return $properties;
    }

    protected function log($message, $properties)
    {
        $settings = Revisjonsspor::$plugin->getSettings();
        // @todo: make properties logging configurable?
        // @todo: try-catch

        $message .= ' - props: '.\json_encode($properties);

        Craft::getLogger()->log($message, $settings['level'], $settings['category']);
    }
}
