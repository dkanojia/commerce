<?php

namespace xepan\commerce;

class page_store_dispatchrequest extends \xepan\base\Page{
	public $title="Dispatch Request Item";
	function init(){
		parent::init();

		$dispatch = $this->add('xepan\commerce\Model_Store_DispatchRequest');
		$dispatch->setOrder('id','desc');
		$dispatch->setOrder('related_document_id','desc');
		$c = $this->add('xepan\hr\CRUD',null,null,['view/store/dispatch-request-grid']);
		$c->setModel($dispatch);
	}
}