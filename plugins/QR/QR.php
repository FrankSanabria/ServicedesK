<?php
class QRPlugin extends MantisPlugin {

  function register() {
    $this->name        = 'QR';
    $this->description =  lang_get( 'plugin_QR_description' );
    $this->version     = '1.1';
    $this->requires    = array('MantisCore' => '1.2.19', 'Lightbox' => '1.0');
    $this->author      = 'Frank Sanabria';
    $this->contact     = 'contacto@sugeek.co';
    $this->url         = 'http://www.sugeek.co';
    $this->page        = '';

   }
	
 function init() {
    plugin_event_hook( 'EVENT_MENU_ISSUE', 'qrmenu' );
  }

  function qrmenu() {
    $f_bug_id =  gpc_get_int( 'id' );
    $bug = bug_get( $f_bug_id, true ); 
    $f_reporter = lang_get ('plugin_QR_attend_by') . user_get_name($bug->reporter_id);
    $f_bug_id =  gpc_get_int( 'id' );
    $f_project = lang_get ('plugin_QR_project') . project_get_name ( $bug->project_id );
    $f_category = lang_get ('plugin_QR_category') . category_full_name( $bug->category_id );
    $f_date_time = date( config_get( 'normal_date_format' ), $bug->date_submitted );
    $f_new_line = '%0A';	
    $f_info = lang_get( 'plugin_QR_issue' ) . $f_bug_id . $f_new_line; 
    $f_info .= $f_reporter . $f_new_line . $f_project . $f_new_line . $f_category . $f_new_line . lang_get( 'plugin_QR_date' ) . $f_date_time;	
    $f_link = 'https://chart.googleapis.com/chart?chs=500x500&cht=qr&chld=H&chl=' . $f_info ;
    return array( '<a rel="lightbox" data-title="<a style=\' color:#fff\' target=\'_blank\' href=\' ' . $f_link . ' \'> ' . lang_get ('plugin_QR_print') . ' </a>" href="' . $f_link . '" title="' . lang_get( 'menu_QR_link' ) . '">' . lang_get( 'menu_QR_link' ) . '</a>');
  }



}
