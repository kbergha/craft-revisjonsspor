<?php

namespace kbergha\revisjonsspor\services;

use craft\services\Users;
use kbergha\revisjonsspor\Revisjonsspor;
use yii\base\Component;
use yii\base\Event;
use yii\web\User;
use yii\web\UserEvent as YiiUserEvent;
use craft\events\UserEvent as CraftUserEvent;
use craft\events\ElementEvent;
use craft\events\UserGroupsAssignEvent;
use craft\services\Elements;
use craft\base\Element;
use craft\helpers\ElementHelper;
use Craft;

class EventListener extends Component
{
    /**
     * Add listeners for various events.
     */
    public function addEventListeners() {

        /*
         * User events
         */
        Event::on(
            User::class,
            User::EVENT_AFTER_LOGIN,
            function(YiiUserEvent $event) {
                $this->onLogin(User::EVENT_AFTER_LOGIN);
            }
        );

        Event::on(
            User::class,
            User::EVENT_BEFORE_LOGOUT,
            function(YiiUserEvent $event) {
                $this->onLogout(User::EVENT_BEFORE_LOGOUT);
            }
        );

        Event::on(
            Users::class,
            Users::EVENT_AFTER_ACTIVATE_USER,
            function(CraftUserEvent $event) {
                $this->onActivatedUser(Users::EVENT_AFTER_ACTIVATE_USER, $event);
            }
        );

        Event::on(
            Users::class,
            Users::EVENT_AFTER_ASSIGN_USER_TO_GROUPS,
            function(UserGroupsAssignEvent $event) {
                $this->onAssignedUserGroups(Users::EVENT_AFTER_ASSIGN_USER_TO_GROUPS, $event);
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
         * Some other events - none so far
         */

    }

    /**
     * When a user is activated.
     *
     * @param $eventName
     * @param UserGroupsAssignEvent $event
     */
    public function onActivatedUser($eventName, CraftUserEvent $event)
    {

        $properties = $this->getDefaultProperties($eventName);
        $user = $event->user;
        $message = 'User with username '.$user->username.', email '.$user->email.' and id '.$user->getId().' was activated';
        $this->log($message, $properties);
    }

    /**
     * When a user is added or removed from user groups.
     *
     * @param $eventName
     * @param UserGroupsAssignEvent $event
     */
    public function onAssignedUserGroups($eventName, UserGroupsAssignEvent $event)
    {

        $userGroupString = 'was assigned user groups with id '.implode(', ', $event->groupIds);
        if (count($event->groupIds) === 0) {
            $userGroupString = 'was removed from all user groups';
        }

        $properties = $this->getDefaultProperties($eventName);
        $message = 'User with id '.$event->userId .' '.$userGroupString.' by '.$properties['userName'].' (id: '.$properties['userId'].')';
        $this->log($message, $properties);
    }

    /**
     * When a user logs in.
     *
     * @param $eventName
     */
    public function onLogin($eventName)
    {
        $properties = $this->getDefaultProperties($eventName);
        $message = 'User '.$properties['userName'].' (id: '.$properties['userId'].') logged in';
        $this->log($message, $properties);
    }

    /**
     * When a user logs out.
     *
     * @param $eventName
     */
    public function onLogout($eventName)
    {
        $properties = $this->getDefaultProperties($eventName);
        $message = 'User '.$properties['userName'].' (id: '.$properties['userId'].') is logging out';
        $this->log($message, $properties);
    }

    /**
     * Do something useful when an element is saved
     *
     * @param $eventName
     * @param Element $element
     * @param bool $isNew
     * @return bool
     */
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

        /*@todo: DRY */
        $userInfo = '';
        if ($element instanceof \craft\elements\User) {
            /* @var $element \craft\elements\User */
            $userInfo = 'username: '.$element->username.', email: '. $element->email.', ';
        }

        /*@todo: DRY */
        $by = '';
        if ($properties['userId'] !== null) {
            $by = ' by user '.$properties['userName'].' (id: '.$properties['userId'].')';
        }

        $this->log('Element of type "'.$element->refHandle(). '" ('.$userInfo.'id: '.$properties['elementId'].') was '.$status.$by, $properties);
    }


    /**
     * Do something useful when an element is deleted.
     *
     * @param $eventName
     * @param Element $element
     * @return bool
     */
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

        /*@todo: DRY */
        $userInfo = '';
        if ($element instanceof \craft\elements\User) {
            /* @var $element \craft\elements\User */
            $userInfo = 'username: '.$element->username.', email: '. $element->email.', ';
        }

        /*@todo: DRY */
        $by = '';
        if ($properties['userId'] !== null) {
            $by = ' by user '.$properties['userName'].' (id: '.$properties['userId'].')';
        }

        $this->log('Element of type "'.$element->refHandle(). '" ('.$userInfo.'id: '.$properties['elementId'].') was deleted'.$by);
    }


    /**
     * Get an array of various useful properties that can be used for various useful things.
     *
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

    /**
     * Log to whatever Craft is configured to log to.
     *
     * @param $message
     * @param null $properties
     */
    protected function log($message, $properties = null)
    {
        try {
            $settings = Revisjonsspor::$plugin->getSettings();

            if ($settings->properties === true && is_array($properties) && count($properties) > 0) {
                // @todo: consider options for logging properties - not always json.
                $message .= ' - props: '.\json_encode($properties);
            }

            Craft::getLogger()->log($message, $settings->level, $settings->category);

        } catch (\Exception $e) {
            Craft::getLogger()->log('Could not log to audit log, exception error message: '.$e->getMessage(), Craft::getLogger()::LEVEL_ERROR);
        }
    }
}
