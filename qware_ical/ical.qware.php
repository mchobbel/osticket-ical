<?php

use ICal\ICal;

class Qware_ical_helper{

    function startsWith($haystack, $needle) {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }
    function endsWith($haystack, $needle) {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }


    function process_email(&$mailfetcher,&$data){
        $mid = $data['current-mid'];
        $this->debug("trigger mail.processed signal ");

        if ($icaldata=$mailfetcher->getPart($mid, 'text/calendar', $mailfetcher->charset)){
            $this->debug(" got icaldata.. going to insert it");
            $stmt = db_prepare("insert into qware_ical_data(data) values (?)");
            $stmt->bind_param("s",$icaldata);
            $stmt->execute();
            global $__db;

            $last_id = $__db->insert_id;
            $this->debug("inserted ical-data with id: $last_id ");
            $parser = new Qware_ical2html($icaldata);
            $icalhtml = $parser->html($last_id);

            if( gettype($data['message']) == "object"){
                $currentType = get_class($data['message']);
                $this->debug("type of existing..: $currentType");
                if($this->endsWith($currentType,'Body')){
                    $this->debug("append ical html to body");
                    $data['message']->append($icalhtml);
                }

            }else{
                $body = HtmlThreadEntryBody::fromFormattedText($icalhtml, 'html');
                $data['message'] = $body;
            }
            
        }else{
            $this->debug("no text/calendar part was found");
        }
    }

    function fetch_ical_data($ical_id){
        $q = "select data from qware_ical_data where id=" .$ical_id;
        $result = db_query($q);
        $row = $result->fetch_row();
        return $row[0];
    }
    
    function download($ical_id,$data){
        $fn2 = "event-".$ical_id.".ics";
        $fn = "../tmp/icalevent.ics";
        $handle = fopen($fn, "w");
        fwrite($handle, $data);
        fclose($handle);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.$fn2);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fn));
        readfile($fn);
        exit;
        
    }
    function debug($msg){
        LogHelperQware::debug('ical_helper',$msg);
    }
    
}

class Qware_ical2html {
    
    function __construct($raw){
        $this->raw = $raw;
    }

    static function printJs(){
        ?>
        <script>

        $( document ).ready(function() {
            console.log( "ready qware js!" );
            var elm = $('div[style="color:#a3a3a4"]');
            
            if(elm.length>0){
                elm.find('span').css('color','white').css('font-size','8pt');
                var ical_id = elm.text().split('=')[1];
                if(ical_id != null){
                    var link = $('<a id="ics-inspect">').attr('ics-id',ical_id).append("Inspect").on('click',(x)=>{
                            var icsid = $(x.target).attr('ics-id');
                            var url = "ical.php?id="+icsid;
                            //console.log(x.target);
                            console.log("bla "+url);
                            $.get(url,(d)=>{
                                    var div =  $('<div class="qware-popup" style="border:2px solid #aaa;background-color:#eef;">');
                                    var close = $('<button>').append('close').on('click',(e)=>{
                                            $(e.target).parent().hide();
                                            $(e.target).parent().parent().find('a#ics-inspect').show();
                                        });
                                    div.append(close);
                                    div.append(d);
                                    elm.parent().append(div);
                                    elm.parent().find('a#ics-inspect').hide();
                            });
                    });


                    var url = 'ical.php?download=1&id='+ical_id;
                    var download = $('<a>').attr('href',url).append("Download ICS");
                    elm.parent().append('<div>').append(download);
                    elm.parent().append('<div>').append(link);                    
                }
            }
        });
        </script>
   <?php
    }
    
    function organizer_html($event){
        $person = $event->organizer_array;
        $name = $person[0]['CN'];
        $mail = $person[1];
        preg_match('/MAILTO:(.*)/i', $mail, $matches);
        if($matches){
            return $name . ' &lt;' . $matches[1] . '&gt;';
        }else{
            return "-n.a.-";
        }
    }
    function persons_html($event){
        $ret = '<ul>';
        foreach($this->persons($event) as $tup){
            $ret .= '<li>' . $tup['CN'] . ' &lt;' . $tup['EMAIL'] . '&gt; </li>';
        }
        $ret .= '</ul>';
        return $ret;
    }

    function persons($event){
        $ret = array();
        $tup = array();
        foreach($event->attendee_array as $row){
            if( is_array($row)){
                $tup = $row;
            }else{
                preg_match('/MAILTO:(.*)/i', $row, $matches);
                if($matches){
                    $tup['EMAIL'] = $matches[1];
                }
                $ret[] = $tup;
            }
        }
        return $ret;
    }


    function row($key,$val){
        return "<tr><td> $key </td> <td> $val </td></tr>";
    }

    function html($record_id){
        
        //date_default_timezone_set('UTC');
        $ical = new ICal();
        $ical->initString($this->raw);
        $event = $ical->events()[0];
        $dtstart = $ical->iCalDateToDateTime($event->dtstart_array[3]);
        $dtend = $ical->iCalDateToDateTime($event->dtend_array[3]);

        $html = '<table class="qware-ical-table">';
        $html .= $this->row("Summary:", $event->summary);
        $html .= $this->row("From:",  $dtstart->format('d-m-Y H:i') );
        $html .= $this->row("Until:",  $dtend->format('d-m-Y H:i') );
        $html .= $this->row("Location:", $event->location);
        $html .= $this->row("Organizer:", $this->organizer_html($event));
        $html .= $this->row("Attendees:",$this->persons_html($event));

        $html .= "</table>";
        // use a magic color-code so we can find this data with jQuery
        //  Nb. we need this work-around since HtmlThreadEntryBody strips all other attributes
        $html .= '<div style="color:#a3a3a4">';
        $html .= ' <span>ical-id='.$record_id.'</span></div>';

        return '<div>' . $html.'</div>';

    }
    function debug($msg){
        LogHelperQware::debug('ical_html',$msg);
    }
    
}

?>
