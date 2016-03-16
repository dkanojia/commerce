<?php

namespace xepan\commerce;

class Tool_ItemList extends \xepan\cms\View_Tool{
	public $options = [

					'show_name'=>true,
					'show_sku'=>true,/* true, false*/
			 		'sku'=>"%",
					'show_sale_price'=>true,/* true, false*/
					'show_original_price'=>true,/* true, false*/
					'show_description'=>true, /*true, false*/ 
					'description'=>"Not Available",
					'show_tags'=>true,/* true, false*/ 
					'tags'=>"Not Taged Yet",
					'show_Specification',
					'show_customfield_type'=>true,
					'show_qty_unit'=>true,
					'show_stock_availability'=>true,
					'show_is_enquiry_allow'=>true,
					'show_is_mostviewed'=>true,
					'show_item_layout'=>'item_grid'
					// 'show_order'=>


				];

	function init(){
		parent::init();

		$item = $this->add('xepan\commerce\Model_Item');
		$item->addExpression('title_image')->set(function($m,$q){
			return $m->refSQL('Attachments')->setOrder('id','desc')->setLimit(1)->fieldQuery('file');
		});

		$cl = $this->add('CompleteLister',null,null,['view/tool/'.$this->options['show_item_layout']]);
		$cl->setModel($item);
		$cl->add('xepan\cms\Controller_Tool_Optionhelper',['options'=>$this->options,'model'=>$item]);

		
	}

	function render(){

		$this->js(true)->_load('tool/jquery-elevatezoom')
					->_load('tool/jquery.fancybox');
		parent::render();

	}


	function addToolCondition_is_new($model){
		$model->getElement('is_new')->destroy();
	}

	function addToolCondition_is_feature($model){
		$model->getElement('is_feature')->destroy();
	}

	function addToolCondition_is_mostviewed($model){
		$model->getElement('is_mostviewed')->destroy();
	}

	function addToolCondition_specification($model){
		$model->getElement('Specification')->destroy();
	}

}