<?php

namespace xepan\commerce;

class Tool_ItemList extends \xepan\cms\View_Tool{
	public $options = [

					'show_name'=>true,
					'show_sku'=>true,/* true, false*/
			 		'sku'=>"Not To Show",
					'show_sale_price'=>true,/* true, false*/
					'show_original_price'=>true,/* true, false*/
					'show_description'=>true, /*true, false*/ 
					'description'=>"Not Available",
					'show_tags'=>true,/* true, false*/ 
					'tags'=>"Not Tag Yet",
					'show_Specification',
					'show_customfield_type'=>true,
					'show_qty_unit'=>true,
					'show_stock_availability'=>true,
					'show_is_enquiry_allow'=>true,
					'show_is_mostviewed'=>true,
					'show_is_new'=>true,
					'show_is_feature'=>true


				];

	function init(){
		parent::init();

		$item = $this->add('xepan\commerce\Model_Item');
		$item->addExpression('title_image')->set(function($m,$q){
			return $m->refSQL('Attachments')->setOrder('id','desc')->setLimit(1)->fieldQuery('file');
		});

		$this->add('CompleteLister',null,null,['view/tool/item_grid'])->setModel($item);
		// $this->add('CompleteLister',null,null,['view/tool/item_list'])->setModel($item);

		
	}

}