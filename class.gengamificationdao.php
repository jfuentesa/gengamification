<?php

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

        if (!empty($r) && $resetAlerts) {
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

    public function getUserLog($userId) {
        $sql = 'SELECT id_user, id_event, eventdate, points, id_badge, id_level FROM t_gengamification_log WHERE id_user = :uid ORDER BY eventdate DESC';
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
        $sql = 'INSERT INTO t_gengamification_scores (id_user, points, id_level) VALUES (:uid, :p, :firstlevel) ON DUPLICATE KEY UPDATE  points = points + :p';
        $params = array(
            ':uid'          => $userId,
            ':p'            => $points,
            ':firstlevel'   => 1
        );
        $this->execute($sql, $params);
        return true;
    }

    public function logUserEvent($userId, $eventId, $points = null, $badgeId = null, $levelId = null) {
        $sql = 'INSERT INTO t_gengamification_log (id_user, id_event, eventdate, points, id_badge, id_level) VALUES (:uid, :eid, UTC_TIMESTAMP(), :p, :bid, :lid)';
        $params = array(
            ':uid'  => $userId,
            ':eid'  => $userId,
            ':p'    => $points,
            ':bid'  => $badgeId,
            ':lid'  => $levelId,
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
}
