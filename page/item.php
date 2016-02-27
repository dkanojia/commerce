<?php 
 namespace xepan\commerce;
 class page_item extends \Page{

	public $title='Items';

	function init(){
		parent::init();

		$item=$this->add('xepan\commerce\Model_Item');

		$crud=$this->add('xepan\hr\CRUD',['action_page'=>'xepan_commerce_itemdetail'],null,['view/item/grid']);

		$crud->setModel($item);
		$crud->grid->addQuickSearch(['name']);
	}

}  