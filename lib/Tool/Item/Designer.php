<?php

namespace xepan\commerce;
class Tool_Item_Designer extends \xepan\cms\View_Tool{
	public $options = [];
	public $item=null;
	public $target=null;
	public $render_designer=true;
	public $designer_mode=false;
	public $load_designer_tool = true;
	public $specification=array('width'=>false,'height'=>false,'trim'=>false,'unit'=>false);

	function init(){
		parent::init();

		//Load Associate Designer Item
		$item_id = $this->api->stickyGET('item_member_design');
		$item = $this->item = $this->add('xepan\commerce\Model_Item')->load($item_id);

		$this->addClass('xshop-designer-tool xshop-item');		

		if(isset($this->api->xepan_xshopdesigner_included)){
			throw $this->exception('Designer Tool Cannot be included twise on same page','StopRender');
		}else{
			$this->api->xepan_xshopdesigner_included = true;
		}


		$designer = $this->add('xepan\commerce\Model_Contact');
		$designer_loaded = $designer->loadLoggedIn();
		
		// 3. Design own in-complete design again
		if($item_id and $designer_loaded){
			
			$target = $this->add('xepan\commerce\Model_Item_Template_Design')->tryLoad($item_id);
			if(!$target->loaded()) return;
			if($target['contcat_id'] != $designer->id){
				$target->unload();
				unset($target);	
			}else{
				$this->item = $item = $target->ref('item_id');
			}
		}

		
		// 1. Designer wants edit template
		if($item_id and $_GET['xsnb_design_template']=='true'  and $designer_loaded){
			$target = $this->item = $this->add('xepan\commerce\Model_Item')->tryLoad($item_id);
			if(!$target->loaded()){
				return;	
			} 
			$item = $target;

			if($target['designer_id'] != $designer->id){
				return;
			}
			$this->designer_mode = true;
		}

		// 2. New personalized item
		if($item_id and is_numeric($item_id) and $_GET['xsnb_design_template'] !='true' and !isset($target)){

			$this->item = $item = $this->add('xepan\commerce\Model_Item')->tryLoad($item_id);
			
			if(!$item->loaded()) {
				return;
			}

			$target = $this->add('xepan\commerce\Model_Item_Template_Design')->addCondition('item_id',$item->id);
			// $target = $item->ref('xepan\commerce\Item_Template_Design');
			$target['designs'] = $item['designs'];
		}


		
		if(!isset($target)){
			$this->render_designer = false;
			$this->add('View_Warning')->set('Insufficient Values, Item unknown or Not Authorised');
			$this->load_designer_tool = false;			
			return;
		}
		
		$this->target = $target;
		// check for required specifications like width / height
		if(!($this->specification['width'] = $item->specification('width')) OR !($this->specification['height'] = $item->specification('height')) OR !($this->specification['trim'] = $item->specification('trim'))){
			$this->add('View_Error')->set('Item Does not have \'width\' and/or \'height\' and/or \'trim\' specification(s) set');
			return;
		}else{
			// width and hirght might be like '51mm' and '91 mm' so get digit and unit sperated
			// print_r($this->specification);
			preg_match_all("/^([0-9]+)\s*([a-zA-Z]+)\s*$/", $this->specification['width'],$temp);
			$this->specification['width']= $temp[1][0];
			preg_match_all("/^([0-9]+)\s*([a-zA-Z]+)\s*$/", $this->specification['height'],$temp);
			$this->specification['height']= $temp[1][0];
			$this->specification['unit']=$temp[2][0];

			preg_match_all("/^([0-9]+)\s*([a-zA-Z]+)\s*$/", $this->specification['trim'],$temp);
			$this->specification['trim']= $temp[1][0];
		}
	}

	function render(){

		if($this->load_designer_tool){
		
		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/css/tool/designer/designer.css" />');
		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/css/tool/designer/flat_top_orange.css" />');
		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/css/tool/designer/jquery.colorpicker.css" />');
		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/css/tool/designer/cropper.css" />');
		$this->api->template->appendHTML('js_include','<link rel="stylesheet" type="text/css" href="'.$this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/css/tool/addtocart.css" />');
		// $this->api->jui->addStaticStyleSheet('addtocart');

		$this->js(true)
				->_load($this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/js/tool/designer/designer.js')
				->_load($this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/js/tool/designer/jquery.colorpicker.js')
				->_load($this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/js/tool/designer/cropper.js')
				->_load($this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/js/tool/designer/pace.js')
				->_load($this->api->url()->absolute()->getBaseURL().'vendor/xepan/commerce/templates/js/tool/addtocart.js')
				;
				
		// $this->js(true)->_load('item/addtocart');
		$design = json_decode($this->target['designs'],true);
		$selected_layouts_for_print = $design['selected_layouts_for_print']; // trimming other array values like px_width etc
		$design = $design['design']; // trimming other array values like px_width etc
		$design = json_encode($design);
		$cart_options = "{}";
		// $selected_layouts_for_print ="front_layout";
		$currency ="INR";
		
		$cart_options = $this->item->getBasicCartOptions();
		$cart_options['item_member_design'] = $_GET['item_member_design']?:'0';
		$cart_options['show_qty'] = '1'; // ?????????????  from options
		$cart_options['show_price'] = '1'; //$this->show_price;
		$cart_options['show_custom_fields'] = '1'; //$this->show_custom_fields;
		$cart_options['is_designable'] = $this->item['is_designable']; //$this->show_custom_fields;
				
		// echo "<pre>";
		// print_r ($design);
		// echo "</pre>";
		// exit;
			$this->js(true)->xepan_xshopdesigner(array('width'=>$this->specification['width'],
														'height'=>$this->specification['height'],
														'trim'=>$this->specification['trim'],
														'unit'=>'mm',
														'designer_mode'=> $this->designer_mode,
														'design'=>$design,
														'show_cart'=>'1',
														'cart_options' => $cart_options,
														'selected_layouts_for_print' => $selected_layouts_for_print,
														'item_id'=>$_GET['xsnb_design_item_id'],
														'item_member_design_id' => $_GET['item_member_design_id'],
														'item_name' => $this->item['name'] ." ( ".$this->item['sku']." ) ",
														'item_sale_price'=>$this->item['sale_price'],
														'item_original_price'=>$this->item['original_price'],
														'currency_symbole'=>$currency,
														'base_url'=>$this->api->url()->absolute()->getBaseURL()
												));
			
		}
		parent::render();
	}

}