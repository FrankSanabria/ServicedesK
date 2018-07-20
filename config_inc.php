<?php
$g_hostname 		= 'localhost';
$g_db_type 		= 'mysql';
$g_database_name 	= 'servicedesk';
$g_db_username 		= 'root';
$g_db_password 		= '';

$g_show_realname 	= ON;
$g_default_timezone 	= 'America/Bogota';
$g_favicon_image        = 'images/favicon.png';
$g_window_title         = 'Su|GE3K Service Desk';
$g_logo_white		= 'LogoService_white.png';
$g_logo_image           = '';
$g_enable_project_documentation = ON;

// UBICACION DE LA INSTALACION DEL SERVICEDESK
// $g_path 		= '';
$g_version_suffix       = 'Rev_4';
$g_rss_enabled 		= OFF;
$g_recently_visited 	= OFF;

//CONFIGURACION DEL CORREO
$g_administrator_email  = '';
$g_webmaster_email      = '';
$g_from_email           = '';
$g_from_name            = 'Mesa de Servicios SU|GE3K';
$g_return_path_email    = '';
$g_phpMailer_method     = PHPMAILER_METHOD_SMTP;
$g_smtp_host            = '';
$g_smtp_username 	= '';
$g_smtp_password 	= '';
$g_smtp_connection_mode = 'ssl';
$g_smtp_port 		= 465;

$g_log_level		= LOG_EMAIL | LOG_EMAIL_RECIPIENT  | LOG_AJAX;
$g_bugnote_order        = 'DESC';
$g_default_bugnote_order = 'DESC';
$g_my_view_bug_count = 25;
$g_my_view_boxes = array (
                'unassigned'    => '0',
               	'assigned'      => '1',
                'reported'      => '0',
                'monitored'     => '0',
               	'resolved'	=> '0',
                'recent_mod'    => '2',
               	'feedback'              => '0',
                'verify'          	=> '0',
                'my_comments'   => '0'
        );
//COLOCAR EN ON SI EL CHAT SE ACTIVA
$g_my_view_boxes_fixed_position = OFF;
$g_bug_view_page_fields = array (
                'id',
                'project',
                'category_id',
                'priority',
                'date_submitted',
                'last_updated',
                'reporter',
                'handler',
               	'status',
                'resolution',
               	'summary',
                'description',
                'attachments',
                'due_date',
		'tags',
        );
$g_bug_report_page_fields = array (
                'id',
                'priority',
                'summary',
                'description',
                'category_id',
                'attachments',
                'tags',
        );


