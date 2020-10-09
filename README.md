# osticket-ical
## Description
This plugin adds Ical support to osTicket. 

It allows your osTicket install to recognise incoming emails which contain an iCal object and handle it in the following way:
   1. Store the iCal object in the database
   1. Create a human-readable HTML rendering of the iCal object, which is included in in the Ticket-entry.
   1. Provide links in the Ticket-entry to inspect the iCal object and to download it as an .ics file.

Nb. ical is also called ics or [icalendar](https://en.wikipedia.org/wiki/ICalendar)

## Requires
   1. osTicket - https://github.com/osTicket/osTicket
   1. the ICal library by John Grogg   - https://github.com/u01jmg3/ics-parser
   
## How to install
   - Install osTicket
   - Install the ICal library by John Grogg   (see https://github.com/u01jmg3/ics-parser).
   
         cd upload
         composer.phar require johngrogg/ics-parser
           
    
   - Install this plugin
   
         git clone https://github.com/mchobbel/osticket-ical.git
   
         cp -r osticket-ical/qware_ical YOUR_OST_INSTAL/upload/include/plugins/
   
         
   - Patch class.mailparser.php ;  At the moment we need 1 small change in the osTicket code for our plugin to function.
      -  Edit upload/include/class.mailparser.php and at line 872, just before :
      
             Signal::send('mail.processed', $this, $vars);
   
      -  insert this line:
      
              $vars['current-mid'] = $mid;

     -  Nb. The reason for this patch is that the plugin needs the imap message-id ($mid) so it can access the IMAP-message and find  the calender imap data.
~
   - Activate the plugin. As admin goto your osticket staff panel and go to Manage -> Plugins. 
     - Choose Add new plugin. You should now find 'Qware ical handler'
     - Enable the plugin
     - Click on the Plugin name 'Qware ical handler' to trigger creation of the SQL table 'qware_ical_data'
     - Now you're good to go!
