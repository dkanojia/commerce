<?php
namespace xepan\commerce;

class Model_Store_DispatchRequest extends \xepan\commerce\Model_Store_Transaction{
	
	function init(){
		parent::init();
		
		$this->addCondition('type','Dispatch');
	}

	function receive(){
		$tra_row=$this->add('xepan\commerce\Model_Store_TransactionRow');
		$tra_row->addCondition('store_transaction_id',$this->id);
		$tra_row->tryLoadAny();
		// throw new \Exception($tra_row->id, 1);
		
		$job_detail=$this->add('xepan\production\Model_Jobcard_Detail');
		$job_detail->addCondition('id',$tra_row['jobcard_detail_id']);
		$job_detail->tryLoadAny();

		if($job_detail->loaded()){
			$job_detail['status']="Completed";
			$job_detail->save();

			$this['status']="Received";
			$this->save();
			return true;
		}else{
			throw new \Exception("Model Job Detail Must be Loaded", 1);
		}
		
	}
}