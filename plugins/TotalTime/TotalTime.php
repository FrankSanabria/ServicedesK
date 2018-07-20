<?php

/**
 * requires columns_api
 */
require_once( 'columns_api.php' );
// Kind of an awkward name because we don't want to conflict with a native Mantis class if they ever add one.
class TotalTimePluginColumn extends MantisColumn
{
    public $title = "Total Time";
    public $column = "col";
    public $sortable = false;
    public function display( $p_bug, $p_columns_target )
    {
        echo db_minutes_to_hhmm(db_query("SELECT SUM(`time_tracking`) FROM `mantis_bugnote_table` WHERE bug_id={$p_bug->id}")->fields["SUM(`time_tracking`)"]);
    }
}

class TotalTimePlugin extends MantisPlugin 
{
    function register() 
    {
        $this->name        = 'Total Time Column';
        $this->description = 'Adds a column within the View Issues screen showing total time for task.';
        $this->version     = '1.0';
        $this->author      = 'Artem Nikolaev';
        $this->contact     = 'artem@nikoalev.by';
        //$this->url         = 'http://nikolaev.by';

        $this->requires = array(    # Plugin dependencies, array of basename => version pairs
            'MantisCore' => '1.2.0',  #   Should always depend on an appropriate version of MantisBT
            );
    }
 
    function init() 
    {
        plugin_event_hook( 'EVENT_FILTER_COLUMNS', 'addColumn' );
    }

    function addColumn()
    {
        return array( 'TotalTimePluginColumn' );
    }
}
?>
