<?php

require_once INCLUDE_DIR . 'class.plugin.php';




class QwareIcalPluginConfig extends PluginConfig implements PluginCustomConfig {
    private $msg = "Click on Save changes to check settings";
    
    function createDbTable(){
        $q = "CREATE TABLE IF NOT EXISTS qware_ical_data (  id int(11)  PRIMARY KEY AUTO_INCREMENT,   tid int(11),data text   );";
        $stmt = db_prepare($q);
        $stmt->execute();
        echo "Created SQL table qware_ical_data";
    }

    function renderCustomConfig(){
        $this->createDbTable();
        $form = $this->getForm();
        include STAFFINC_DIR . 'templates/simple-form.tmpl.php';
    }


    function saveCustomConfig(){
        $this->debug("custom config");
        $this->commitForm();

    }

    function debug($msg){
        LogHelperQware::debug('config',$msg);
    }

}

?>
