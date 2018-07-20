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


// get the rest of data from history table
$resolved_times = array();

$query = "
        SELECT mbht.bug_id, mbht.date_modified
        FROM $mantis_bug_history_table mbht
        LEFT JOIN $mantis_bug_table mbt
        ON mbht.bug_id = mbt.id
        WHERE $specific_where
        AND type = " . NORMAL_TYPE . "
        AND field_name='status'
        AND old_value < '" . $resolved_status_threshold . "'
        AND new_value >= '" . $resolved_status_threshold . "'
        AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        ORDER BY mbht.date_modified
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $resolved_times[$row['bug_id']] = intval( $row['date_modified'] );
}

unset( $result );


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
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_awaiting_for_resol' ) . "</td>
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
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_time_to_resolution' ) . "</td>
        </tr>
        </thead>
        <tbody>";


// populate tables and prepare for summary table
$sum = array( 'open' => 0, 'resolved' => 0 );
$sum_count = array( 'open' => 0, 'resolved' => 0 );

$data_table_helper = $data_table_sorter_helper = array( 'open' => array(), 'resolved' => array() );

foreach ( $extra as $key => $val ) {

    if ( $val['st'] < $resolved_status_threshold ) {

        $time_diff = $time_now - $created_times[$key];
        $sum['open'] = $sum['open'] + $time_diff;
        $sum_count['open']++;

        if ( ( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }

        $data_table_helper['open'][$key] = "
        <tr>
            <td>" . string_get_bug_view_link( $key ) . " - " . $extra[$key]['sm'] . "</td>
            <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$key]['st'] ) . "</td>
            <td class='dt-right'>" . $extra[$key]['pr'] . "</td>
            <td class='dt-right'>" . $extra[$key]['sv'] . "</td>
            <td class='dt-right'>" . $extra[$key]['nt'] . "</td>
            <td class='dt-right'>" . waitFormat( $time_diff ) . "</td>
            <td>" . $time_diff . "</td>
            <td>" . $key . "</td>
        </tr>
        ";
        $data_table_sorter_helper['open'][$key] = $time_diff;

    } else {

        if ( isset( $resolved_times[$key] ) ) {
            $time_diff = $resolved_times[$key] - $created_times[$key];
            $sum['resolved'] = $sum['resolved'] + $time_diff;
            $sum_count['resolved']++;
        } else {
            $sum_count['resolved']++;
            continue;
        }

        if ( ( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }
        
        $data_table_helper['resolved'][$key] = "
        <tr>
            <td>" . string_get_bug_view_link( $key ) . " - " . $extra[$key]['sm'] . "</td>
            <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$key]['st'] ) . "</td>
            <td class='dt-right'>" . $extra[$key]['pr'] . "</td>
            <td class='dt-right'>" . $extra[$key]['sv'] . "</td>
            <td class='dt-right'>" . $extra[$key]['nt'] . "</td>
            <td class='dt-right'>" . waitFormat( $time_diff ) . "</td>
            <td>" . $time_diff . "</td>
            <td>" . $key . "</td>
        </tr>
        ";
        $data_table_sorter_helper['resolved'][$key] = $time_diff;

    }

}


if ( isset( $data_table_sorter_helper['open'] ) ) { arsort( $data_table_sorter_helper['open'] ); }
if ( isset( $data_table_sorter_helper['resolved'] ) ) { arsort( $data_table_sorter_helper['resolved'] ); }


$i = 0;
foreach ( $data_table_sorter_helper['open'] as $key => $val ) {
    $i++;
    if ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) { break; }
    $data_table_print['open'] .= $data_table_helper['open'][$key];
}

$i = 0;
foreach ( $data_table_sorter_helper['resolved'] as $key => $val ) {
    $i++;
    if ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) { break; }
    $data_table_print['resolved'] .= $data_table_helper['resolved'][$key];
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
                <td>" . lang_get( 'plugin_MantisStats_awaiting_for_resol' ) . " / " . strtolower( lang_get( 'plugin_MantisStats_time_to_resolution' ) ) . "</td>
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
                <input type="hidden" name="page" value="MantisStats/time_to_resolution" />
                <?php echo form_security_field( 'date_picker' ) ?>
                <br />
                <div>
                    <div>
                        <input type="text" name="date-from" id="from" value="<?php echo cleanDates('date-from', $dateFrom); ?>" />
                        -
                        <input type="text" name="date-to" id="to"  value="<?php echo cleanDates('date-to', $dateTo); ?>" />
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
