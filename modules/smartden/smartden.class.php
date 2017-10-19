<?php
/**
* SmartDen 
* @package project
* @author Wizard <sergejey@gmail.com>
* @copyright http://majordomo.smartliving.ru/ (c)
* @version 0.1 (wizard, 14:10:26 [Oct 19, 2017])
*/
//
//
class smartden extends module {
/**
* smartden
*
* Module class constructor
*
* @access private
*/
function smartden() {
  $this->name="smartden";
  $this->title="SmartDen";
  $this->module_category="<#LANG_SECTION_DEVICES#>";
  $this->checkInstalled();
}
/**
* saveParams
*
* Saving module parameters
*
* @access public
*/
function saveParams($data=0) {
 $p=array();
 if (IsSet($this->id)) {
  $p["id"]=$this->id;
 }
 if (IsSet($this->view_mode)) {
  $p["view_mode"]=$this->view_mode;
 }
 if (IsSet($this->edit_mode)) {
  $p["edit_mode"]=$this->edit_mode;
 }
 if (IsSet($this->data_source)) {
  $p["data_source"]=$this->data_source;
 }
 if (IsSet($this->tab)) {
  $p["tab"]=$this->tab;
 }
 return parent::saveParams($p);
}
/**
* getParams
*
* Getting module parameters from query string
*
* @access public
*/
function getParams() {
  global $id;
  global $mode;
  global $view_mode;
  global $edit_mode;
  global $data_source;
  global $tab;
  if (isset($id)) {
   $this->id=$id;
  }
  if (isset($mode)) {
   $this->mode=$mode;
  }
  if (isset($view_mode)) {
   $this->view_mode=$view_mode;
  }
  if (isset($edit_mode)) {
   $this->edit_mode=$edit_mode;
  }
  if (isset($data_source)) {
   $this->data_source=$data_source;
  }
  if (isset($tab)) {
   $this->tab=$tab;
  }
}


function readState($device_id) {
    $device=SQLSelectOne("SELECT * FROM smartden_devices WHERE ID=".(int)$device_id);
    if (!$device['ID']) return;
    $data=getURL('http://'.$device['IP'].'/current_state.xml?pw='.$device['PASSWORD']);
    $this->processStateXML($device_id,$device['DEVICE_TYPE'],$data);
}

function processStateXML($device_id,$device_type,$data) {
    $res = simplexml_load_string($data);
    $res = json_decode(json_encode($res),true);
    if (is_array($res)) {
        if ($device_type=='ip16r') {
            foreach($res as $k=>$v) {
                $title=$v['Name'];
                $value=$v['State'];
                $this->processStateCommand($device_id,$k,$value,$title);
            }
        }
    }
}

function processStateCommand($device_id,$command_name,$value, $title = '') {
    $command=SQLSelectOne("SELECT * FROM smartden_commands WHERE DEVICE_ID=".$device_id." AND SYSTEM LIKE '".$command_name."'");
    if ($title != '') {
        $command['TITLE']=$title;
    }
    $updated = 0;
    if ($command['VALUE']!=$value) {
        $command['UPDATED']=date('Y-m-d H:i:s');
        $updated = 1;
    }
    $command['VALUE']=$value;
    if (!$command['ID']) {
        $command['DEVICE_ID']=$device_id;
        $command['SYSTEM']=$command_name;
        SQLInsert('smartden_commands',$command);
    } else {
        SQLUpdate('smartden_commands',$command);
    }

    if ($command['LINKED_OBJECT'] && $command['LINKED_PROPERTY']) {
        sg($command['LINKED_OBJECT'].'.'.$command['LINKED_PROPERTY'],$value);
    }
    if ($command['LINKED_OBJECT'] && $command['LINKED_METHOD'] && $updated) {
        $params=array('VALUE'=>$value);
        callMethodSafe($command['LINKED_OBJECT'].'.'.$command['LINKED_METHOD'],$params);
    }
}

/**
* Run
*
* Description
*
* @access public
*/
function run() {
 global $session;
  $out=array();
  if ($this->action=='admin') {
   $this->admin($out);
  } else {
   $this->usual($out);
  }
  if (IsSet($this->owner->action)) {
   $out['PARENT_ACTION']=$this->owner->action;
  }
  if (IsSet($this->owner->name)) {
   $out['PARENT_NAME']=$this->owner->name;
  }
  $out['VIEW_MODE']=$this->view_mode;
  $out['EDIT_MODE']=$this->edit_mode;
  $out['MODE']=$this->mode;
  $out['ACTION']=$this->action;
  $out['DATA_SOURCE']=$this->data_source;
  $out['TAB']=$this->tab;
  $this->data=$out;
  $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
  $this->result=$p->result;
}
/**
* BackEnd
*
* Module backend
*
* @access public
*/
function admin(&$out) {
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='smartden_devices' || $this->data_source=='') {
  if ($this->view_mode=='' || $this->view_mode=='search_smartden_devices') {
   $this->search_smartden_devices($out);
  }
  if ($this->view_mode=='edit_smartden_devices') {
   $this->edit_smartden_devices($out, $this->id);
  }
  if ($this->view_mode=='delete_smartden_devices') {
   $this->delete_smartden_devices($this->id);
   $this->redirect("?data_source=smartden_devices");
  }
 }
 if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
  $out['SET_DATASOURCE']=1;
 }
 if ($this->data_source=='smartden_commands') {
  if ($this->view_mode=='' || $this->view_mode=='search_smartden_commands') {
   $this->search_smartden_commands($out);
  }
  if ($this->view_mode=='edit_smartden_commands') {
   $this->edit_smartden_commands($out, $this->id);
  }
 }
}
/**
* FrontEnd
*
* Module frontend
*
* @access public
*/
function usual(&$out) {
 $this->admin($out);
}
/**
* smartden_devices search
*
* @access public
*/
 function search_smartden_devices(&$out) {
  require(DIR_MODULES.$this->name.'/smartden_devices_search.inc.php');
 }
