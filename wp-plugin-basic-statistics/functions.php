<?php

// vytvoří stránku
function newPage() {
  $title = "Statistiky";
  $user = "administrator";

  // přidá odkaz na stránku do menu
  add_menu_page($title, $title, $user, "slug_statistiky", "pageContent", "dashicons-chart-bar");
}

// obsah stránky
function pageContent() {
  global $wpdb;

  $stats = [];

  // příspěvky
  $stats["postsPublished"]["text"] = "Celkový počet publikovaných přístpěvků";
  $stats["postsPublished"]["data"] = publishedPostsCount();
  $stats["postsPublishedMonth"]["text"] = "Počet publikovaných příspěvků tento měsíc";
  $stats["postsPublishedMonth"]["data"] = count(postsTime("month"));
  $stats["postsPublishedWeek"]["text"] = "Počet publikovaných příspěvků tento týden";
  $stats["postsPublishedWeek"]["data"] = count(postsTime("week"));
  $stats["postsAverageWeek"]["text"] = "Průměrný počet publikovaných příspěvků na 1 den za posledních 7 dnů";
  $stats["postsAverageWeek"]["data"] = round(count(postsTime("week"))/7, 3);

  $posts = get_posts();

  $postsCharCount = 0;
  $postsWordCount = 0;
  $postsCount = 0;

  foreach ($posts as $key => $value) {
    $value = (array) $value;
    $postsCharCount += strlen(strip_tags($value["post_content"]));
    $postsWordCount += str_word_count(strip_tags($value["post_content"]));
    $postsCount++;
  }

  $stats["wordsAverage"]["text"] = "Průměrný počet slov na 1 článek";
  $stats["wordsAverage"]["data"] = round($postsWordCount/$postsCount, 3);

  $stats["charactersAverage"]["text"] = "Průměrný počet znaků na 1 článek";
  $stats["charactersAverage"]["data"] = round($postsCharCount/$postsCount, 3);

  // uživatelé
  $usersCount = count_users()["total_users"];
  $stats["usersCount"]["text"] = "Celkový počet uživatelů";
  $stats["usersCount"]["data"] = $usersCount;

  $stats["registeredLastMonthCalendar"]["text"] = "Registrovaní uživatelé za minulý kalendářní měsíc";
  $stats["registeredLastMonthCalendar"]["data"] = count(usersRegisteredTime("calendar"));

  $stats["registeredLastMonth"]["text"] = "Registrovaní uživatelé za poslední měsíc (30/31 dní)";
  $stats["registeredLastMonth"]["data"] = count(usersRegisteredTime());

  // autoři
  $authorsCount = getAuthors();

  $stats["authorsCount"]["text"] = "Celkový počet autorů";
  $stats["authorsCount"]["data"] = $authorsCount;

  // komentáře
  $commentsCount = get_comment_count()["total_comments"];

  $stats["commentsCount"]["text"] = "Celkový počet komentářů";
  $stats["commentsCount"]["data"] = $commentsCount;

  $stats["commentsPostAverage"]["text"] = "Průměrný počet komentářů na 1 článek";
  $stats["commentsPostAverage"]["data"] = round($commentsCount/publishedPostsCount(), 3);

  // vytvoří z výše nadefinovaných dat tabulku
  echo("<table id='statistics_table'>");
  echo("<thead><tr><td colspan='2'>Statistiky</td></tr></thead><tbody>");
  foreach ($stats as $key => $value) {
    echo("<tr>");
    echo("<td>{$value['text']}</td>");
    echo("<td>{$value['data']}</td>");
    echo("</tr>");
  }
  echo("</tbody></table>");

  // tabulka s 5 nejvíce komentovanými příspěvky
  $mostCommentedPosts = mostCommentedPosts();
  echo("<table id='statistics_most_commented'>");
  echo("<thead><tr><td colspan='6'>5 nejvíce komentovaných příspěvků</td></tr><tr>");
  echo("<td>ID příspěvku</td>");
  echo("<td>Autor</td>");
  echo("<td>Počet komentářů</td>");
  echo("<td>Status</td>");
  echo("<td>Datum zveřejnění</td>");
  echo("<td>Titulek</td>");
  echo("</tr></thead>");
  echo("<tbody>");
  foreach ($mostCommentedPosts as $key => $value) {
    echo("<tr>");
    echo("<td>".$value["ID"]."</td>");
    echo("<td>".$value["post_author"]."</td>");
    echo("<td>".$value["comment_count"]."</td>");
    echo("<td>".$value["post_status"]."</td>");
    echo("<td>".$value["post_date"]."</td>");
    echo("<td><a href='".$value["guid"]."'>".$value["post_title"]."</td>");
    echo("</tr>");
  }
  echo("</tbody></table>");
}

function publishedPostsCount() {
  $postsCount = wp_count_posts();

  if ($postsCount) {
    $published = $postsCount->publish;
  } else {
    $published = 0;
  }
  return($published);
}

function usersRegisteredTime($type = "") {
  global $wpdb;

  // uživatelé, kteří se zaregistrovali minulý měsíc (kalendářní)
  if ($type == "calendar") {

    $firstDayOfLastMonth = date("Y-m-d 00:00:00", strtotime("first day of last month"));
    $lastDayOfLastMonth = date("Y-m-d 00:00:00", strtotime("first day of this month"));
    $sqlQuery = "SELECT ID FROM $wpdb->users WHERE user_registered >= '".$firstDayOfLastMonth."' AND user_registered < '".$lastDayOfLastMonth."'";

  } else {
    // uživatelé, kteří se zaregistrovali za posledních 30/31 dní

    $monthAgo = date("Y-m-d 00:00:00", strtotime("-1 month"));
    $sqlQuery = "SELECT ID FROM $wpdb->users WHERE user_registered >= '".$monthAgo."'";
  }

  $sql = $wpdb->prepare($sqlQuery);
  $result = $wpdb->get_results($sql);

  return($result);
}

function mostCommentedPosts($count = 5) {
  global $wpdb;

  $sql = $wpdb->prepare("SELECT ID, comment_count, post_title, post_author, post_date, post_status, guid FROM $wpdb->posts WHERE comment_count > 0 ORDER BY comment_count DESC LIMIT ".$count);
  $data = $wpdb->get_results($sql);

  $result = [];
  $i = 0;
  foreach ($data as $key => $value) {
    $result[$i] = (array) $value;
    $result[$i]["post_author"] = get_user_by("id", $result[$i]["post_author"])->display_name;

    $i++;
  }

  return($result);
}

function postsTime($time = "month") {
  if ($time == "month") {
    $result = get_posts(
      [
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'date_query' =>
        [
          'column'  => 'post_date',
          'after'   => '-1 month'  // -7 Means last 7 days
        ]
      ]
    );
  } elseif($time == "week") {
    $result = get_posts(
      [
        'numberposts' => -1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'date_query' =>
        [
          'column'  => 'post_date',
          'after'   => '-1 week'  // -7 Means last 7 days
        ]
      ]
    );
  }
  return($result);
}

// příspěvky může přidávat super-admin, administrator, editor a contributor
function getAuthors() {
  $result = 0;

  $result += count_users()["avail_roles"]["super-admin"];
  $result += count_users()["avail_roles"]["administrator"];
  $result += count_users()["avail_roles"]["editor"];
  $result += count_users()["avail_roles"]["contributor"];

  return($result);
}

function loadCss() {
  wp_enqueue_style("statistics_style", plugins_url()."/statistics/style.css", false);
}

?>
