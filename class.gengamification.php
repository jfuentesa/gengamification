<?php

/**
 *
 * GenGamification - A generic gamification PHP framework
 *
 * @author Javier Fuentes <javier.fuentes@redalumnos.com>
 * @license http://opensource.org/licenses/MIT MIT License
 * @version 1.1
 *
 * CHANGES:
 *
 * 2014-10-21:
 *
 * Added getLastLevelId()
 * Added printBadges()
 * Added printProgressBar()
 * Added getUserEvents()
 * Added getEventById()
 * Added private method getEventById() to get event by id
 * Fixed repeated events when required repetitions and repetitions are allowed
 *
 * 2014-09-02:
 *
 * Now addEvent and addBadgets accepts event IDs.
 * Added setTestUserId() for testing purposes.
 *
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
    public function logUserEvent($userId, $eventId, $points = null, $badgeId = null, $levelId = null);
    public function getUserLog($userId);
}

class gengamificationEvent {
    private $id = null;

    private $repetitions = null;        /* Trigger counter (null = triggers every execution. $allowrepetitions must be true, otherwise triggers once) */
    private $allowrepetitions = false;  /* Allows repetitions (Default: NO) */

    private $badge = null;              /* Badge granted when triggers */
    private $descriptor = null;         /* Event descriptor */
    private $description = null;         /* Event description */
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

    public function getDescription() {
        return $this->description;
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

    public function getId()
    {
        return $this->id;
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

    public function setDescription($str) {
        $str = trim($str);
        if (empty($str)) throw new Exception(__METHOD__.': Invalid description');

        $this->description = $str;

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

    public function setId($f) {
        if (!is_numeric($f)) throw new Exception(__METHOD__.': Invalid id');

        $this->id = $f;

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
    /** @var bool $letsGoParty false = stops gamification execution */
    private $letsGoParty = null;

    /** @var int $testUserId */
    private $testUserId = null;

    /** @var array $events Events definitions */
    private $events = array();

    /** @var array $badges Badges definitions */
    private $badges = array();

    /** @var array $levels Levels definitions */
    private $levels = array();

    // User id
    private $userId = null;

    // Definitions counters
    private $levelsCounter = 0;

    // Events queue
    private $eventsQueue = array();

    // Data Access Object (DAO)
    /** @var gengamificationDAO $dao */
    private $dao = null;

    public function __construct($enabled = true) {
        $this->letsGoParty = $enabled;
    }

    /**
     *
     * Add badge to gamification engine
     *
     * @param      $id
     * @param      $internalDescriptor
     * @param      $descriptor
     * @param      $description
     * @param      $imageURL
     * @param null $event
     *
     * @return $this
     * @throws Exception
     *
     */
    public function addBadge($id, $internalDescriptor, $descriptor, $description, $imageURL, $event = null) {
        if (empty($descriptor) || empty($description)) throw new Exception(__METHOD__.': Invalid parameters');

        // Add event to gamification events array
        $this->badges[$id] = array(
            'id'            => $id,
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
            // If event exists just add new trigger

            // Checking allowrepetitions matching with previously created event
            if ($this->events[$i]['allowrepetitions'] != $event->allowRepetitions()) throw new Exception(__METHOD__.': Allow repetitions does not match for the event');
            // Checking id (not required) for same event descriptor
            if (!is_null($event->getId())) {
                if ($this->events[$i]['id'] != $event->getId()) throw new Exception(__METHOD__.': Id does not match for the event');
            }
        } else {
            // Check event id
            if (!is_numeric($event->getId())) throw new Exception(__METHOD__.': Invalid event id');

            $this->events[$i] = array(
                'id'                    => $event->getId(),
                'allowrepetitions'      => $event->allowRepetitions(),
                'triggers'              => array()
            );
        }

        // Add trigger to triggers for this event
        $this->events[$i]['triggers'][] = $event;

        return $this;
    }

    /**
     *
     * Add event to pending events queue
     *
     * @param $descriptor
     *
     */
    private function addEventToQueue($descriptor) {
        $this->eventsQueue[] = $descriptor;
    }

    /**
     *
     * Add badge to gamification engine
     *
     * @param      $pointsThreshold
     * @param      $descriptor
     * @param null $eventDescriptor
     *
     * @return $this
     * @throws Exception
     *
     */
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

    /**
     *
     * Save alert for received badges
     *
     * @param $id
     *
     * @return bool
     *
     */
    private function alertBadge($id) {
        // Save alert
        $this->dao->saveBadgeAlert($this->getUserId(), $id);

        return true;
    }

    /**
     *
     * Save alert for level upgrade
     *
     * @param $id
     *
     * @return bool
     *
     */
    private function alertLevel($id) {
        // Save alert
        $this->dao->saveLevelAlert($this->getUserId(), $id);

        return true;
    }

    private function badgeExists($descriptor) {
        foreach ($this->badges as $b) {
            if ($descriptor == $b['internal']) return true;
        }

        return false;
    }

    public function getUserAlerts($resetAlerts = false) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        return $this->dao->getUserAlerts($this->getUserId(), $resetAlerts);
    }

    /**
     *
     * Get user events progress
     *
     * @return array
     * @throws Exception
     *
     *
     *
     */
    public function getUserEvents() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        $r = array();

        foreach ($this->dao->getUserEvents($this->getUserId()) as $x) {
            $_ = $this->getEventById($x['id_event']);

            $triggers = array();
            /** @var $trigger gengamificationEvent */
            foreach ($_['triggers'] as $trigger) {
                $triggers[] = array(
                    'reached'       => ($x['eventcounter'] >= $trigger->getRequiredRepetitions() ? true : false),
                    'description'   => $trigger->getDescription(),
                );
            }
            $r[] = array(
                'id'            => $x['id_event'],
                'counter'       => $x['eventcounter'],
                'triggers'      => $triggers
            );
        }

        return $r;
    }

    /**
     *
     * Get badge info from id
     *
     * @param $id
     *
     * @return mixed
     * @throws Exception
     *
     */
    public function getBadge($id) {
        if (!isset($this->badges[$id])) throw new Exception(__METHOD__.': Invalid badge');

        return $this->badges[$id];
    }

    /**
     *
     * Get badge id
     *
     * @param $descriptor
     *
     * @return null
     * @throws Exception
     *
     */
    private function getBadgeId($descriptor) {
        $r = null;

        foreach ($this->badges as $b) {
            if ($descriptor == $b['internal']) $r = $b['id'];
        }

        if (is_null($r)) throw new Exception(__METHOD__.': Invalid badge id');

        return $r;
    }

    private function getEventById($id) {
        $r = null;

        foreach ($this->events as $e) {
            if ($id == $e['id']) $r = $e;
        }

        if (is_null($r)) throw new Exception(__METHOD__.': Invalid event id');

        return $r;
    }

    /**
     *
     * Get event id
     *
     * @param $descriptor
     *
     * @return mixed
     * @throws Exception
     *
     */
    private function getEventId($descriptor) {
        if (empty($this->events[$descriptor])) throw new Exception(__METHOD__.': Invalid event');

        return $this->events[$descriptor]['id'];
    }

    /**
     *
     * Get level
     *
     * @param $id
     *
     * @return array
     *
     */
    public function getLevel($id) {
        $r = array();
        foreach ($this->levels as $l) {
            if ($id == $l['id']) $r = $l;
        }

        return $r;
    }

    /**
     *
     * Get last level id
     *
     * @param $id
     *
     * @return array
     *
     */
    public function getLastLevelId() {
        return (string)count($this->levels);
    }

    /**
     *
     * Get user log
     *
     * @return array
     * @throws Exception
     *
     */
    public function getUserLog() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        return $this->dao->getUserLog($this->getUserId());
    }

    /**
     *
     * Get a list of user badges
     *
     * @return array
     * @throws Exception
     *
     */
    public function getUserBadges() {
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

    /**
     *
     * Get user scores
     *
     * @return mixed
     * @throws Exception
     *
     */
    public function getUserScores() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        $r = $this->dao->getUserScores($this->getUserId());

        // Include additional progress data
        if (empty($r)) {
            $r['id_user'] = $this->getUserId();
            $r['points'] = '0';
            $r['id_level'] = 1;
        }

        // Get user current level
        $level = $this->getLevel($r['id_level']);
        $nextLevel = $this->getLevel($r['id_level'] + 1);

        $r['progress'] = 100;
        $r['levelname'] = $level['descriptor'];

        // It isn't the last level
        if (!empty($nextLevel)) {
            // Progress percentage to reach next level
            $r['progress'] = round((($r['points'] - $level['threshold']) * 100) / ($nextLevel['threshold'] - $level['threshold']));
        }

        return $r;
    }

    /**
     *
     * Get user id
     *
     * @return null
     * @throws Exception
     *
     */
    public function getUserId() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        return $this->userId;
    }

    /**
     *
     * Execute gamification event
     *
     * @param      $descriptor
     * @param null $additional
     *
     * @return bool
     * @throws Exception
     *
     */
    public function executeEvent($descriptor, $additional = null) {
        // Is the service enabled?
        if (!$this->letsGoParty) return false;

        // Filter to user test
        if (!is_null($this->testUserId)) {
            if ($this->testUserId != $this->userId) return false;
        }

        // Check invalid event
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

        // Event doesn't executes if $eventCounter > 0 AND 'allowrepetitions' is false (events executed once are eventcounter = 1)
        $executeEvent = true;
        if (($eventCounter > 0) && !$this->events[$descriptor]['allowrepetitions']) $executeEvent = false;

        // Is the event allow to execute?
        if ($executeEvent) {
            // Increase internal counter for this user/event
            $eventCounter++;

            // Check if any trigger in the event is higher than current event counter for updating database
            $updateCounter = false;
            if ($eventCounter == 1) {
                // First execution time counter for event is always updated
                $updateCounter = true;
            } else {
                // COUNTERS WON'T BE UPDATED IF NOT REQUIRED FOR CONTROL EVENT REPETITIONS

                // If any trigger for this event require more repetitions than eventcounter, eventcounter is updated (increased +1)
                /** @var $e gengamificationEvent */
                foreach ($this->events[$descriptor]['triggers'] as $e) {
                    if (!is_null($e->getRequiredRepetitions())) {
                        if ($e->getRequiredRepetitions() >= $eventCounter) $updateCounter = true;
                    }
                }
            }

            // Update counter for this event
            if ($updateCounter) $this->dao->increaseEventCounter($this->getUserId(), $currentEventId);

            // Search triggers counter
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
                                    // Grant points from current event to user scores
                                    $this->grantPoints($e->getPoints(), $this->getEventId($descriptor));

                                    // Update points for this event
                                    $this->dao->increaseEventPoints($this->getUserId(), $currentEventId, $e->getPoints());
                                }
                            }

                            // Grant badges
                            if (!is_null($e->getBadge())) $this->grantBadge($e->getBadge(), $this->getEventId($descriptor));
                        }
                    }
                }
            }
        }

        // Process events queue
        $this->processEventsQueue($additional);

        return true;
    }

    /**
     *
     * Grant badge to user
     *
     * @param      $descriptor
     * @param null $eventId
     *
     * @return bool
     * @throws Exception
     *
     */
    public function grantBadge($descriptor, $eventId = null) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        $badgeId = $this->getBadgeId($descriptor);

        // Grant badge to user
        $this->dao->grantBadgeToUser($this->getUserId(), $badgeId);

        // Log event
        $this->dao->logUserEvent($this->getUserId(), $eventId, null, $badgeId);

        // Gamification alert
        $this->alertBadge($badgeId);

        // Add event to queue when the user reach this level
        if (!is_null($this->badges[$badgeId]['event'])) $this->addEventToQueue($this->badges[$badgeId]['event']);

        return true;
    }

    /**
     *
     * Grant level to user
     *
     * @param      $levelId
     * @param null $eventId
     *
     * @return bool
     * @throws Exception
     *
     */
    private function grantLevel($levelId, $eventId = null) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        // Grant level
        $this->dao->grantLevelToUser($this->getUserId(), $levelId);

        // Log event
        $this->dao->logUserEvent($this->getUserId(), $eventId, null, null, $levelId);

        // Gamification alert
        $this->alertLevel($levelId);

        // Add event to queue when the user reach this level
        $l = $this->getLevel($levelId);
        if (!is_null($l['event'])) $this->addEventToQueue($l['event']);

        return true;
    }

    /**
     *
     * Grant points to user
     *
     * @param      $points
     * @param null $eventId
     *
     * @throws Exception
     *
     */
    public function grantPoints($points, $eventId = null) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        // Get user level/points
        $scores = $this->dao->getUserScores($this->getUserId());

        if (empty($scores)) {
            $userCurrentLevel = 1 ; // Initial level: 1 (DON'T CHANGE IT)
            $userPoints = 0;
        } else {
            $userCurrentLevel = $scores['id_level'];
            $userPoints = $scores['points'];
        }

        // Add points to user counter
        $this->dao->grantPointsToUser($this->getUserId(), $points);

        // Log event
        $this->dao->logUserEvent($this->getUserId(), $eventId, $points);

        // Updated points for levels comparison
        $userPoints += $points;

        // Check new level
        foreach ($this->levels as $l) {
            // Check levels higher than user level
            if ($l['id'] > $userCurrentLevel) {
                // Check if user reaches next level
                if ($userPoints >= $l['threshold']) $this->grantLevel($l['id'], $eventId);
            }
        }
    }

    public function pointsToNextLevel() {
        $scores = $this->getUserScores();

        $r = null;

        // Check last level
        if ((int)$scores['id_level'] < (int)$this->getLastLevelId()) {
            $nextLevel = $this->getLevel($scores['id_level'] + 1);
            $r = $nextLevel['threshold'] - $scores['points'];
        }

        return $r;
    }

    public function prepareAlertsSection($return = false) {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        $r = '';

        $alerts = $this->getUserAlerts(true);

        if (!empty($alerts)) {
            $levelId = 0;
            $levelLabel = '';
            $badges = array();

            foreach ($alerts as $a) {
                // Get alert for new level
                if (!is_null($a['id_level'])) {
                    if ($a['id_level'] > $levelId) {
                        $level = $this->getLevel($a['id_level']);
                        $levelId = $a['id_level'];
                        $levelLabel = $level['descriptor'];
                    }
                }

                // Get alert for new badge
                if (!is_null($a['id_badge'])) {
                    $badges[] = $a['id_badge'];
                }
            }

            $gamifCongratsBodyLevel = '';
            if ($levelId) {
                $gamifCongratsBodyLevel = <<<LEVEL
<p>Has alcanzado el nivel:</p>
<p id="gengamif-level-number">$levelId</p>
<p id="gengamif-level-label">$levelLabel</p>
LEVEL;
            }

            $gamifCongratsBodyBadges = '';
            if (!empty($badges)) {
                foreach ($badges as $b) {
                    $badge = $this->getBadge($b);

                    $gamifCongratsBodyBadges .= '<div class="gengamif-badge"><img src="'.$badge['imageurl'].'" title="'.htmlspecialchars($badge['descriptor']).' - '.htmlspecialchars($badge['description']).'"><br />'.htmlspecialchars($badge['descriptor']).'<br />'.htmlspecialchars($badge['description']).'</div>';
                }

                $gamifCongratsBodyBadges = '<p>Has obtenido las siguientes medallas:</p><div id="gengamif-badgeslist">'.$gamifCongratsBodyBadges.'</div>';
            }

            $pointsLeft = $this->pointsToNextLevel();
            $pointsLeftStr = '';
            if (!is_null($pointsLeft)) $pointsLeftStr = '<p>Te faltan '.$pointsLeft.' puntos para alcanzar el próximo nivel, ¡ánimo!</p>';

            $r = <<<LEVEL
<section id="gengamif-alerts">
<section id="gengamif-alerts-inner">
<h3>¡Enhorabuena!</h3>
$gamifCongratsBodyLevel
$gamifCongratsBodyBadges
<section id="gengamif-alerts-footer">
$pointsLeftStr
</section>
</section>
</section>
LEVEL;

        }

        if ($return) return $r;

        echo $r;
        return true;
    }

    public function printBadges() {
        if (is_null($this->userId)) throw new Exception(__METHOD__.': Invalid user id');

        $r = '';

        foreach ($this->getUserBadges() as $m) {
            $r .= '<img src="'.$m['imageurl'].'" title="'.htmlspecialchars($m['descriptor']).' - '.htmlspecialchars($m['description']).'">';
        }

        if (!empty($r)) $r = '<section class="gengamif-badges-images">'.$r.'</section>';

        return $r;
    }

    public function printProgressBar($return = false) {
        if (is_null($this->userId)) {
            $level = $this->getLevel(1);
            $_ = array(
                'points'    => 0,
                'progress'  => 0,
                'id_level'  => 1,
                'levelname' => $level['descriptor']
            );
        } else {
            $_ = $this->getUserScores();
        }

        $puntos = $_['points'].' '.T_('puntos');
        $progreso = $_['progress'];
        $nivel = T_('Nivel: ').' '.$_['id_level'];
        $nombreNivel = $_['levelname'];

        $r = <<<EOF
<div class="gamif-info" style="margin: 10px 0;">
<div class="gamif-progressbar-points" style="padding-left: 5px;font-size: 13px; text-align: center;">$puntos</div>
<div class="gamif-progressbar" style="border: 1px solid #ddd;height: 10px;overflow: hidden;">
<div class="gamif-progressbar-fill" style="width: $progreso%;height: 10px;background-color: #26AF61;"></div>
</div>
<div class="gamif-progressbar-level" style="font-size: 13px; text-align: center;width: 100%;white-space:nowrap;overflow: hidden; text-overflow: ellipsis"><a title="$nivel" href="config.php?m=progress">$nombreNivel</a></div>
</div>
EOF;

        if ($return) return $r;

        echo $r;
        return true;
    }

    /**
     * @param null $data
     *
     * Execute next event of the events queue
     *
     */
    private function processEventsQueue($data = null) {
        if (!empty($this->eventsQueue)) {
            $eventDescriptor = array_shift($this->eventsQueue);

            $this->executeEvent($eventDescriptor, $data);
        }
    }

    /**
     *
     * Set Data Access Object
     *
     * @param $dao
     *
     */
    public function setDAO($dao) {
        $this->dao = $dao;
    }

    /**
     *
     * Enable/disable executeEvent() globally
     *
     * @param bool $enabled
     *
     */
    public function setEnabled($enabled = true) {
        $this->letsGoParty = $enabled;
    }

    /**
     *
     * Set user id
     *
     * @param $userId
     *
     * @return bool
     * @throws Exception
     *
     */
    public function setUserId($userId) {
        if (!is_numeric($userId)) throw new Exception(__METHOD__.': Invalid parameters');

        $this->userId = $userId;

        return true;
    }

    /**
     *
     * Set user id
     *
     * @param $userId
     *
     * @return bool
     * @throws Exception
     *
     */
    public function setTestUserId($userId) {
        if (!is_numeric($userId)) throw new Exception(__METHOD__.': Invalid parameters');

        $this->testUserId = $userId;

        return true;
    }
}