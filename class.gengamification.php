<?php

/**
 *
 * GenGamification - A generic gamification PHP framework
 *
 * @author Javier Fuentes <javier.fuentes@redalumnos.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.0
 *
 */

/*

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

CREATE TABLE `t_gengamification_alerts` (
  `id_user` int(10) unsigned NOT NULL,
  `id_badge` int(10) unsigned DEFAULT NULL,
  `id_level` int(10) unsigned DEFAULT NULL,
  KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_badges` (
  `id_user` int(10) unsigned NOT NULL,
  `id_badge` int(10) unsigned NOT NULL,
  `badgescounter` int(10) unsigned NOT NULL,
  `grantdate` datetime NOT NULL,
  PRIMARY KEY (`id_user`,`id_badge`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_events` (
  `id_user` int(10) unsigned NOT NULL,
  `id_event` int(10) unsigned NOT NULL,
  `eventcounter` int(10) unsigned NOT NULL,
  `pointscounter` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_user`,`id_event`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_log` (
  `id_user` int(10) unsigned NOT NULL,
  `id_event` int(10) unsigned NOT NULL,
  `eventdate` datetime NOT NULL,
  `points` int(11) DEFAULT NULL,
  `id_badge` int(11) DEFAULT NULL,
  `id_level` int(11) DEFAULT NULL,
  KEY `id_user` (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE `t_gengamification_scores` (
  `id_user` int(10) unsigned NOT NULL,
  `points` int(10) unsigned NOT NULL,
  `id_level` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

 */

interface gengamificationDAOint {
    public function getUserAlerts($userId, $resetAlerts = false);
    public function getUserBadges($userId);
    public function getUserEvents($userId);
    public function getUserEvent($userId, $eventId);
    public function getUserScores($userId);
    public function grantBadgeToUser($userId, $badgeId);
    public function grantLevelToUser($userId, $levelId);
    public function grantPointsToUser($userId, $points);
    public function saveBadgeAlert($userId, $badgeId);
    public function saveLevelAlert($userId, $levelId);
    public function increaseEventCounter($userId, $eventId);
    public function increaseEventPoints($userId, $eventId, $points);
}

class gengamificationEvent {
    private $repetitions = null;        /* Counter trigger (1 = unique event / null = triggers every execution) */
    private $allowrepetitions = false;  /* Allows repetitions */

    private $badge = null;              /* Badge granted when triggers */
    private $descriptor = null;         /* Event descriptor */
    private $points = null;             /* Points granted when triggers */
    private $maxpoints = null;          /* Max points granted for this event */
    private $eachcallback = null;       /* Each callback function */
    private $eventcallback = null;      /* Trigger callback function */

    public function allowrepetitions() {
        return $this->allowrepetitions;
    }

    public function getBadge() {
        return $this->badge;
    }

    public function getDescriptor() {
        return $this->descriptor;
    }

    public function getEachCallback()
    {
        return $this->eachcallback;
    }

    public function getEventCallback()
    {
        return $this->eventcallback;
    }

    public function getMaxpoints() {
        return $this->maxpoints;
    }

    public function getPoints() {
        return $this->points;
    }

    public function getRequiredRepetitions() {
        return $this->repetitions;
    }

    public function setAllowRepetitions($b) {
        if (!is_bool($b)) throw new Exception(__METHOD__.': Invalid repetitions');

        $this->allowrepetitions = $b;

        return $this;
    }

    public function setBadgeGranted($str) {
        $str = trim($str);
        if (empty($str)) throw new Exception(__METHOD__.': Invalid badge');

        $this->badge = $str;

        return $this;
    }

    public function setDescriptor($str) {
        $str = trim($str);
        if (empty($str)) throw new Exception(__METHOD__.': Invalid descriptor');

        $this->descriptor = $str;

        return $this;
    }

    public function setEachCallback($f) {
        if (!is_callable($f)) throw new Exception(__METHOD__.': Invalid callback function');

        $this->eachcallback = $f;

        return $this;
    }

    public function setEventCallback($f) {
        if (!is_callable($f)) throw new Exception(__METHOD__.': Invalid callback function');

        $this->eventcallback = $f;

        return $this;
    }

    public function setMaxPointsGranted($n) {
        if (!is_numeric($n)) throw new Exception(__METHOD__.': Invalid points');

        $this->maxpoints = $n;

        return $this;
    }

    public function setPointsGranted($n) {
        if (!is_numeric($n)) throw new Exception(__METHOD__.': Invalid points');

        $this->points = $n;

        return $this;
    }

    public function setRequiredRepetitions($n) {
        if (!is_numeric($n)) throw new Exception(__METHOD__.': Invalid repetitions');

        $this->repetitions = $n;

        return $this;
    }
}

class gengamification {
    /** @var bool $letsGoParty This stops gamification execution */
    private $letsGoParty = true;

    /** @var array $events Events definitions */
    private $events = array();

