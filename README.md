# osticket-ical
## Description
This plugin adds Ical support to osTicket. 

It allows your osTicket install to recognise incoming emails which contain an iCal object and handle it in the following way:
   1. Store this object in the database
   1. Create an HTML rendering of the object, which is shown in the Ticket-entry.
   1. Provide links in the Ticket-entry to inspect the iCal object and to download it as an .ics file.

## Requires
   1. osTicket - https://github.com/osTicket/osTicket
   1. the ICal library by John Grogg   - https://github.com/u01jmg3/ics-parser
   
## How to install
   - Install osTicket
   - Install the ICal library by John Grogg   (see https://github.com/u01jmg3/ics-parser).
   
             cd upload
             composer.phar require johngrogg/ics-parser
           
 
   - Under upload : create directory 'tmp' ( the webserver needs write-access).
   
         mkdir upload/tmp
 
   - Install this plugin
   
         cp -r osticket-ical/qware_ical upload/include/plugins/
   - Install one php-file under upload/scp
   
         cp  osticket-ical/qware_ical/ical.php upload/scp
         
   - Patch class.mailparser.php ;  At the moment we need 1 small change in the osTicket code for our plugin to function.
      -  Edit upload/include/class.mailparser.php and at line 872, just before :
      
             Signal::send('mail.processed', $this, $vars);
   
      -  insert this line:
      
              $vars['current-mid'] = $mid;

     -  Nb. The reason for this patch is that the plugin needs the imap message-id ($mid) so it can access the IMAP-message and find  the calender imap data.
~

