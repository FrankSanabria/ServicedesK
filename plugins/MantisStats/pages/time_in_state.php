<?php

# MantisStats - a statistics plugin for MantisBT
#
# Copyright (c) MantisStats.Org
#
# MantisStats is available in two versions: LIGHT and PRO.
#
# MantisStats LIGHT version is free for use (freeware software).
# MantisStats PRO version is available for fee.
# MantisStats LIGHT and PRO versions are NOT open-source software.
#
# A copy of License was delivered to you during the software download. See LICENSE file.
#
# MantisStats is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See License for more
# details.
#
# https://www.mantisstats.org

if( !defined( 'MANTIS_VERSION' ) ) { exit(); }


html_page_top( lang_get( 'summary_link' ) );
echo "<br />";
if ( plugin_config_get( 'menu_location' ) == 'EVENT_MENU_SUMMARY' ) { print_summary_menu( 'summary_page.php' ); }

require_once 'common_includes.php';


$project_id                 = helper_get_current_project();
$specific_where             = helper_project_specific_where( $project_id );
$mantis_bug_table           = db_get_table( 'mantis_bug_table' );
$mantis_bugnote_table       = db_get_table( 'mantis_bugnote_table' );
$mantis_bug_history_table   = db_get_table( 'mantis_bug_history_table' );
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$private_bug_threshold      = config_get( 'private_bug_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$priority_enum_string       = lang_get( 'priority_enum_string' );
$severity_enum_string       = lang_get( 'severity_enum_string' );
$status_values              = MantisEnum::getValues( $status_enum_string );


// states drop-down preparation
$selectedPageOption = $status_values[0];

if ( isset( $_GET['selState'] ) and !empty( $_GET['selState'] ) ) {
    foreach ( $status_values as $k => $v) {
        if ( $v == strip_tags( $_GET['selState'] ) ) {
            $selectedPageOption = $v;
            $_SESSION['selState'] = $v;
            break;
        }
    }
} elseif ( isset( $_SESSION['selState'] ) and !empty( $_SESSION['selState'] ) ) {
    foreach ( $status_values as $k => $v) {
        if ( $v == strip_tags( $_SESSION['selState'] ) ) {
            $selectedPageOption = $v;
            break;
        }
    }
}

// drop-down prep
$pageOptionsDropDown = "<strong>" . lang_get( 'plugin_MantisStats_in_state' ) . "</strong>&nbsp;&nbsp;<select name='selState' id='selState'>";

foreach ( $status_values as $key => $val ) {
    $selected = "";
    if ( $selectedPageOption == $val ) { $selected = " selected "; }
    $pageOptionsDropDown .= "<option value='" . $val . "'" . $selected . ">" . MantisEnum::getLabel( $status_enum_string, $val ) . "</option>";
}

$pageOptionsDropDown .= "</select>";


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates( 'date-from', $dateFrom ) . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );


// prepare state, priority, severity, number of notes, issues creation date and summary for all issues
$extra = $created_times = $tmp_nt = array();

$query = "
        SELECT mbnt.bug_id, COUNT( * ) AS the_count
        FROM $mantis_bugnote_table mbnt
        LEFT JOIN $mantis_bug_table mbt ON mbnt.bug_id = mbt.id
        WHERE $specific_where
        AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        GROUP BY mbnt.bug_id;
        ";
$result = db_query( $query );

foreach ( $result as $row ) { $tmp_nt[$row['bug_id']] = $row['the_count']; }

unset( $result );

$query = "
        SELECT id, status, priority, severity, date_submitted, summary
        FROM $mantis_bug_table 
        WHERE $specific_where
        AND date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $extra[$row['id']]['st'] = $row['status'];
    $extra[$row['id']]['pr'] = MantisEnum::getLabel( $priority_enum_string, $row['priority'] );
    $extra[$row['id']]['sv'] = MantisEnum::getLabel( $severity_enum_string, $row['severity'] );

    if ( isset( $tmp_nt[$row['id']] ) ) { $extra[$row['id']]['nt'] = $tmp_nt[$row['id']]; } else { $extra[$row['id']]['nt'] = 0; }

    $created_times[$row['id']] = intval( $row['date_submitted'] );
    $extra[$row['id']]['sm'] = $row['summary'];
}

unset( $result );


// status changes FROM or TO desired state
$status_change_time = $data_table_sorter = $data_table_sorter_helper = $no_of_st_changes = $status_change_state = array();

