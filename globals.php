<?php

// Categories

class Category {

	function __construct($emoji, $color, $sortPriority, $name) {

		$this->emoji = $emoji;
		$this->color = $color;
		$this->sortPriority = $sortPriority;
		$this->name = $name;
	}
}

$cats_txt = fread(fopen('categories.txt', 'r'), 1024*1024);
$cats     = explode("\n", $cats_txt);
$CATEGORIES = array();
foreach ($cats as &$c) {

	$c = explode(', ', $c);
	$CATEGORIES[$c[3]] = new Category($c[0], $c[1], $c[2], $c[3]);
}
function catSort($a, $b) {

	if ($a->sortPriority == $b->sortPriority)
		return 0;
	return ($a->sortPriority < $b->sortPriority) ? 1 : -1;
}
uasort($CATEGORIES, "catSort");

//

define('TIME', time());

define('PERIODS',    array('This Week', 'Next Week', 'Two Weeks', 'Next Month', 'Next Quarter', 'Next Half', 'Next Year'));

define('NEXT_WEEK', strtotime("next monday", TIME));
define('THIS_WEEK', NEXT_WEEK - (7 * 24 * 60 * 60));
define('TWO_WEEKS', strtotime("next monday", TIME) + (7 * 24 * 60 * 60));
define('NEXT_MONTH', strtotime("first day of next month", TIME));
define('NEXT_QUARTER', getNextQuarter());
define('NEXT_HALF', getNextHalf());
define('NEXT_YEAR', getNextOccurrence("January 1st"));


define('SHOW_FUTURE', GetSetting(0));

function GetSetting($index) {

	$settings = fread(fopen('settings.txt', 'r'), 1024*1024);
	$settings = explode("\n", $settings);
	$setting = $settings[$index];
	return explode(': ', $setting)[1];
}

function getNextQuarter() {

	if (date('m') < 4)
		return getNextOccurrence("April 1st");
	else if (date('m') < 7)
		return getNextOccurrence("July 1st");
	else if (date('m') < 10)
		return getNextOccurrence("October 1st");
	else // using a ternary operator didn't work for this for some weird reason
		return getNextOccurrence("January 1st"); 
}

function getNextHalf() {

	if (date('m') < 7)
		return getNextOccurrence("July 1st");
	else
		return getNextOccurrence("January 1st");
}

function getNextOccurrence($date) {

	return strtotime($date) < TIME ? strtotime($date. (date('Y')+1), TIME) : strtotime($date, TIME);
}

?>