<?php 
 namespace xepan\commerce;
 class page_discountvoucher extends \Page{

	public $title='Discount Vouchers';

	function init(){
		parent::init();

		$discount = $this->add('xepan\commerce\Model_DiscountVoucher');
		$crud=$this->add('xepan\hr\CRUD',null,
						null,
						['view/discount/vouchers/grid']
					);

		$crud->setModel($discount);
		$crud->grid->addQuickSearch(['name']);
		$crud->grid->addPaginator(25);

		
	}

}  