<?php

error_reporting(E_ALL^E_NOTICE);
ini_set('log_errors', 'Off');
ini_set('display_errors', 'On');

// Config
define('BD_SERVER', 'localhost');
define('BD_NAME', 'databasename');
define('BD_USER', 'username');
define('BD_PASSWD', 'password');

// Generic gamification required
require_once('class.gengamification.php');
require_once('class.gengamificationdao.php');

// Creation of gamification engine
$g = new gengamification();

$g->setDAO(new gengamificationDAO());

// Badges definitions
$g->addBadge(1, 'the_one', 'The One', 'You have logged in 10 times (50 points)', 'img/badge1.png')
    ->addBadge(2, 'king_of_chat', 'King of the Chat', 'You posted 10 messages to the chat (500 points)', 'img/badge2.png')
    ->addBadge(3, 'spreader', 'Blog Spreader', 'You wrote 5 post to your blog (1000 points)', 'img/badge3.png')
    ->addBadge(4, 'five_stars_badge', 'Five Stars', 'You get the Five Stars level', 'img/badge4.png');

// Levels definitions
$g->addLevel(0, 'No Star')
    ->addLevel(50, 'One star')
    ->addLevel(500, 'Three stars')
    ->addLevel(1000, 'Five stars', 'grant_five_stars_badge'); // Execute event: grant_five_stars_badge

/**
 *
 * Events definitions
 *
 */

// You have logged in 10 times (50 points)
$e = new gengamificationEvent();
$e->setId(1)
    ->setDescriptor('login')
    ->setPointsGranted(50)
    ->setBadgeGranted('the_one')
    ->setRequiredRepetitions(10)
    ->setAllowRepetitions(true)
;

$g->addEvent($e);

// You posted 20 messages to the chat (500 points)
$e = new gengamificationEvent();
$e->setId(2)
    ->setDescriptor('post_to_chat')
    ->setPointsGranted(500)
    ->setBadgeGranted('king_of_chat')
    ->setRequiredRepetitions(10)
    ->setAllowRepetitions(true)
;

$g->addEvent($e);

// You wrote 5 post to your blog (1000 points)
$e = new gengamificationEvent();
$e->setId(3)
    ->setDescriptor('post_to_blog')
    ->setPointsGranted(1000)
    ->setBadgeGranted('spreader')
    ->setRequiredRepetitions(5)
    ->setAllowRepetitions(true)
;

$g->addEvent($e);

// You get the Five Stars level
$e = new gengamificationEvent();
$e->setId(4)
    ->setDescriptor('grant_five_stars_badge')
    ->setBadgeGranted('five_stars_badge')
;

$g->addEvent($e);

/**
 *
 * USAGE:
 *
 */

// User who receives gamification events
$g->setUserId(1);

try {
    $g->executeEvent('login', array('additional data sent to callback functions'));
} catch (Exception $e) {

}
