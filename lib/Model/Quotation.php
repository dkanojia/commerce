<?php

namespace xepan\commerce;

class Model_Quotation extends \xepan\commerce\Model_Document{
	public $status = ['Draft','Submitted','Approved','Redesign','Rejected','Converted'];
	public $actions = [
					'Draft'=>['view','edit','delete','submit'],
					'Submitted'=>['view','edit','delete','approve','redesign','reject'],
					'Approved'=>['view','edit','delete','redesign','reject','send'],
					'Redesign'=>['view','edit','delete','submit','reject'],
					'Rejected'=>['view','edit','delete'],
					'Converted'=>['view','edit','delete','send']
					];

	function init(){
		parent::init();

		$quotation_j = $this->join('quotation.document_id');

		$quotation_j->hasOne('xepan\base\Contact','contact_id');
		$quotation_j->hasOne('xepan\commerce\TNC','tnc_id');

		//$quotation_j->hasOne('xepan\commerce\Currency','currency_id');
		$quotation_j->addField('name')->Caption('Quotation Number')->hint('For Autogenerated, Leave Empty');


		$quotation_j->addField('narration')->type('text');
		$quotation_j->addField('total_amount')->type('money');
		$quotation_j->addField('tax')->type('money');
		$quotation_j->addField('discount_voucher_amount');
		$quotation_j->addField('gross_amount')->type('money');
		$quotation_j->addField('net_amount')->type('money')->mandatory(true);

		$this->addCondition('type','quotation');

	}
}