    /** @var array $badges Badges definitions */
    private $badges = array();

    /** @var array $levels Levels definitions */
    private $levels = array();

    // User id
    private $userId = null;

    // Definitions counters
    private $badgesCounter = 0;
    private $eventsCounter = 0;
    private $levelsCounter = 0;

    // Events queue
    private $eventsQueue = array();

    // Data Access Object (DAO)
    /** @var gengamificationDAO $dao */
    private $dao = null;

    public function __construct($dao) {
        $this->dao = $dao;
    }


    /**
     *
     * Public functions
     *
     */

    // Add badge to gamification engine
    public function addBadge($internalDescriptor, $descriptor, $description, $imageURL, $event = null) {
        if (empty($descriptor) || empty($description)) throw new Exception(__METHOD__.': Invalid parameters');

        // Add event to gamification events array
        $this->badges[$internalDescriptor] = array(
            'id'            => ++$this->badgesCounter,
            'internal'      => $internalDescriptor,
            'descriptor'    => $descriptor,
            'description'   => $description,
            'imageurl'      => $imageURL,
            'event'         => $event
        );

        return $this;
    }

    /**
     *
     * Add event to gamification engine
     *
     * @param $event gengamificationEvent
     *
     * @return bool
     * @throws Exception
     *
     */
    public function addEvent($event) {
        if (!is_null($event->getBadge())) {
            if (!$this->badgeExists($event->getBadge())) throw new Exception(__METHOD__.': Invalid badge');
        }

        $i = $event->getDescriptor();

        // Add new event/trigger to array events
        if (isset($this->events[$i])) {
            if ($this->events[$i]['allowrepetitions'] != $event->allowRepetitions()) throw new Exception(__METHOD__.': Allow repetitions not match for the event');
        } else {
            $this->events[$i] = array(
                'id'                    => ++$this->eventsCounter,
                'allowrepetitions'      => $event->allowRepetitions(),
                'triggers'              => array()
            );
        }

        // Add trigger to triggers for this event
        $this->events[$i]['triggers'][] = $event;

        return $this;
    }

    private function addEventToQueue($descriptor) {
        $this->eventsQueue[] = $descriptor;
    }

    // Add badge to gamification engine
    public function addLevel($pointsThreshold, $descriptor, $eventDescriptor = null) {
        $descriptor = trim($descriptor);

        if (!is_numeric($pointsThreshold) || empty($descriptor)) throw new Exception(__METHOD__.': Invalid parameters');

        // Add event to gamification events array
        $this->levels[] = array(
            'id'            => ++$this->levelsCounter,
            'threshold'     => $pointsThreshold,
            'descriptor'    => $descriptor,
            'event'         => $eventDescriptor
        );

        return $this;
    }

    // Save alert for received badges
    private function alertBadge($id) {
        // Save alert
        $this->dao->saveBadgeAlert($this->getUserId(), $id);

        return true;
    }

    // Save alert for level upgrade
    private function alertLevel($id) {
        // Save alert
        $this->dao->saveLevelAlert($this->getUserId(), $id);

        return true;
    }

    private function badgeExists($descriptor) {
        if (array_key_exists($descriptor, $this->badges)) return true;
        else return false;
    }