$query = "
        SELECT mbht.bug_id, mbht.old_value, mbht.new_value, mbht.date_modified
        FROM $mantis_bug_history_table mbht
        LEFT JOIN $mantis_bug_table mbt ON mbht.bug_id = mbt.id
        WHERE $specific_where
        AND type = " . NORMAL_TYPE . "
        AND mbht.field_name = 'status'
        AND ( mbht.old_value = '" . $selectedPageOption . "' OR mbht.new_value = '" . $selectedPageOption . "' )
        AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        ORDER BY mbht.date_modified
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $status_change_time[$row['bug_id']][] = intval( $row['date_modified'] );
    if ( intval( $row['old_value'] ) == $selectedPageOption ) { $status_change_state[$row['bug_id']][] = 'old'; }
    if ( intval( $row['new_value'] ) == $selectedPageOption ) { $status_change_state[$row['bug_id']][] = 'new'; }
    if ( isset( $no_of_st_changes[$row['bug_id']] ) ) { $no_of_st_changes[$row['bug_id']]++; } else { $no_of_st_changes[$row['bug_id']] = 1; }
}

unset( $result );


$rows_with_st_change = array_unique( array_keys( $status_change_time ) );
$rows_without_st_change = array_diff( array_keys( $created_times ), $rows_with_st_change );


// prep those without status change, which are in desired state
foreach( $rows_without_st_change as $key => $val ) {
    if ( $extra[$val]['st'] == $selectedPageOption ) {
        $time_diff = $time_now - $created_times[$val];
        $data_table_sorter_helper[$val] = $time_diff;
    }
}

// go over those with status change
foreach ( $rows_with_st_change as $key => $val ) {

    $no_of_last_row = $no_of_st_changes[$val] - 1;

    if ( $status_change_state[$val][0] == 'old' ) {

        $i = 0;
        // initial
        $time_diff = $status_change_time[$val][$i] - $created_times[$val];
        $data_table_sorter_helper[$val] = $time_diff;

        // iterations
        $iterations = round( ($no_of_st_changes[$val] - 2) / 2 );
        
        while ( $iterations > $i ) {
            $i++;

            $n=2*$i;
            $k=2*$i-1;

            $time_diff = $status_change_time[$val][$n] - $status_change_time[$val][$k];
            if ( isset( $data_table_sorter_helper[$val] ) ) { $data_table_sorter_helper[$val] = $data_table_sorter_helper[$val] + $time_diff; } else { $data_table_sorter_helper[$val] = $time_diff; } 
        }

        // add last period till now()
        if ( round( $no_of_st_changes[$val]/2 ) == $no_of_st_changes[$val]/2 ) {
            $time_diff = $time_now - $status_change_time[$val][$no_of_last_row];
            if ( isset( $data_table_sorter_helper[$val] ) ) { $data_table_sorter_helper[$val] = $data_table_sorter_helper[$val] + $time_diff; } else { $data_table_sorter_helper[$val] = $time_diff; } 
        }

    }

    if ( $status_change_state[$val][0] == 'new' ) {

        $i = 0;

        // iterations
        $iterations = round( ($no_of_st_changes[$val] - 1) / 2 );
        
        while ( $iterations > $i ) {
            $i++;

            $n=2*$i-1;
            $k=2*$i-2;

            $time_diff = $status_change_time[$val][$n] - $status_change_time[$val][$k];
            if ( isset( $data_table_sorter_helper[$val] ) ) { $data_table_sorter_helper[$val] = $data_table_sorter_helper[$val] + $time_diff; } else { $data_table_sorter_helper[$val] = $time_diff; } 
        }

        // add last period till now()
        if ( round( $no_of_st_changes[$val]/2 ) != $no_of_st_changes[$val]/2 ) {
            $time_diff = $time_now - $status_change_time[$val][$no_of_last_row];
            if ( isset( $data_table_sorter_helper[$val] ) ) { $data_table_sorter_helper[$val] = $data_table_sorter_helper[$val] + $time_diff; } else { $data_table_sorter_helper[$val] = $time_diff; } 
        }

    }

}


// split open - resolved
$sum = array( 'open' => 0, 'resolved' => 0 );
$sum_count = array( 'open' => 0, 'resolved' => 0 );

