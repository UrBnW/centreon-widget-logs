<?php
/**
 * Copyright 2005-2011 MERETHIS
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give MERETHIS
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of MERETHIS choice, provided that
 * MERETHIS also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

require_once "../../require.php";
require_once "./DB-Func.php";

require_once $centreon_path . 'www/class/centreon.class.php';
require_once $centreon_path . 'www/class/centreonSession.class.php';
require_once $centreon_path . 'www/class/centreonDB.class.php';
require_once $centreon_path . 'www/class/centreonWidget.class.php';
require_once $centreon_path . 'www/class/centreonDuration.class.php';
require_once $centreon_path . 'www/class/centreonUtils.class.php';
require_once $centreon_path . 'www/class/centreonACL.class.php';
require_once $centreon_path . 'www/class/centreonHost.class.php';

require_once $centreon_path . 'www/class/centreonMedia.class.php';
require_once $centreon_path . 'www/class/centreonCriticality.class.php';

require_once $centreon_path ."GPL_LIB/Smarty/libs/Smarty.class.php";

session_start();
if (!isset($_SESSION['centreon']) || !isset($_REQUEST['widgetId']) || !isset($_REQUEST['page'])) {
    exit;
}

$centreon = $_SESSION['centreon'];

$db = new CentreonDB();
if (CentreonSession::checkSession(session_id(), $db) == 0) {
    exit;
}
$dbb = new CentreonDB("centstorage");

/* Init Objects */
$criticality = new CentreonCriticality($db);
$media = new CentreonMedia($db);

$path = $centreon_path . "www/widgets/eventlogs/src/";
$template = new Smarty();
$template = initSmartyTplForPopup($path, $template, "./", $centreon_path);

$centreon = $_SESSION['centreon'];
$widgetId = $_REQUEST['widgetId'];
$page = $_REQUEST['page'];

$widgetObj = new CentreonWidget($centreon, $db);
$preferences = $widgetObj->getWidgetPreferences($widgetId);

// Set Colors Table
$res = $db->query("SELECT `key`, `value` FROM `options` WHERE `key` LIKE 'color%'");
$stateSColors = array(0 => "#13EB3A",
                     1 => "#F8C706",
                     2 => "#F91D05",
                     3 => "#DCDADA",
                     4 => "#2AD1D4");
$stateHColors = array(0 => "#13EB3A",
                     1 => "#F91D05",
                     2 => "#DCDADA",
                     3 => "#2AD1D4");
while ($row = $res->fetchRow()) {
    if ($row['key'] == "color_ok") {
        $stateSColors[0] = $row['value'];
    } elseif ($row['key'] == "color_warning") {
        $stateSColors[1] = $row['value'];
    } elseif ($row['key'] == "color_critical") {
        $stateSColors[2] = $row['value'];
    } elseif ($row['key'] == "color_unknown") {
        $stateSColors[3] = $row['value'];
    } elseif ($row['key'] == "color_pending") {
        $stateSColors[4] = $row['value'];
    } elseif ($row['key'] == "color_up") {
        $stateHColors[4] = $row['value'];
    } elseif ($row['key'] == "color_down") {
        $stateHColors[4] = $row['value'];
    } elseif ($row['key'] == "color_unreachable") {
        $stateHColors[4] = $row['value'];
    }
}

$stateSLabels = array(0 => "Ok",
                      1 => "Warning",
                      2 => "Critical",
                      3 => "Unknown",
                      4 => "Pending");

$stateHLabels = array(0 => "Up",
                      1 => "Down",
                      2 => "Unreachable",
                      3 => "Pending");

$typeLabels = array(0 => "SOFT",
                    1 => "HARD");

// Default colors
$stateColors = getColors($db);
// Get status labels
$stateLabels = getLabels();

$msg_type_set = array ();
if (isset($preferences['alert']) && $preferences['alert'] == "1") {
    array_push ($msg_type_set, "'0'");
    array_push ($msg_type_set, "'1'");
}
if (isset($preferences['notification']) && $preferences['notification'] == "1") {
    array_push ($msg_type_set, "'2'");
    array_push ($msg_type_set, "'3'");
}
if (isset($preferences['error']) && $preferences['error'] == "1")
    array_push ($msg_type_set, "'4'");

$msg_req = '';

