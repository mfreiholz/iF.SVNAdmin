<?php
/**
 * iF.SVNAdmin
 * Copyright (c) 2010 by Manuel Freiholz
 * http://www.insanefactory.com/
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; version 2
 * of the License.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.
 */
include("include/config.inc.php");
$appEngine->forwardInvalidModule(!$appEngine->isUserViewActive());
$appEngine->checkUserAuthentication(true, ACL_MOD_HISTORY, ACL_ACTION_VIEW);
$appTR->loadModule("historylist");

// query type. default page
$q = 'page';
$current_page = 1;

$pre_page = get_request_var("prePage"); // get the prePage page
$next_page = get_request_var("nextPage"); // get the nextPage page


// make sure to do which action
if (check_request_var("one_day") ||
  check_request_var('yesterday') ||
  check_request_var('one_week') ||
  check_request_var('one_month') ||
  check_request_var('show_all') ||
  check_request_var('pre_page') ||
  check_request_var('next_page')) {
  if (check_request_var('one_day')) {
    $q = 'show_day';
  } else if (check_request_var('yesterday')) {
    $q = 'yesterday';
  } else if (check_request_var('one_week')) {
    $q = 'one_week';
  } else if (check_request_var('one_month')) {
    $q = 'one_month';
  } else if (check_request_var('show_all')) {
    $q = 'show_all';
  } else if (check_request_var('pre_page')) {
    $q = $pre_page;
    $current_page = intval($q);
  } else if (check_request_var('next_page')) {
    $q = $next_page;
    $current_page = intval($q);
  }
}

// Get all histories list
$histories = $appEngine->getHistoryViewProvider()->getHistories($q);
$history_list = $histories["items"];
usort($history_list, array('\svnadmin\core\entities\History', "compare"));

$history_number = $histories["number"];
$pages = (int)($history_number / HISTORY_PAGE_ITEMS) + 1;
SetValue("HistoryList", $history_list);
SetValue("HistoryNumber", $history_number);
SetValue('prePage', $current_page > 1 ? $current_page - 1 : 1);
SetValue('nextPage', $current_page !== $pages ? $current_page + 1 : $pages);
SetValue('currentPage', $current_page);
SetValue('allPages', $pages);
ProcessTemplate("history/historylist.html.php");
?>