    public function getAlerts($resetAlerts = false) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        return $this->dao->getUserAlerts($this->getUserId(), $resetAlerts);
    }

    // Get badge info from id
    public function getBadge($id) {
        $r = array();
        foreach ($this->badges as $b) {
            if ($id == $b['id']) $r = $b;
        }

        return $r;
    }

    // Get badge id
    private function getBadgeId($descriptor) {
        if (empty($this->badges[$descriptor])) throw new Exception(__METHOD__.': Invalid badge');

        return $this->badges[$descriptor]['id'];
    }

    // Get event id
    private function getEventId($descriptor) {
        if (empty($this->events[$descriptor])) throw new Exception(__METHOD__.': Invalid event');

        return $this->events[$descriptor]['id'];
    }

    // Get event id
    private function getLevel($id) {
        $r = array();
        foreach ($this->levels as $l) {
            if ($id == $l['id']) $r = $l;
        }

        return $r;
    }

    // Get a list of user badges
    public function getBadges() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        $r = array();
        foreach ($this->dao->getUserBadges($this->getUserId()) as $x) {
            $b = $this->getBadge($x['id_badge']);

            $r[] = array(
                'id'            => $x['id_badge'],
                'counter'       => $x['badgescounter'],
                'imageurl'      => $b['imageurl'],
                'internal'      => $b['internal'],
                'descriptor'    => $b['descriptor'],
                'description'   => $b['description'],
            );
        }

        return $r;
    }

    // Get user scores
    public function getUserScores() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        return $this->dao->getUserScores($this->getUserId());
    }

    // Get user id
    public function getUserId() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        return $this->userId;
    }

    // Execute gamification event
    public function executeEvent($descriptor, $additional = null) {
        if (!$this->letsGoParty) return false;

        if (!isset($this->events[$descriptor])) throw new Exception(__METHOD__.': Invalid event');

        // Get id of event in $this->events array
        $currentEventId = $this->getEventId($descriptor);

        // Get event counter and current points for this event
        $userEvent = $this->dao->getUserEvent($this->getUserId(), $currentEventId);

        // Counters initialization (max repetitions and current points for this event)
        if (empty($userEvent)) {
            $eventCounter = 0;
            $eventPoints = 0;
        } else {
            $eventCounter = $userEvent['eventcounter'];
            $eventPoints = $userEvent['pointscounter'];
        }

        // Is this event allowed to repeat?
        $executeEvent = true;
        if (!$this->events[$descriptor]['allowrepetitions'] && $eventCounter > 0) $executeEvent = false;

        // Is the event allow to execute?
        if ($executeEvent) {
            // Update counter for this event
            $this->dao->increaseEventCounter($this->getUserId(), $currentEventId);

            // Increase internal counter for this user/event
            $eventCounter++;

            // Search triggers counter
            /** @var $e gengamificationEvent */
            foreach ($this->events[$descriptor]['triggers'] as $e) {
                $eachOk = true;

                // Execute each function
                $callback = $e->getEachCallback();
                if (is_callable($callback)) $eachOk = $callback($additional);

                // if each iterative function returns false, event cancels execution.
                if ($eachOk) {
                    // Check if counter match trigger or required repetitions is null
                    if ((is_null($e->getRequiredRepetitions())) || ($e->getRequiredRepetitions() == $eventCounter)) {
                        $triggerOk = true;

                        // Execute trigger function
                        $callback = $e->getEventCallback();
                        if (is_callable($callback)) $triggerOk = $callback($additional);

                        // if event trigger function returns false, event cancels execution.
                        if ($triggerOk) {
                            // Grant points
                            if (!is_null($e->getPoints())) {
                                $grantPoints = true;

                                // Check max points for this event
                                if (!is_null($e->getMaxpoints())) {
                                    // If event points OLD counter greater than maxpoints, don't save anything
                                    if ($eventPoints >= $e->getMaxpoints()) $grantPoints = false;
                                }

                                // If points not reaches max event points, it saves them.
                                if ($grantPoints) {
                                    // Grant points to user
                                    $this->grantPoints($e->getPoints());

                                    // Update points for this event
                                    $this->dao->increaseEventPoints($this->getUserId(), $currentEventId, $e->getPoints());
                                }
                            }

                            // Grant badges
                            if (!is_null($e->getBadge())) $this->grantBadge($e->getBadge());
                        }
                    }
                }
            }
        }

        // Process events queue
        $this->processEventsQueue($additional);

        return true;
    }

    // Grant badge to user
    public function grantBadge($descriptor) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        // Grant badge to user
        $this->dao->grantBadgeToUser($this->getUserId(), $this->getBadgeId($descriptor));

        // Gamification alert
        $this->alertBadge($this->getBadgeId($descriptor));

        // Add event to queue when the user reach this level
        if (!is_null($this->badges[$descriptor]['event'])) $this->addEventToQueue($this->badges[$descriptor]['event']);

        return true;
    }

    // Grant level to user
    public function grantLevel($levelId) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        // Grant level
        $this->dao->grantLevelToUser($this->getUserId(), $levelId);

        // Gamification alert
        $this->alertLevel($levelId);

        // Add event to queue when the user reach this level
        $l = $this->getLevel($levelId);
        if (!is_null($l['event'])) $this->addEventToQueue($l['event']);

        return true;
    }

    // Grant points to user
    public function grantPoints($points) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        // Get user level/points
        $scores = $this->dao->getUserScores($this->getUserId());

        if (empty($scores)) {
            $userCurrentLevel = 1; // Initial level: 1
            $userPoints = 0;
        } else {
            $userCurrentLevel = $scores['id_level'];
            $userPoints = $scores['points'];
        }

        // Add points to user counter
        $this->dao->grantPointsToUser($this->getUserId(), $points);

        // Updated points for levels comparison
        $userPoints += $points;

        // Check new level
        foreach ($this->levels as $l) {
            // Check levels higher than user level
            if ($l['id'] > $userCurrentLevel) {
                // Check if user reaches next level

                if ($userPoints >= $l['threshold']) $this->grantLevel($l['id']);
            }
        }
    }

    // Execute next event of the events queue
    private function processEventsQueue($data = null) {
        if (!empty($this->eventsQueue)) {
            $eventDescriptor = array_shift($this->eventsQueue);

            $this->executeEvent($eventDescriptor, $data);
        }
    }

    // Set Data Access Object
    public function setDAO($dao) {
        $this->dao = $dao;
    }

    // Set user id
    public function setUserId($userId) {
        if (!is_numeric($userId)) throw new Exception(__METHOD__.': Invalid parameters');

        $this->userId = $userId;

        return true;
    }
}