$host_msg_status_set = array();
if (isset($preferences['host_up']) && $preferences['host_up'] == "1")
    array_push($host_msg_status_set, "'0'");
if (isset($preferences['host_down']) && $preferences['host_down'] == "1")
    array_push($host_msg_status_set, "'1'");
if (isset($preferences['host_unreachable']) && $preferences['host_unreachable'] == "1")
    array_push($host_msg_status_set, "'2'");

$svc_msg_status_set = array();
if (isset($preferences['service_ok']) && $preferences['service_ok'] == "1")
    array_push($svc_msg_status_set, "'0'");
if (isset($preferences['service_warning']) && $preferences['service_warning'] == "1")
    array_push($svc_msg_status_set, "'1'");
if (isset($preferences['service_critical']) && $preferences['service_critical'] == "1")
    array_push($svc_msg_status_set, "'2'");
if (isset($preferences['service_unknown']) && $preferences['service_unknown'] == "1")
    array_push($svc_msg_status_set, "'3'");

$flag_begin = 0;
if (isset($preferences['notification']) && $preferences['notification'] == "1") {
    if (count($host_msg_status_set)) {
        $flag_begin = 1;
        $msg_req .= " (`msg_type` = '3' AND `status` IN (" . implode(',', $host_msg_status_set)."))";
    }
    if (count($svc_msg_status_set)) {
        if ($flag_begin) {
            $msg_req .= " OR ";
        } else {
            $msg_req .= "(";
        }
        $msg_req .= " (`msg_type` = '2' AND `status` IN (" . implode(',', $svc_msg_status_set)."))";
        if (!$flag_begin) {
            $msg_req .= ") ";
        }
        $flag_begin = 1;
    }
}
if (isset($preferences['alert']) && $preferences['alert'] == "1") {
    if (count($host_msg_status_set)) {
        if ($flag_begin) {
            $msg_req .= " OR ";
        }
        $flag_begin = 1;
        $msg_req .= " (`msg_type` IN ('1', '10', '11') AND `status` IN (" . implode(',', $host_msg_status_set).")) ";
    }
    if (count($svc_msg_status_set)) {
        if ($flag_begin) {
            $msg_req .= " OR ";
        }
        $flag_begin = 1;
        $msg_req .= " ((`msg_type` IN ('0', '10', '11') ";
        $msg_req .= " AND `status` IN (" . implode(',', $svc_msg_status_set).")) ";
        $msg_req .= ") ";
    }
    if ($preferences['state_type_filter'] == "hardonly") {
        $flag_begin = 1;
        $msg_req .= " AND `type` = '1' ";
    } else if ($preferences['state_type_filter'] == "softonly") {
        $flag_begin = 1;
        $msg_req .= " AND `type` = '0' ";
    }
}
if (isset($preferences['error']) && $preferences['error'] == "1") {
    if ($flag_begin == 0) {
        $msg_req .= " AND ";
    } else {
        $msg_req .= " OR ";
    }
    $msg_req .= " (`msg_type` IN ('4') AND `status` IS NULL) ";
}
if (isset($preferences['info']) && $preferences['info'] == "1") {
    if ($flag_begin == 0) {
        $msg_req .= " AND ";
    } else {
        $msg_req .= " OR ";
    }
    $msg_req .= " (`msg_type` IN ('5'))";
}
if ($flag_begin) {
    $msg_req = " AND (".$msg_req.") ";
}

// Search on object name
if (isset($preferences['object_name_search']) && $preferences['object_name_search'] != "") {
    $tab = split(" ", $preferences['object_name_search']);
    $op = $tab[0];
    if (isset($tab[1])) {
        $search = $tab[1];
    }
    if ($op && isset($search) && $search != "") {
        $msg_req .= " AND (host_name ".CentreonUtils::operandToMysqlFormat($op)." '".$dbb->escape($search)."' ";
        $msg_req .= " OR service_description ".CentreonUtils::operandToMysqlFormat($op)." '".$dbb->escape($search)."' ";
        $msg_req .= " OR instance_name ".CentreonUtils::operandToMysqlFormat($op)." '".$dbb->escape($search)."') ";
    }
}