/**
* smartden_devices edit/add
*
* @access public
*/
 function edit_smartden_devices(&$out, $id) {
  require(DIR_MODULES.$this->name.'/smartden_devices_edit.inc.php');
 }
/**
* smartden_devices delete record
*
* @access public
*/
 function delete_smartden_devices($id) {
  $rec=SQLSelectOne("SELECT * FROM smartden_devices WHERE ID='$id'");
  // some action for related tables
  SQLExec("DELETE FROM smartden_devices WHERE ID='".$rec['ID']."'");
 }
/**
* smartden_commands search
*
* @access public
*/
 function search_smartden_commands(&$out) {
  require(DIR_MODULES.$this->name.'/smartden_commands_search.inc.php');
 }
/**
* smartden_commands edit/add
*
* @access public
*/
 function edit_smartden_commands(&$out, $id) {
  require(DIR_MODULES.$this->name.'/smartden_commands_edit.inc.php');
 }

 function sendApiCommand($device_id,$command_name,$value) {
     $device=SQLSelectOne("SELECT * FROM smartden_devices WHERE ID=".$device_id);
     if ($device['DEVICE_TYPE']=='ip16r') {
         $res=getURL('http://'.$device['IP'].'/current_state.xml?pw='.$device['PASSWORD'].'&'.$command_name.'='.$value);
         $this->processStateXML($device['ID'],$device['DEVICE_TYPE'],$res);
     }
 }

 function propertySetHandle($object, $property, $value) {
   $properties=SQLSelect("SELECT smartden_commands.* FROM smartden_commands WHERE smartden_commands.LINKED_OBJECT LIKE '".DBSafe($object)."' AND smartden_commands.LINKED_PROPERTY LIKE '".DBSafe($property)."'");
   $total=count($properties);
   if ($total) {
    for($i=0;$i<$total;$i++) {
        $properties[$i]['VALUE']=$value;
        SQLUpdate('smartden_commands',$properties[$i]);
        $result = $this->sendApiCommand($properties[$i]['DEVICE_ID'],$properties[$i]['SYSTEM'],$value);
    }
   }
 }
/**
* Install
*
* Module installation routine
*
* @access private
*/
 function install($data='') {
  parent::install();
 }
/**
* Uninstall
*
* Module uninstall routine
*
* @access public
*/
 function uninstall() {
  SQLExec('DROP TABLE IF EXISTS smartden_devices');
  SQLExec('DROP TABLE IF EXISTS smartden_commands');
  parent::uninstall();
 }
/**
* dbInstall
*
* Database installation routine
*
* @access private
*/
 function dbInstall($data = '') {
/*
smartden_devices - 
smartden_commands - 
*/
  $data = <<<EOD
 smartden_devices: ID int(10) unsigned NOT NULL auto_increment
 smartden_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 smartden_devices: IP varchar(255) NOT NULL DEFAULT ''
 smartden_devices: PASSWORD varchar(255) NOT NULL DEFAULT ''
 smartden_devices: DEVICE_TYPE varchar(255) NOT NULL DEFAULT ''
 
 smartden_commands: ID int(10) unsigned NOT NULL auto_increment
 smartden_commands: DEVICE_ID int(10) NOT NULL DEFAULT '0' 
 smartden_commands: SYSTEM varchar(100) NOT NULL DEFAULT ''
 smartden_commands: TITLE varchar(100) NOT NULL DEFAULT ''
 smartden_commands: VALUE varchar(255) NOT NULL DEFAULT ''
 smartden_commands: LINKED_OBJECT varchar(100) NOT NULL DEFAULT ''
 smartden_commands: LINKED_PROPERTY varchar(100) NOT NULL DEFAULT ''
 smartden_commands: LINKED_METHOD varchar(100) NOT NULL DEFAULT ''
 smartden_commands: UPDATED datetime
EOD;
  parent::dbInstall($data);
 }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgT2N0IDE5LCAyMDE3IHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