foreach ( $data_table_sorter_helper as $key => $val ) {
    if ( $extra[$key]['st'] < $resolved_status_threshold ) {
        $data_table_sorter['open'][$key] = $data_table_sorter_helper[$key];
        if ( isset( $sum['open'] ) ) { $sum['open'] = $sum['open'] + $data_table_sorter_helper[$key]; } else { $sum['open'] = $data_table_sorter_helper[$key]; }
        $sum_count['open']++;
    } else {
        $data_table_sorter['resolved'][$key] = $data_table_sorter_helper[$key];
        if ( isset( $sum['resolved'] ) ) { $sum['resolved'] = $sum['resolved'] + $data_table_sorter_helper[$key]; } else { $sum['resolved'] = $data_table_sorter_helper[$key]; }
        $sum_count['resolved']++;
    }
}

if ( isset( $data_table_sorter['open'] ) ) { arsort( $data_table_sorter['open'] ); }
if ( isset( $data_table_sorter['resolved'] ) ) { arsort( $data_table_sorter['resolved'] ); }


// build tables headers
$data_table_print['open'] = "
    <table class='display' id='open' style='display:none'>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_MantisStats_issue_summary' ) . "</td>
            <td class='dt-right'>" . lang_get( 'status' ) . "</td>
            <td class='dt-right'>" . lang_get( 'priority' ) . "</td>
            <td class='dt-right'>" . lang_get( 'severity' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_notes' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_in_state' ) . "</td>
        </tr>
        </thead>
        <tbody>";

$data_table_print['resolved'] = "
    <table class='display' id='resolved' style='display:none'>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_MantisStats_issue_summary' ) . "</td>
            <td class='dt-right'>" . lang_get( 'status' ) . "</td>
            <td class='dt-right'>" . lang_get( 'priority' ) . "</td>
            <td class='dt-right'>" . lang_get( 'severity' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_notes' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_in_state' ) . "</td>
        </tr>
        </thead>
        <tbody>";


// populate html tables
$i = 0;
if ( isset( $data_table_sorter['open'] ) ) {
    foreach ( $data_table_sorter['open'] as $key => $val ) {

        if ( ( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }

        $i++;
        if ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) { break; }

        $data_table_print['open'] .= "
        <tr>
            <td>" . string_get_bug_view_link( $key ) . " - " . $extra[$key]['sm'] . "</td>
            <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$key]['st'] ) . "</td>
            <td class='dt-right'>" . $extra[$key]['pr'] . "</td>
            <td class='dt-right'>" . $extra[$key]['sv'] . "</td>
            <td class='dt-right'>" . $extra[$key]['nt'] . "</td>
            <td class='dt-right'>" . waitFormat( $val ) . "</td>
            <td>" . $val . "</td>
            <td>" . $key . "</td>
        </tr>
        ";
    }
}

$i = 0;
if ( isset( $data_table_sorter['resolved'] ) ) {
    foreach ( $data_table_sorter['resolved'] as $key => $val ) {

        if ( ( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }
        
        $i++;
        if ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) { break; }

        $data_table_print['resolved'] .= "
        <tr>
            <td>" . string_get_bug_view_link( $key ) . " - " . $extra[$key]['sm'] . "</td>
            <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$key]['st'] ) . "</td>
            <td class='dt-right'>" . $extra[$key]['pr'] . "</td>
            <td class='dt-right'>" . $extra[$key]['sv'] . "</td>
            <td class='dt-right'>" . $extra[$key]['nt'] . "</td>
            <td class='dt-right'>" . waitFormat( $val ) . "</td>
            <td>" . $val . "</td>
            <td>" . $key . "</td>
        </tr>
        ";
    }
}

$data_table_print['open']       .= "</tbody></table>";
$data_table_print['resolved']   .= "</tbody></table>";


// build summary table
$sum_count['all_states'] = $sum_count['open'] + $sum_count['resolved'];

if ( $sum_count['open'] != 0 ) { $avg['open'] = round( $sum['open'] / $sum_count['open'], 0 ); } else { $avg['open'] = 0; }
if ( $sum_count['resolved'] != 0 ) { $avg['resolved'] = round( $sum['resolved'] / $sum_count['resolved'], 0 ); } else { $avg['resolved'] = 0; }
if ( $sum_count['all_states'] != 0 ) { $avg['all_states'] = round( ( $sum['open'] + $sum['resolved'] ) / $sum_count['all_states'], 0 ); } else { $avg['all_states'] = 0; }

