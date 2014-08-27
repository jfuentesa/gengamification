<?php

error_reporting(E_ALL^E_NOTICE);
ini_set('log_errors', 'On');
ini_set('display_errors', 'On');

// Config
define('BD_SERVER', 'localhost');
define('BD_NAME', 'databasename');
define('BD_USER', 'root');
define('BD_PASSWD', '');

// Generic gamification required
require_once('class.gengamification.php');

// Data Access Object for gengamification persistent data layer
class gengamificationDAO implements gengamificationDAOint {
    public $conn = null;

    /**
     *
     * PDO methods
     *
     */

    public function getConnection() {
        if (!$this->conn) {
            try {
                $this->conn = new PDO('mysql:host='.BD_SERVER.';dbname='.BD_NAME.';charset=utf8', BD_USER, BD_PASSWD);
                $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            } catch(PDOException $e) {
                exit($e->getMessage());
            }
        }

        return $this->conn;
    }

    public function execute($sql, $params = array()) {
        $conn = $this->getConnection();

        /** @var PDOStatement $stmt */
        $stmt = $conn->prepare($sql);

        $stmt->execute($params);

        return $stmt;
    }

    public function query($sql, $params = array()) {
        $conn = $this->getConnection();

        if (empty($params)) {
            /** @var $stmt PDOStatement */
            $stmt = $conn->query($sql);
        } else $stmt = $this->execute($sql, $params);

        return $stmt;
    }

    public function toArray($query, $params = false)
    {
        $a = array();
        $q = $this->query($query, $params);

        while ($r = $q->fetch(PDO::FETCH_ASSOC)) $a[] = $r;

        return $a;
    }

    /**
     *
     * Interface methods
     *
     */

    public function getUserAlerts($userId, $resetAlerts = false) {
        $sql = 'SELECT id_user, id_badge, id_level FROM t_gengamification_alerts WHERE id_user = :uid';
        $params = array(
            ':uid'  => $userId
        );
        $r = $this->toArray($sql, $params);

        if ($resetAlerts) {
            $sql = 'DELETE FROM t_gengamification_alerts WHERE id_user = :uid';
            $params = array(
                ':uid'  => $userId
            );
            $this->execute($sql, $params);
        }

        return $r;
    }

    public function getUserBadges($userId) {
        $sql = 'SELECT id_badge, badgescounter FROM t_gengamification_badges WHERE id_user = :uid';
        $params = array(
            ':uid'  => $userId
        );

        return $this->toArray($sql, $params);
    }

    public function getUserEvents($userId) {
        $sql = 'SELECT id_user, id_event, eventcounter FROM t_gengamification_events WHERE id_user = :uid';
        $params = array(
            ':uid'  => $userId
        );
        return $this->toArray($sql, $params);
    }

    public function getUserEvent($userId, $eventId) {
        $sql = 'SELECT id_user, id_event, eventcounter, pointscounter FROM t_gengamification_events WHERE id_user = :uid AND id_event = :eid LIMIT 1';
        $params = array(
            ':uid'  => $userId,
            ':eid'  => $eventId
        );
        $r = $this->toArray($sql, $params);
        return $r[0];
    }

    public function getUserScores($userId) {
        $sql = 'SELECT id_user, points, id_level FROM t_gengamification_scores WHERE id_user = :uid LIMIT 1';
        $params = array(
            ':uid'  => $userId
        );
        $r = $this->toArray($sql, $params);
        return $r[0];
    }

    public function grantBadgeToUser($userId, $badgeId) {
        $sql = 'INSERT INTO t_gengamification_badges (id_user, id_badge, badgescounter, grantdate) VALUES (:uid, :bid, 1, UTC_TIMESTAMP()) ON DUPLICATE KEY UPDATE badgescounter = badgescounter + 1';
        $params = array(
            ':uid'  => $userId,
            ':bid'  => $badgeId
        );
        $this->execute($sql, $params);
        return true;
    }

    public function grantLevelToUser($userId, $levelId) {
        $sql = 'UPDATE t_gengamification_scores SET id_level = :lid WHERE id_user = :uid LIMIT 1';
        $params = array(
            ':uid'  => $userId,
            ':lid'  => $levelId
        );
        $this->execute($sql, $params);
        return true;
    }

