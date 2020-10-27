<?php

if( ! file_exists(ROOT_DIR.'vendor/autoload.php')){
    echo "FATAL ERROR, file not found: ".ROOT_DIR.'vendor/autoload.php';
}
require_once(ROOT_DIR.'vendor/autoload.php');
require_once 'ical.qware.php';

require_once 'config.php';
if( ! class_exists('LogHelperQware')) {
    require_once 'log_helper.php';
}

class QwareIcalPlugin extends Plugin {
    var $config_class = 'QwareIcalPluginConfig';
    function bootstrap() {

        // NEED TO patch class.mailparser.php to pass the $mid variable
        //
        // ADD THIS LINE:
        //   $vars['current-mid'] = $mid;
        // just before
        //    Signal::send('mail.processed', $this, $vars);


        Signal::connect('object.view', function($object,$type){
            Qware_ical2html::printJs();
        });
        
        Signal::connect('mail.processed', function(&$mailfetcher,&$data) {
            $qwh = new Qware_ical_helper();
            $qwh->process_email($mailfetcher,$data);

        });

        Signal::connect('ajax.scp', function($object,$type){
            $id = intval($_GET['download-ical-id']);
            if($id>0){
                $qw = new Qware_ical_helper();
                $data = $qw->db_fetch($id);
                $qw->download($id,$data);
            }
            $id = intval($_GET['view-ical-id']);
            if($id>0){
                $qw = new Qware_ical_helper();
                $data = $qw->db_fetch($id);
                    
                if($_GET['mode']=='raw'){
                    echo '<h3>mode=raw</h3>';
                    echo "<pre>";
                    echo htmlspecialchars($data);
                    echo "</pre>";
                    exit;
                }else{
                    $qwhtml = new Qware_ical2html($data);
                    echo $qwhtml->html($id);
                    echo "<pre>";
                    echo htmlspecialchars($data);                    
                    echo "</pre>";
                    exit;
                }
            }
            
        });

    }

    function debug($msg){
        LogHelperQware::debug('boostrap',$msg);
    }
}

