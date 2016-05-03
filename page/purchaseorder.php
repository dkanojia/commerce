<?php 
 namespace xepan\commerce;
 class page_purchaseorder extends \Page{

	public $title='Purchase Order';

	function init(){
		parent::init();

		$purchaseorder = $this->add('xepan\commerce\Model_PurchaseOrder');
		$purchaseorder->add('xepan\commerce\Controller_SideBarStatusFilter');

		$purchaseorder->add('misc/Field_Callback','net_amount_client_currency')->set(function($m){
			return $m['exchange_rate'] == '1'? "": ($m['net_amount'].' '. $m['currency']);
		});

		$purchaseorder->addExpression('contact_type',$purchaseorder->refSQL('contact_id')->fieldQuery('type'));

		$crud=$this->add('xepan\hr\CRUD',
						['action_page'=>'xepan_commerce_purchaseorderdetail']
						,null,
						['view/order/purchase/grid']);

		$crud->grid->addHook('formatRow',function($g){
			$g->current_row['contact_url']= $g->model['contact_type'];
		});

		$crud->setModel($purchaseorder)->setOrder('created_at','desc');
		$crud->grid->addPaginator(50);
		$frm=$crud->grid->addQuickSearch(['contact','document_no']);
		
		$crud->add('xepan\base\Controller_Avatar',['name_field'=>'contact']);
		
	}
} 