    public function grantPointsToUser($userId, $points) {
        $sql = 'INSERT INTO t_gengamification_scores (id_user, points, id_level) VALUES (:uid, :p, 0) ON DUPLICATE KEY UPDATE  points = points + :p';
        $params = array(
            ':uid'  => $userId,
            ':p'    => $points
        );
        $this->execute($sql, $params);
        return true;
    }

    public function saveBadgeAlert($userId, $badgeId) {
        $sql = 'INSERT INTO t_gengamification_alerts (id_user, id_badge, id_level) VALUES (:uid, :bid, NULL)';
        $params = array(
            ':uid'  => $userId,
            ':bid'  => $badgeId
        );
        $this->execute($sql, $params);
        return true;
    }

    public function saveLevelAlert($userId, $levelId) {
        $sql = 'INSERT INTO t_gengamification_alerts (id_user, id_badge, id_level) VALUES (:uid, NULL, :lid)';
        $params = array(
            ':uid'  => $userId,
            ':lid'  => $levelId
        );
        $this->execute($sql, $params);
        return true;
    }

    public function increaseEventCounter($userId, $eventId) {
        $sql = 'INSERT INTO t_gengamification_events (id_user, id_event, eventcounter) VALUES (:uid, :eid, 1) ON DUPLICATE KEY UPDATE eventcounter = eventcounter + 1';
        $params = array(
            ':uid'  => $userId,
            ':eid'  => $eventId
        );

        $this->execute($sql, $params);

        return true;
    }

    public function increaseEventPoints($userId, $eventId, $points) {
        $sql = 'UPDATE t_gengamification_events SET pointscounter = pointscounter + :c WHERE id_user = :uid AND id_event = :eid LIMIT 1';
        $params = array(
            ':c'    => $points,
            ':uid'  => $userId,
            ':eid'  => $eventId
        );
        $this->execute($sql, $params);
        return true;
    }
}

// Creation of gamification engine
$g = new gengamification(new gengamificationDAO());

// Badges definitions
$g->addBadge('the_one', 'The One', 'You have logged in 10 times (50 points)', 'img/badge1.png')
    ->addBadge('king_of_chat', 'King of the Chat', 'You posted 10 messages to the chat (500 points)', 'img/badge2.png')
    ->addBadge('spreader', 'Blog Spreader', 'You wrote 5 post to your blog (1000 points)', 'img/badge3.png')
    ->addBadge('five_stars_badge', 'Five Stars', 'You get the Five Stars level', 'img/badge4.png');

// Levels definitions
$g->addLevel(0, 'No Star')
    ->addLevel(53, 'One star')
    ->addLevel(500, 'Three stars')
    ->addLevel(1000, 'Five stars', 'grant_five_stars_badge'); // Execute event: grant_five_stars_badge

/**
 *
 * Events definitions
 *
 */

// You have logged in 10 times (50 points)
$e = new gengamificationEvent();
$e->setDescriptor('login')
    ->setPointsGranted(50)
    ->setBadgeGranted('the_one')
    ->setRequiredRepetitions(10)
    ->setAllowRepetitions(true);

$g->addEvent($e);

// You posted 20 messages to the chat (500 points)
$e = new gengamificationEvent();
$e->setDescriptor('post_to_chat')
    ->setPointsGranted(500)
    ->setBadgeGranted('king_of_chat')
    ->setRequiredRepetitions(10)
    ->setAllowRepetitions(true);

$g->addEvent($e);

// You wrote 5 post to your blog (1000 points)
$e = new gengamificationEvent();
$e->setDescriptor('post_to_blog')
    ->setPointsGranted(1000)
    ->setBadgeGranted('spreader')
    ->setRequiredRepetitions(5)
    ->setAllowRepetitions(true);

$g->addEvent($e);

// You get the Five Stars level
$e = new gengamificationEvent();
$e->setDescriptor('grant_five_stars_badge')
    ->setBadgeGranted('five_stars_badge');

$g->addEvent($e);

/**
 *
 * USAGE:
 *
 */

// User who receives gamification events
$g->setUserId(1);

// Execute gamification event for selected user
// $g->executeEvent('login', array('additional data sent to callback functions'));
// $g->executeEvent('post_to_chat', array('additional data sent to callback functions'));
// $g->executeEvent('post_to_blog', array('additional data sent to callback functions'));

// Grant defined badges
// $g->grantBadge('predefined_badge');

// Grant points
//$g->grantPoints(1);

// Get user alerts
//print_r($g->getAlerts());

// Get user badges
// print_r($g->getBadges());
