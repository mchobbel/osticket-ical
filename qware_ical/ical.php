<?php
  // This file
  //  * is part of the Qware-ical plugin
  //  * should installed directly under /scp/

  require('staff.inc.php');
  require_once INCLUDE_DIR . 'plugins/qware_ical/ical.qware.php';


  $qw = new Qware_ical_helper();
  $ical_id =     abs( intval($_GET['id']));
  $data = $qw->db_fetch($ical_id);
  if($_GET['download'] == "1"){
      $qw->download($ical_id,$data);
  }

  echo "# ical-id: ".$ical_id . "\n";
  $download_url  = "ical.php?id=".$ical_id."&download=1";
  $qwhtml = new Qware_ical2html($data);
  

?>



<?php echo $qwhtml->html($ical_id);?>
<pre>
 <?php   print_r($data); ?>
</pre>
<a href="<?php echo $download_url;?>">Download</a>
