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
$resolved_status_threshold  = config_get( 'bug_resolved_status_threshold' );
$private_bug_threshold      = config_get( 'private_bug_threshold' );
$status_enum_string         = lang_get( 'status_enum_string' );
$priority_enum_string       = lang_get( 'priority_enum_string' );
$severity_enum_string       = lang_get( 'severity_enum_string' );


// start and finish dates and times
$db_datetimes = array();

$db_datetimes['start']  = strtotime( cleanDates( 'date-from', $dateFrom, 'begOfTimes' ) . " 00:00:00" );
$db_datetimes['finish'] = strtotime( cleanDates( 'date-to', $dateTo ) . " 23:59:59" );


// prepare state, priority, severity, number of notes and summary for all issues
$extra = array();

$query = "
        SELECT mbt.id, mbt.status, mbt.priority, mbt.severity, mbt.date_submitted, mbt.summary
        FROM $mantis_bug_table mbt
        WHERE $specific_where
        AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        GROUP BY mbt.id;
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $extra[$row['id']]['st'] = $row['status'];
    $extra[$row['id']]['pr'] = MantisEnum::getLabel( $priority_enum_string, $row['priority'] );
    $extra[$row['id']]['sv'] = MantisEnum::getLabel( $severity_enum_string, $row['severity'] );
    $extra[$row['id']]['sm'] = $row['summary'];
}

unset( $result );


// get data
$issues_fetch_from_db = array();

$query = "
        (
            SELECT mbt.id, count(*) AS the_count, mbt.status
            FROM $mantis_bug_table mbt
            LEFT JOIN $mantis_bugnote_table mbnt
            ON mbt.id = mbnt.bug_id
            WHERE $specific_where
            AND mbnt.id IS NOT NULL
            AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
            AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
            GROUP BY mbt.id
        )
            UNION
        (
            SELECT mbt.id, 0, mbt.status
            FROM $mantis_bug_table mbt
            LEFT JOIN $mantis_bugnote_table mbnt
            ON mbt.id = mbnt.bug_id
            WHERE $specific_where
            AND mbnt.id IS NULL
            AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
            AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        )
            ORDER BY the_count DESC, id ASC;
        ";
$result = db_query( $query );


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
        </tr>
        </thead>
        <tbody>";


// populate html tables
$i['open'] = $i['resolved'] = 0;
$sum['open'] = $sum['resolved'] = 0;
$nonotes['open'] = $nonotes['resolved'] = 0;

foreach ( $result as $row ) {

    if ( $row['status'] < $resolved_status_threshold ) {

        $i['open']++;
        $sum['open'] = $sum['open'] + $row['the_count'];
        
        if( $row['the_count'] == 0 ) { $nonotes['open']++; }

        if ( ( VS_PRIVATE == bug_get_field( $row['id'], 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $row['id'] ) ) ) { continue; }
        if ( $maxResultsInTables != 0 and $i['open'] > $maxResultsInTables ) { continue; }

        $data_table_print['open'] .= "
        <tr>
            <td>" . string_get_bug_view_link( $row['id'] ) . " - " . $extra[$row['id']]['sm'] . "</td>
            <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$row['id']]['st'] ) . "</td>
            <td class='dt-right'>" . $extra[$row['id']]['pr'] . "</td>
            <td class='dt-right'>" . $extra[$row['id']]['sv'] . "</td>
            <td class='dt-right'>" . number_format( $row['the_count'] ) . "</td>
            <td>" . $row['id'] . "</td>
        </tr>
        ";

    } else {

        $i['resolved']++;
        $sum['resolved'] = $sum['resolved'] + $row['the_count'];

        if( $row['the_count'] == 0 ) { $nonotes['resolved']++; }

        if ( ( VS_PRIVATE == bug_get_field( $row['id'], 'view_state' ) ) && ( false == access_has_bug_level( $private_bug_threshold, $row['id'] ) ) ) { continue; }
        if ( $maxResultsInTables != 0 and $i['resolved'] > $maxResultsInTables ) { continue; }

        $data_table_print['resolved'] .= "
        <tr>
            <td>" . string_get_bug_view_link( $row['id'] ) . " - " . $extra[$row['id']]['sm'] . "</td>
            <td class='dt-right'>" . MantisEnum::getLabel( $status_enum_string, $extra[$row['id']]['st'] ) . "</td>
            <td class='dt-right'>" . $extra[$row['id']]['pr'] . "</td>
            <td class='dt-right'>" . $extra[$row['id']]['sv'] . "</td>
            <td class='dt-right'>" . number_format( $row['the_count'] ) . "</td>
            <td>" . $row['id'] . "</td>
        </tr>
        ";
        
    }

}

$data_table_print['open']       .= "</tbody></table>";
$data_table_print['resolved']   .= "</tbody></table>";

unset ( $result );


// summary table
$i['all_states'] = $i['open'] + $i['resolved'];

if ( $i['open'] != 0 ) { $avg['open'] = round( $sum['open'] / $i['open'], 2 ); } else { $avg['open'] = 0; }
if ( $i['resolved'] != 0 ) { $avg['resolved'] = round( $sum['resolved'] / $i['resolved'], 2 ); } else { $avg['resolved'] = 0; }
if ( $i['all_states'] != 0 ) { $avg['all_states'] = round( ( $sum['open'] + $sum['resolved'] ) / $i['all_states'], 2 ); } else { $avg['all_states'] = 0; }

$nonotes['all_states'] = $nonotes['open'] + $nonotes['resolved'];


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
                <td>" . lang_get( 'plugin_MantisStats_average_nt_periss' ) . "</td>
                <td class='dt-right'>" . $avg['open'] . "</td>
                <td class='dt-right'>" . $avg['resolved'] . "</td>
                <td class='dt-right'>" . $avg['all_states'] . "</td>
            </tr>
            <tr>
                <td>" . lang_get( 'plugin_MantisStats_no_iss_without_nt' ) . "</td>
                <td class='dt-right'>" . number_format( $nonotes['open'] ) . "</td>
                <td class='dt-right'>" . number_format( $nonotes['resolved'] ) . "</td>
                <td class='dt-right'>" . number_format( $nonotes['all_states'] ) . "</td>
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
            "order": [[ 4, 'desc' ], [ 0, 'asc' ]],
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
            ],
            "columnDefs": [
                { 'orderData': [5], 'targets': [0] },
                {
                    'targets': [5],
                    'visible': false,
                    'searchable': false
                },
            ],
            <?php echo $dt_language_snippet; ?>
        } );

        $('#open').show();

        $('#resolved').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ 4, 'desc' ], [ 0, 'asc' ]],
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
            ],
            "columnDefs": [
                { 'orderData': [5], 'targets': [0] },
                {
                    'targets': [5],
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
                <input type="hidden" name="page" value="MantisStats/issues_by_notes" />
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
