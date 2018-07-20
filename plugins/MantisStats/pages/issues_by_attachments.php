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
$mantis_bug_file_table      = db_get_table( 'mantis_bug_file_table' );
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
$attachments = $attachments_filesizes = $sum = $sum_filesizes = array();

$sum['open'] = $sum['resolved'] = 0;
$sum_filesizes['open'] = $sum_filesizes['resolved'] = 0;

$query = "
        SELECT mbft.bug_id, sum(filesize) AS file_size, count(*) as the_count
        FROM $mantis_bug_file_table mbft
        LEFT JOIN $mantis_bug_table mbt ON mbft.bug_id = mbt.id
        WHERE $specific_where
        AND mbt.date_submitted >= " . db_prepare_string( $db_datetimes['start'] ) . "
        AND mbt.date_submitted <= " . db_prepare_string( $db_datetimes['finish'] ) . "
        GROUP BY mbft.bug_id
        ORDER BY the_count DESC
        ";
$result = db_query( $query );

foreach ( $result as $row ) {
    $attachments[$row['bug_id']] = $row['the_count'];
    $attachments_filesizes[$row['bug_id']] = $row['file_size'];
    if ( $extra[$row['bug_id']]['st'] < $resolved_status_threshold ) {
        if ( isset( $sum['open'] ) ) { $sum['open'] = $sum['open'] + $attachments[$row['bug_id']]; } else { $sum['open'] = $attachments[$row['bug_id']]; }
    } else {
        if ( isset( $sum['resolved'] ) ) { $sum['resolved'] = $sum['resolved'] + $attachments[$row['bug_id']]; } else { $sum['resolved'] = $attachments[$row['bug_id']]; }        
    }

    if ( $extra[$row['bug_id']]['st'] < $resolved_status_threshold ) {
        if ( isset( $sum_filesizes['open'] ) ) { $sum_filesizes['open'] = $sum_filesizes['open'] + $attachments_filesizes[$row['bug_id']]; } else { $sum_filesizes['open'] = $attachments_filesizes[$row['bug_id']]; }
    } else {
        if ( isset( $sum_filesizes['resolved'] ) ) { $sum_filesizes['resolved'] = $sum_filesizes['resolved'] + $attachments_filesizes[$row['bug_id']]; } else { $sum_filesizes['resolved'] = $attachments_filesizes[$row['bug_id']]; }
    }
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
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_filesize' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_attachments' ) . "</td>
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
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_filesize' ) . "</td>
            <td class='dt-right nowrap'>" . lang_get( 'plugin_MantisStats_no_of_attachments' ) . "</td>
        </tr>
        </thead>
        <tbody>";


// populate html tables
$i = 0;
foreach ( $attachments as $key => $val ) {
    
    $t_filesize = number_format( round( $attachments_filesizes[$key] / 1024) );

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
                <td class='dt-right'>" . $t_filesize . "</td>
                <td class='dt-right'>" . $val . "</td>
                <td>" . $val . "</td>
                <td>" . $key . "</td>
            </tr>
            ";
    }
}

$i = 0;
foreach ( $attachments as $key => $val ) {

    $t_filesize = number_format( round( $attachments_filesizes[$key] / 1024) );

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
                <td class='dt-right'>" . $t_filesize . "</td>
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

$sum['all_states'] = $sum['open'] + $sum['resolved'];
$sum_count['all_states'] = $sum_count['open'] + $sum_count['resolved'];
$sum_filesizes['all_states'] = $sum_filesizes['open'] + $sum_filesizes['resolved'];

if ( $sum_count['open'] != 0 )          { $t_avg['open'] = round( $sum['open'] / $sum_count['open'], 2 ); }                                      else { $t_avg['open'] = 0; }
if ( $sum_count['resolved'] != 0 )      { $t_avg['resolved'] = round( $sum['resolved'] / $sum_count['resolved'], 2 ); }                          else { $t_avg['resolved'] = 0; }
if ( $sum_count['all_states'] != 0 )    { $t_avg['all_states'] = round( ( $sum['open'] + $sum['resolved'] ) / $sum_count['all_states'], 2 ); }   else { $t_avg['all_states'] = 0; }

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
                <td>" . lang_get( 'plugin_MantisStats_av_att_per_tkt' ) . "</td>
                <td class='dt-right'>" . $t_avg['open'] . "</td>
                <td class='dt-right'>" . $t_avg['resolved'] . "</td>
                <td class='dt-right'>" . $t_avg['all_states'] . "</td>
            </tr>
            <tr>
                <td>" . lang_get( 'plugin_MantisStats_total_no_of_atts' ) . "</td>
                <td class='dt-right'>" . number_format( $sum['open'] ) . "</td>
                <td class='dt-right'>" . number_format( $sum['resolved'] ) . "</td>
                <td class='dt-right'>" . number_format( $sum['all_states'] ) . "</td>
            </tr>
            <tr>
                <td>" . lang_get( 'plugin_MantisStats_total_size_attach' ) . "</td>
                <td class='dt-right'>" . number_format( round( $sum_filesizes['open']/1024 ) ) . "</td>
                <td class='dt-right'>" . number_format( round( $sum_filesizes['resolved']/1024 ) ) . "</td>
                <td class='dt-right'>" . number_format( round( $sum_filesizes['all_states']/1024 ) ) . "</td>
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
            "order": [[ 6, 'desc' ], [ 0, 'asc' ]],
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
                { "asSorting": [ "desc", "asc" ] },
                { "asSorting": [ "asc", "desc" ] },
            ],
            "columnDefs": [
                { 'orderData': [7], 'targets': [6] },
                {
                    'targets': [7],
                    'visible': false,
                    'searchable': false
                },
                { 'orderData': [8], 'targets': [0] },
                {
                    'targets': [8],
                    'visible': false,
                    'searchable': false
                },
            ],
            <?php echo $dt_language_snippet; ?>
        } );

        $('#open').show();

        $('#resolved').DataTable( {
            dom: 'lfrtip<"clear spacer">T',
            "order": [[ 6, 'desc' ], [ 0, 'asc' ]],
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
                { "asSorting": [ "desc", "asc" ] },
                { "asSorting": [ "asc", "desc" ] },
            ],
            "columnDefs": [
                { 'orderData': [7], 'targets': [6] },
                {
                    'targets': [7],
                    'visible': false,
                    'searchable': false
                },
                { 'orderData': [8], 'targets': [0] },
                {
                    'targets': [8],
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
                <input type="hidden" name="page" value="MantisStats/issues_by_attachments" />
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
