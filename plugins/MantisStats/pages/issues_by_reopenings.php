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


// prepare state, priority, severity, number of notes and summary for all issues
$extra = $tmp_nt = $sum_count = array();

$sum_count['open'] = $sum_count['resolved'] = 0;

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

    $extra[$row['id']]['sm'] = $row['summary'];

    if ( $extra[$row['id']]['st'] < $resolved_status_threshold ) { $sum_count['open']++; } else { $sum_count['resolved']++; }
}

unset( $result );


// get first note time from the bugnote table
$reopened = $sum = array();

$sum['open'] = $sum['resolved'] = 0;

$query = "
        SELECT mbht.bug_id, count(*) as the_count
        FROM $mantis_bug_history_table mbht
        LEFT JOIN $mantis_bug_table mbt ON mbht.bug_id = mbt.id
        WHERE $specific_where
        AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        AND mbht.type = " . NORMAL_TYPE . "
        AND mbht.field_name='status'
        AND mbht.old_value >= '" . $resolved_status_threshold . "'
        AND mbht.new_value < '" . $resolved_status_threshold . "'
        GROUP BY mbht.bug_id
        ORDER BY the_count DESC
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $reopened[$row['bug_id']] = $row['the_count'];
    if ( $extra[$row['bug_id']]['st'] < $resolved_status_threshold ) { $sum['open']++; } else { $sum['resolved']++; }
}

unset( $result );


// build tables headers
$data_table_print = array();

$data_table_print['open'] = "
    <table class='display' id='open' style='display:none'>
        <thead>
        <tr class='tblheader'>
            <td width='100%'>" . lang_get( 'plugin_MantisStats_issue_summary' ) . "</td>
            <td class='dt-right'>" . lang_get( 'status' ) . "</td>
            <td class='dt-right'>" . lang_get( 'priority' ) . "</td>
            <td class='dt-right'>" . lang_get( 'severity' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_notes' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_reopenings' ) . "</td>
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
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_reopenings' ) . "</td>
        </tr>
        </thead>
        <tbody>";


// populate html tables
$i = 0;
foreach ( $reopened as $key => $val ) {

    if ( ( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }

    if ( $extra[$key]['st'] < $resolved_status_threshold ) {
        $i++;
        if ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) { break; }

        $data_table_print['open'] .= "

            <tr>
                <td>" . string_get_bug_view_link( $key ) . " - " . $extra[$key]['sm'] . "</td>
                <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$key]['st'] ) . "</td>
                <td class='dt-right'>" . $extra[$key]['pr'] . "</td>
                <td class='dt-right'>" . $extra[$key]['sv'] . "</td>
                <td class='dt-right'>" . $extra[$key]['nt'] . "</td>
                <td class='dt-right'>" . $val . "</td>
                <td>" . $val . "</td>
                <td>" . $key . "</td>
            </tr>
            ";
    }
}

$i = 0;
foreach ( $reopened as $key => $val ) {

    if ( ( VS_PRIVATE == bug_get_field( $key, 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $key ) ) ) { continue; }

    if ( $extra[$key]['st'] >= $resolved_status_threshold ) {
        $i++;
        if ( $i > $maxResultsInTables and $maxResultsInTables != 0 ) { break; }

        $data_table_print['resolved'] .= "

            <tr>
                <td>" . string_get_bug_view_link( $key ) . " - " . $extra[$key]['sm'] . "</td>
                <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$key]['st'] ) . "</td>
                <td class='dt-right'>" . $extra[$key]['pr'] . "</td>
                <td class='dt-right'>" . $extra[$key]['sv'] . "</td>
                <td class='dt-right'>" . $extra[$key]['nt'] . "</td>
                <td class='dt-right'>" . $val . "</td>
                <td>" . $val . "</td>
                <td>" . $key . "</td>
            </tr>
            ";
    }

}


$data_table_print['open']       .= "</tbody></table>";
$data_table_print['resolved']   .= "</tbody></table>";


// build summary table
$perc = array();

$sum_count['all_states'] = $sum_count['open'] + $sum_count['resolved'];

if ( $sum_count['open'] != 0 )          { $perc['open'] = round( 100*$sum['open'] / $sum_count['open'], 2 ); }                                      else { $perc['open'] = 0; }
if ( $sum_count['resolved'] != 0 )      { $perc['resolved'] = round( 100*$sum['resolved'] / $sum_count['resolved'], 2 ); }                          else { $perc['resolved'] = 0; }
if ( $sum_count['all_states'] != 0 )    { $perc['all_states'] = round( 100*( $sum['open'] + $sum['resolved'] ) / $sum_count['all_states'], 2 ); }   else { $perc['all_states'] = 0; }

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
                <td>" . lang_get( 'plugin_MantisStats_perc_of_reopenings' ) . "</td>
                <td class='dt-right'>" . $perc['open'] . "%</td>
                <td class='dt-right'>" . $perc['resolved'] . "%</td>
                <td class='dt-right'>" . $perc['all_states'] . "%</td>
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
                <input type="hidden" name="page" value="MantisStats/issues_by_reopenings" />
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
