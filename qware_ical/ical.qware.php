<?php

use ICal\ICal;

class Qware_ical_helper{

    function startsWith($haystack, $needle) {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }
    function endsWith($haystack, $needle) {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }


    function db_fetch($ical_id){
        $q = "select data from qware_ical_data where id=" .$ical_id;
        $result = db_query($q);
        $row = $result->fetch_row();
        return $row[0];
    }
    function db_insert($icaldata){
        $stmt = db_prepare("insert into qware_ical_data(data) values (?)");
        $stmt->bind_param("s",$icaldata);
        $stmt->execute();
        global $__db;
        return $__db->insert_id;
    }
    

    function process_email(&$mailfetcher,&$data){
        $mid = $data['current-mid'];
        $this->debug("trigger mail.processed signal, charset= ".$mailfetcher->charset);

        if ($icaldata=$mailfetcher->getPart($mid, 'text/calendar', $mailfetcher->charset)){
            $this->debug(" got icaldata.. going to insert it");
            $ical_record_id = $this->db_insert($icaldata);
            $this->debug("inserted ical-data with id: $ical_record_id ");
            $parser = new Qware_ical2html($icaldata);
            $icalhtml = $parser->html($ical_record_id);

            if( gettype($data['message']) == "object"){
                $currentType = get_class($data['message']);
                $this->debug("Existing body type : $currentType");
                if($currentType == 'HtmlThreadEntryBody'){

                    $this->debug("append ical html to existing body");
                    $data['message']->append($icalhtml);
                    return;
                  }
                if($currentType == 'TextThreadEntryBody'){
                    $this->debug("merge ical together with existing txt");                    
                    $html = $data['message']->display(false) . "<br>\n" . $icalhtml ;
                    $data['message'] = HtmlThreadEntryBody::fromFormattedText($html, 'html');
                    return;
                }
            }
            // No existing body, so the Ical-data is the whole body.
            $data['message'] =  HtmlThreadEntryBody::fromFormattedText($icalhtml, 'html');
        }else{
            $this->debug("no text/calendar part was found");
        }
    }

    
    function download($ical_id,$data){
        $fn2 = "event-".$ical_id.".ics";
        //$fn = "../tmp/icalevent.ics";
        //$handle = fopen($fn, "w");
        //fwrite($handle, $data);
        //fclose($handle);
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename='.$fn2);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        //header('Content-Length: ' . filesize($fn));
        header('Content-Length: ' . strlen($data));
        //readfile($fn);
        echo $data;
        exit;
        
    }
    function debug($msg){
        LogHelperQware::debug('ical_helper',$msg);
    }
    
}

class Qware_ical2html {
    // Don't change $color_code !
    //  it is used as identifier so jQuery can recognise  previously generated html-code.
    //  Nb. we need this work-around since HtmlThreadEntryBody strips all other html-attributes.
    static $color_code = '#a3a3a4';
    
    function __construct($raw){
        $this->raw = $raw;
    }

    static function printJs(){
        ?>
        <script type="text/javascript">

        $( document ).ready(function() {
            console.log( "ready qware js!" );
            var elm = $('div[style="color:<?php echo self::$color_code?>"]');
            
            if(elm.length>0){
                elm.find('span').css('color','white').css('font-size','8pt');
                var ical_id = elm.text().split('=')[1];
                if(ical_id != null){
                    var link = $('<a id="ics-inspect">').attr('ics-id',ical_id).append("Inspect").on('click',(x)=>{
                            var icsid = $(x.target).attr('ics-id');
                            var url = "ajax.php?view-ical-id="+icsid;
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

                    var url = 'ajax.php?download-ical-id='+ical_id;
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

    function hrefcallback2($matches){
        return ' <a href="'.$matches[2].'">[Link]</a> ';
    }
    function hrefcallback($matches){
        return ' <a href="'.$matches[1].'">[Link]</a> ';
    }
    function sipcallback($matches){
        return   ' ';
     }
     function descr2($event){
         $txt =  str_replace("\n","<br>",$event->description);
         //print($txt);
         //print("<br> ----------END_DEBUG---------------");

         $ret1 = preg_replace_callback(  '/(\&lt;)?(http[^\s<>&]+)(\&gt;)?/', [$this,'hrefcallback2'],$txt);
         $ret2 = preg_replace_callback( '/(\&lt;sip[^&]+)(\&gt;)?/', [$this,'sipcallback'],$ret1);
         $ret3 = preg_replace_callback( '/(\&lt;tel[^&]+)(\&gt;)?/', [$this,'sipcallback'],$ret2);
         return str_replace('%2B','+',$ret3);
     }
     function descr($event){
         $txt = str_replace("&gt;",">",str_replace("&lt;","<",$event->description));
         $ret1 = preg_replace_callback(  '/<?(http[^\s<>]+)>?/', [$this,'hrefcallback'],$txt);
         $ret2 = preg_replace_callback( '/(<sip[^>]+)>/', [$this,'sipcallback'],$ret1);
         $ret3 = preg_replace_callback( '/(<tel[^>]+)>/', [$this,'sipcallback'],$ret2);
         //$ret4 = str_replace(">","&gt;",str_replace("<","&lt;",$ret3));
         return str_replace("\n","<br>",$ret3);
     }



    function html($record_id){
        // 
        //date_default_timezone_set('UTC');
        $ical = new ICal();
        $ical->initString($this->raw);
        $event = $ical->events()[0];
        $dtstart = $ical->iCalDateToDateTime($event->dtstart_array[3]);
        $dtend = $ical->iCalDateToDateTime($event->dtend_array[3]);

        $html = '<table class="qware-ical-table">';
        $html .= $this->row("Summary:", $event->summary);
        $html .= $this->row("Description:", $this->descr($event));
        $html .= $this->row("From:",  $dtstart->format('d-m-Y H:i') );
        $html .= $this->row("Until:",  $dtend->format('d-m-Y H:i') );
        $html .= $this->row("Location:", $event->location);
        $html .= $this->row("Organizer:", $this->organizer_html($event));
        $html .= $this->row("Attendees:",$this->persons_html($event));

        $html .= "</table>";
        $html .= '<div style="color:'.self::$color_code . '">';
        $html .= ' <span>ical-id='.$record_id.'</span></div>';

        return '<div>' . $html.'</div>';

    }
    function debug($msg){
        LogHelperQware::debug('ical_html',$msg);
    }
    
}

?>
