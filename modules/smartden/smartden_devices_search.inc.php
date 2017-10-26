<?php
/*
* @version 0.1 (wizard)
*/
 global $session;
  if ($this->owner->name=='panel') {
   $out['CONTROLPANEL']=1;
  }
  $qry="1";
  // search filters
  // QUERY READY
  global $save_qry;
  if ($save_qry) {
   $qry=$session->data['smartden_devices_qry'];
  } else {
   $session->data['smartden_devices_qry']=$qry;
  }
  if (!$qry) $qry="1";
  $sortby_smartden_devices="ID DESC";
  $out['SORTBY']=$sortby_smartden_devices;
  // SEARCH RESULTS
  $res=SQLSelect("SELECT * FROM smartden_devices WHERE $qry ORDER BY ".$sortby_smartden_devices);
  if ($res[0]['ID']) {
   //paging($res, 100, $out); // search result paging
   $total=count($res);
   for($i=0;$i<$total;$i++) {
    // some action for every record if required
    $commands=SQLSelect("SELECT * FROM smartden_commands WHERE DEVICE_ID=".$res[$i]['ID']." AND LINKED_OBJECT!=''");
    if ($commands[0]['ID']) {
     foreach($commands as $cmd) {
      $res[$i]['LINKED_OBJECTS'].=$cmd['TITLE'].': '.$cmd['LINKED_OBJECT'].'.'.$cmd['LINKED_PROPERTY'].' ('.$cmd['VALUE'].')<br/>';
     }
    }
   }
   $out['RESULT']=$res;
  }
