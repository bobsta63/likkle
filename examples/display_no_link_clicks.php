<?php

/**
 * Likkle
 *
 * Likkle is a composer compatible PHP client library for the lk2.in
 *  URL shortener service (http://lk2.in).
 *
 * @author bobbyallen.uk@gmail.com (Bobby Allen)
 * @version 1.0.0
 * @license http://opensource.org/licenses/MIT
 * @link https://github.com/bobsta63/likkle
 * @link http://www.bobbyallen.me
 *
 */
require_once '../src/Ballen/Likkle/Lk2inClient.php';

use Ballen\Likkle\Lk2inClient;

$lk2client = new Lk2inClient;

$shortcode = 'vwhi5';
$number_of_clicks = $lk2client->getClicks($shortcode);

switch ($number_of_clicks) {
    case 0:
        $stub = "never";
        break;
    case 1:
        $stub = " once";
        break;
    default:
        $stub = $number_of_clicks . " times";
        break;
}

echo "The URL <a href=\"http://lk2.in/" . $shortcode . "\">http://lk2.in/" . $shortcode . "</a> has been clicked " . $stub . ".";
?>