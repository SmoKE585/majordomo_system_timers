<?php
class system_timers extends module {
  function __construct() {
    $this->name="system_timers";
    $this->title="Системные таймеры";
    $this->module_category="<#LANG_SECTION_SYSTEM#>";
    $this->version="1.3";
    $this->checkInstalled();
  }

  function saveParams($data=1) {
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
    if (IsSet($this->tab)) {
		$p["tab"]=$this->tab;
    }
    return parent::saveParams($p);
  }

  function getParams() {
    global $id;
    global $mode;
    global $view_mode;
    global $edit_mode;
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
    if (isset($tab)) {
		$this->tab=$tab;
    }
  }

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
    $this->data=$out;
    $p=new parser(DIR_TEMPLATES.$this->name."/".$this->name.".html", $this->data, $this);
    $this->result=$p->result;
  }

	function admin(&$out) {
		$this->getConfig();

		//Выгрузим таймеры
		$timers = SQLSelect("SELECT * FROM `jobs` ORDER BY ID");
		$out['ALLTIMERS'] = $timers;
		//Добавим в вывод время до сработки
		foreach($timers as $key => $value) {
			$out['ALLTIMERS'][$key]['TIMEREXISTS'] = $this->timerExists($timers[$key]['ID']);
		}
		
		$out['VERSION_MODULE'] = $this->version;
	}

	function usual(&$out) {
		$this->admin($out);
		global $type;
		global $timerid;
		
		$this->type = $_GET['type'];
		$this->timerName = $_GET['timerName'];
		
		if($this->type == 'ajax' && !empty($this->timerName)) {
			$getTimerID = SQLSelectOne("SELECT ID FROM jobs WHERE TITLE='".dbSafe($this->timerName)."'");
			$out['TIMEREXISTS_AJAX'] = $this->timerExists($getTimerID['ID']);
		}
		
		if(!empty($this->mode)) {
			$timer_job = SQLSelectOne("SELECT ID,TITLE FROM jobs WHERE TITLE='".dbSafe($this->mode)."'");
			$diff = $this->timerExists($timer_job['ID']);
			$out['TIMEREXISTS'] = $diff;
			$out['TIMEREXISTS_ID'] = $timer_job['ID'];
			$out['TIMEREXISTS_NAME'] = $timer_job['TITLE'];

			if(!empty($this->view_mode)) {
				$params = explode(';', $this->view_mode);
				foreach($params as $key => $value) {
					$params_get = explode('=', $value);
					$out[mb_strtoupper('SET_'.$params_get[0])] = $params_get[1];
				}
			}
		}
	}

	function timerExists($timerID) {
		if($timerID) {
			$timer_job = SQLSelectOne("SELECT UNIX_TIMESTAMP(RUNTIME) as TM FROM jobs WHERE ID='".$timerID."'");
			$diff = (int) $timer_job['TM']-time();
			if($diff < 0) $diff = 0;
			return $diff;
		} else {
			return 'Таймер сейчас не активен';
		}
	}

	function processCycle() {
		$this->getConfig();
	}

	function install($data='') {
		parent::install();
	}
}
