<?php

class LogHelperQware{

    function debug($name,$msg){
        $now = date ('Y-m-d H:i:s', time());
        $logdir = "/var/www/html/logs";
        $fn = $logdir . '/'. $name ;
        $fd = fopen($fn, 'a');
        fwrite($fd, $now . ' '. $msg . "\n");
        fclose($fd);
    }

}
?>