$summary_table_print = "

    <table class='display' id='summary' style='display:none'>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_MantisStats_summary_table' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_in_open_issues' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_in_resolved_iss' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_in_all_issues' ) . "</td>
        </tr>
        </thead>
        <tbody>
            <tr>
                <td>" . lang_get( 'plugin_MantisStats_avg_time_in_state' ) . " <strong>" . MantisEnum::getLabel( $status_enum_string, $selectedPageOption ) . "</strong></td>
                <td class='dt-right'>" . waitFormat( $avg['open'] ) . "</td>
                <td class='dt-right'>" . waitFormat( $avg['resolved'] ) . "</td>
                <td class='dt-right'>" . waitFormat( $avg['all_states'] ) . "</td>
            </tr>
        </tbody>
    </table>
    ";

?>


<script>
    $(document).ready( function () {
        $('#summary').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "paging": false,
            "info": false,
            "ordering": false,
            <?php echo $dt_language_snippet; ?>
        } );

        $('#summary').show();

        $('#open').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ 5, 'desc' ], [ 0, 'asc' ]],
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "aoColumns": [
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "desc", "asc" ] },
                { "asSorting": [ "asc", "desc" ] },
            ],
            "columnDefs": [
                { 'orderData': [6], 'targets': [5] },
                {
                    'targets': [6],
                    'visible': false,
                    'searchable': false
                },
                { 'orderData': [7], 'targets': [0] },
                {
                    'targets': [7],
                    'visible': false,
                    'searchable': false
                },
            ],
            <?php echo $dt_language_snippet; ?>
        } );

        $('#open').show();

        $('#resolved').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ 5, 'desc' ], [ 0, 'asc' ]],
            "autoWidth": false,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10,
            "aoColumns": [
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "asc", "desc" ] },
                { "asSorting": [ "desc", "asc" ] },
                { "asSorting": [ "asc", "desc" ] },
            ],
            "columnDefs": [
                { 'orderData': [6], 'targets': [5] },
                {
                    'targets': [6],
                    'visible': false,
                    'searchable': false
                },
                { 'orderData': [7], 'targets': [0] },
                {
                    'targets': [7],
                    'visible': false,
                    'searchable': false
                },
            ],
            <?php echo $dt_language_snippet; ?>
        } );

        $('#resolved').show();

    } );

    $(function() {
        $( "#from" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd"
        });
    });
    $(function() {
        $( "#to" ).datepicker({
            firstDay: 1,
            changeMonth: true,
            changeYear: true,
            maxDate: new Date(),
            showButtonPanel: true,
            dateFormat: "yy-mm-dd"
        });
    });
</script>

                
<div id="wrapper">

        <div id="logo">
        </div>

        <?php echo $whichReport; ?>

        <p />

        <div id="titleText">
            <div id="scope"><?php echo lang_get( 'plugin_MantisStats_project' ); ?>: <?php echo project_get_name( $project_id ); ?></div><div id="sup"><?php if ( $project_id == ALL_PROJECTS ) { echo "<sup>&dagger;</sup>"; } ?></div>
        </div>

        <p class="clear" />


        <div id="filter">
            <strong><?php echo lang_get( 'plugin_MantisStats_timeframe' ); ?></strong>

            <form method="get">
                <input type="hidden" name="page" value="MantisStats/time_in_state" />
                <?php echo form_security_field( 'date_picker' ) ?>
                <br />
                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
                    </div>
                    <p />
                    <div id="options">
                        <?php echo $pageOptionsDropDown; ?>
                    </div>
                </div>
                <div>
                    <input type="submit" id="displaysubmit" value=<?php echo lang_get( 'plugin_MantisStats_display' ); ?> class="button" />
                </div>
            </form>
        </div>


        <p class="space40Before" />
        <?php echo $summary_table_print; ?>

        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_open_issues' ); ?></strong>
        <p />
        <?php echo $data_table_print['open']; ?>

        <p class="space40Before" />
        <strong>&raquo; <?php echo lang_get( 'plugin_MantisStats_resolved_issues' ); ?></strong>
        <p />
        <?php echo $data_table_print['resolved']; ?>


        <p class="space40Before" />

        <?php if ( $project_id == ALL_PROJECTS ) { echo "<p />&dagger; " . lang_get( 'plugin_MantisStats_priv_proj_skip' ) . "<br />"; } ?>
        
        <?php if ( $maxResultsInTables ) { echo "<strong>&raquo;</strong> "; printf( lang_get( 'plugin_MantisStats_tables_maxdisp' ), $maxResultsInTables ); } ?>

        <?php if ( $showRuntime == 1 ) { printf( "<p class='graycolor'>" . lang_get( 'plugin_MantisStats_runtime_string' ) . "</p>", round(microtime(true) - $starttime, 5) ); } ?>

        <?php html_page_bottom();?>
</div>