// Build final request
$start = time() - $preferences['log_period'];
$end = time();
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM logs WHERE ctime > '$start' AND ctime <= '$end' $msg_req";
$query .= " ORDER BY ctime DESC, host_name ASC, log_id DESC, service_description ASC";
$query .= " LIMIT ".($page * $preferences['entries']).",".$preferences['entries'];
$res = $dbb->query($query);
$nbRows = $dbb->numberRows();
$data = array();
$outputLength = $preferences['output_length'] ? $preferences['output_length'] : 50;

if (!$centreon->user->admin) {
    $pearDB = $db;
    $aclObj = new CentreonACL($centreon->user->get_id(), $centreon->user->get_admin());
    $lca = array("LcaHost" => $aclObj->getHostServices($dbb, null, 1), "LcaHostGroup" => $aclObj->getHostGroups(), "LcaSG" => $aclObj->getServiceGroups());
}

while ($row = $res->fetchRow()) {
    if (!$centreon->user->admin) {
        $continue = true;
        if (isset($row['host_id']) && isset($lca['LcaHost'][$row['host_id']])) {
            $continue = false;
        } elseif (isset($row['host_id']) && isset($row['service_description'])) {
            foreach ($lca['LcaHost'][$row['host_id']] as $key => $value) {
                if ($value == $row['service_description']) {
                    $continue = false;
                }
            }
        }
        if ($continue == true) {
            continue;
        }
    }
    if (isset($row['host_name']) && $row['host_name'] != "") {
        $data[$row['log_id']]['object_name1'] = $row['host_name'];
    } elseif (isset($row['instance_name']) && $row['instance_name'] != "") {
        $data[$row['log_id']]['object_name1'] = $row['instance_name'];
    } else {
        $data[$row['log_id']]['object_name1'] = "";
    }
    if (isset($row['service_description']) && $row['service_description'] != "") {
        $data[$row['log_id']]['object_name2'] = $row['service_description'];
    } else {
        $data[$row['log_id']]['object_name2'] = "";
    }
    foreach ($row as $key => $value) {
        if ($key == "ctime") {
            $value = date("Y-m-d H:i:s", $value);
        } elseif ($key == "status") {
            if (isset($row['service_description']) && $row['service_description'] != "") {
                $data[$row['log_id']]['color'] = $stateSColors[$value];
                $value = $stateSLabels[$value];
            } else if (isset($row['host_name']) && $row['host_name'] != "") {
                $data[$row['log_id']]['color'] = $stateHColors[$value];
                $value = $stateHLabels[$value];
            } else {
                $value = "Info";
            }
        } elseif ($key == "output") {
            $value = substr($value, 0, $outputLength);
        } elseif ($key == "type") {
            if (isset($row['host_name']) && $row['host_name'] != "") {
                $value = $typeLabels[$value];
            } else {
                $value = "";
            }
        } elseif ($key == "retry") {
            if (!isset($row['host_name']) || $row['host_name'] == "") {
                $value = "";
            }
        }
        $data[$row['log_id']][$key] = $value;
    }
}
$template->assign('centreon_web_path', trim($centreon->optGen['oreon_web_path'], "/"));
$template->assign('preferences', $preferences);
$template->assign('data', $data);
$template->display('index.ihtml');

?>
<script type="text/javascript">
    var nbRows = <?php echo $nbRows;?>;
    var currentPage = <?php echo $page;?>;
    var orderby = '<?php echo $orderby;?>';
    var nbCurrentItems = <?php echo count($data);?>;

    $(function () {
        $("#eventLogsTable").styleTable();
        if (nbRows > itemsPerPage) {
            $("#pagination").pagination(nbRows, {
                items_per_page: itemsPerPage,
                current_page: pageNumber,
                callback: paginationCallback
            }).append("<br/>");
        }

        $("#nbRows").html(nbCurrentItems+"/"+nbRows);

        $(".selection").each(function() {
            var curId = $(this).attr('id');
            if (typeof(clickedCb[curId]) != 'undefined') {
                this.checked = clickedCb[curId];
            }
        });

        var tmp = orderby.split(' ');
        var icn = 'n';
        if (tmp[1] == "DESC") {
            icn = 's';
        }
        $("[name="+tmp[0]+"]").append('<span style="position: relative; float: right;" class="ui-icon ui-icon-triangle-1-'+icn+'"></span>');
    });

    function paginationCallback(page_index, jq)
    {
        if (page_index != pageNumber) {
            pageNumber = page_index;
            clickedCb = new Array();
            loadPage();
        }
    }
</script>