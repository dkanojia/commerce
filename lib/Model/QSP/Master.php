<?php

namespace xepan\commerce;

class Model_QSP_Master extends \xepan\hr\Model_Document{

public $status = ['Draft','Submitted','Approved','Redesign','Rejected','Converted'];
public $actions = [
				'Draft'=>['view','edit','delete','submit','manage_attachments'],
				'Submitted'=>['view','edit','delete','redesign','reject','approve'],
				'Approved'=>['view','edit','delete','redesign','reject','send'],
				'Redesign'=>['view','edit','delete','submit','reject'],
				'Rejected'=>['view','edit','delete'],
				'Converted'=>['view','edit','delete','send']
				];
public $acl=false;				

	function init(){
		parent::init();

		$qsp_master_j = $this->join('qsp_master.document_id');
		$qsp_master_j->hasOne('xepan/base/Contact','contact_id');
		$qsp_master_j->hasOne('xepan/commerce/Currency','currency_id');
		$qsp_master_j->hasOne('xepan/commerce/TNC','tnc_id');

		$qsp_master_j->addField('document_no');

		$qsp_master_j->addField('billing_landmark');
		$qsp_master_j->addField('billing_address');
		$qsp_master_j->addField('billing_city');
		$qsp_master_j->addField('billing_state');
		$qsp_master_j->addField('billing_country');
		$qsp_master_j->addField('billing_pincode');
		$qsp_master_j->addField('billing_tel');
		$qsp_master_j->addField('billing_email');

		$qsp_master_j->addField('shipping_landmark');
		$qsp_master_j->addField('shipping_address');
		$qsp_master_j->addField('shipping_city');
		$qsp_master_j->addField('shipping_state');
		$qsp_master_j->addField('shipping_country');
		$qsp_master_j->addField('shipping_pincode');
		$qsp_master_j->addField('shipping_tel');
		$qsp_master_j->addField('shipping_email');
		
		$qsp_master_j->addField('gross_amount'); //Total Item amount Sum
		$qsp_master_j->addField('discount_amount'); 

		$qsp_master_j->addField('net_amount'); // Total amount - discount_amount
		// $qsp_master_j->addField('tax');
		// $qsp_master_j->addField('total_amount');
		
		$qsp_master_j->addField('due_date')->type('datetime');
		$qsp_master_j->addField('priority_id');
		$qsp_master_j->addField('narration')->type('text');

		$qsp_master_j->addField('exchange_rate')->defaultValue(1);		
		$qsp_master_j->addField('tnc_text')->type('text');		

		//used for the Invoice only
		$qsp_master_j->addField('payment_gateway_id');		
		$qsp_master_j->addField('transaction_reference');
		$qsp_master_j->addField('transaction_response_data');

		$this->getElement('status')->defaultValue('Draft');

		$qsp_master_j->hasMany('xepan/commerce/QSP_Detail','qsp_master_id',null,'Details');

		$this->addHook('beforeDelete',[$this,'deleteDetails']);

		$this->addHook('beforeSave',[$this,'updateTnCTextifChanged']);

		$this->is([
			'document_no|required|number',
			'contact_id|required',
			'exchange_rate|number|gt|0'
			]);
	}

	function updateTnCTextifChanged(){
		if($this->isDirty('tnc_id')){
			$this['tnc_text'] = $this->ref('tnc_id')->get('content');
		}
	}

	function deleteDetails(){
		$this->ref('Details')->deleteAll();
	}

} 