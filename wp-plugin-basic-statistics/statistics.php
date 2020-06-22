<?php
/**
 * Plugin Name: Statistiky
 * Plugin URI: http://ondrejp.cz
 * Description: Statistiky
 * Version: 0.1a
 * Author: Ondřej Pešek
 * Author URI: http://ondrejp.cz
 * License: GPLv2
*/

require_once("functions.php");

add_action("admin_menu", "newPage");

if ($_GET["page"] == "slug_statistiky") {
  add_action("admin_enqueue_scripts", "loadCss");
}
