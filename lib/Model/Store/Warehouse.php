<?php
namespace xepan\commerce;
class Model_Store_Warehouse extends \xepan\base\Model_Contact{
	// public $table="store_warehouse";
	public $acl=false;

	function init(){
		parent::init();

		$this->addCondition('type','Warehouse');

		$this->hasMany('xepan\commerce\Store_Transaction','from_warehouse_id',null,'FromTransactions');
		$this->hasMany('xepan\commerce\Store_Transaction','to_warehouse_id',null,'ToTransactions');
	}


	function newTransaction($related_document,$related_document_type){

		$m = $this->add('xepan\commerce\Model_Store_Transaction');
		$m['document_type'] = $related_document_type;
		$m['from_warehouse_id'] = $related_document['contact_id'];
		$m['to_warehouse_id'] = $this->id;
		$m['related_document_id']=$related_document->id;	
		$m->save();
		return $m;
	}

}