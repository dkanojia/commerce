<?php 

 namespace xepan\commerce;

 class Model_Item extends \xepan\hr\Model_Document{
	public $status = ['Draft','Submitted','Published'];
	
	// draft
		// Item are not published or is_party published off
	//submitted 
		//item status unpublished and and is_paty published
	//published 
		// Item is published true

	public $actions = [
					'Draft'=>['view','edit','delete','submit'],
					'Submitted'=>['view','edit','delete','published','reject'],
					'Reject'=>['view','edit','delete','submit'],
					'Published'=>['view','edit','delete']
					];

	function init(){
		parent::init();

		$item_j=$this->join('item.document_id');

		$item_j->hasOne('xepan\base\Contact','designer_id');

		$item_j->addField('name')->mandatory(true);
		$item_j->addField('sku')->PlaceHolder('Insert Unique Referance Code')->caption('Code')->hint('Insert Unique Referance Code')->mandatory(true);
		$item_j->addField('display_sequence')->hint('descending wise sorting');
		$item_j->addField('description')->type('text')->display(array('form'=>'xepan\base\RichText'));
		
		$item_j->addField('original_price')->type('money')->mandatory(true);
		$item_j->addField('sale_price')->type('money')->mandatory(true);
		

		
		$item_j->addField('expiry_date')->type('date');
		
		$item_j->addField('stock_availability')->type('boolean');
		
		$item_j->addField('minimum_order_qty')->type('int');
		$item_j->addField('maximum_order_qty')->type('int');
		$item_j->addField('qty_unit');
		$item_j->addField('qty_from_set_only')->type('boolean');
		
		//Item Allow Optins
		$item_j->addField('is_party_publish')->type('boolean')->hint('Freelancer Item Design/Template to be Approved');
		$item_j->addField('is_saleable')->type('boolean')->hint('Make Item Becomes Saleable');
		$item_j->addField('is_allowuploadable')->type('boolean')->hint('on website customer can upload a degin for designable item');
		$item_j->addField('is_purchasable')->type('boolean')->hint('item display only at purchase Order/Invoice');
		//Item Stock Options
		$item_j->addField('maintain_inventory')->type('boolean')->hint('Manage Inventory ');
		$item_j->addField('allow_negative_stock')->type('boolean')->hint('show item on wensite apart from stock is available or not');
		$item_j->addField('negative_qty_allowed')->type('number')->hint('allow the negative stock until this quantity');
		$item_j->addField('is_visible_sold')->type('boolean')->hint('display item on website after out of stock/all sold');
		
		$item_j->addField('is_servicable')->type('boolean');
		$item_j->addField('is_productionable')->type('boolean')->hint('used in Production');
		$item_j->addField('website_display')->type('boolean')->hint('Show on Website');
		$item_j->addField('is_downloadable')->type('boolean');
		$item_j->addField('is_rentable')->type('boolean');
		$item_j->addField('is_designable')->type('boolean')->hint('item become designable and customer customize the design');
		$item_j->addField('is_template')->type('boolean')->hint('blueprint/layout of designable item');
		$item_j->addField('is_attachment_allow')->type('boolean')->hint('by this option you can attach the item information pdf/doc etc. to be available on website');
		
		$item_j->addField('warranty_days')->type('int');
		
		//Item Display Options
		$item_j->addField('show_detail')->type('boolean');
		$item_j->addField('show_price')->type('boolean');

		//Marked
		$item_j->addField('is_new')->type('boolean')->caption('New');
		$item_j->addField('is_feature')->type('boolean')->caption('Featured');
		$item_j->addField('is_mostviewed')->type('boolean')->caption('Most Viewed');

		//Enquiry Send To
		$item_j->addField('is_enquiry_allow')->type('boolean')->hint('display enquiry form at item detail on website');
		$item_j->addField('enquiry_send_to_admin')->type('boolean')->hint('send a copy of enquiry form to admin');
		$item_j->addField('item_enquiry_auto_reply')->caption('Item Enquiry Auto Reply')->type('boolean');
		
		//Item Comment Options
		$item_j->addField('is_comment_allow')->type('boolean');
		$item_j->addField('comment_api')->setValueList(array('disqus'=>'Disqus'));

		//Item Other Options
		$item_j->addField('add_custom_button')->type('boolean');
		$item_j->addField('custom_button_label');
		$item_j->addField('custom_button_url')->placeHolder('subpage name like registration etc.');
		
		// Item WaterMark
		// $item_j->add('filestore/Field_Image','watermark_image_id');
		$item_j->addField('watermark_text')->type('text');
		$item_j->addField('watermark_position')->enum(array('TopLeft','TopRight','BottomLeft','BottomRight','Center','Left Diagonal','Right Diagonal'));
		$item_j->addField('watermark_opacity');
		
		//Item SEO
		$item_j->addField('meta_title');
		$item_j->addField('meta_description')->type('text');
		$item_j->addField('tags')->type('text')->PlaceHolder('Comma Separated Value');

		//Item Designs
		$item_j->addField('designs')->type('text')->hint('used for internal, design saved');

		//others
		$item_j->addField('terms_and_conditions')->type('text');
		$item_j->addField('duplicate_from_item_id')->hint('internal used saved its parent');

		$this->addCondition('type','Item');

		$this->getElement('status')->defaultValue('Draft');
		// $item_j->addExpression('total_sale')->set(" 'TODO' ");

		//Quantity set condition just for relation
		$item_j->hasMany('xepan\commerce\Item_Quantity_Set','item_id');
		$item_j->hasMany('xepan\commerce\Item_CustomField_Association','item_id');
		$item_j->hasMany('xepan\commerce\Item_Department_Association','item_id',null);
		//Category Item Association
		$item_j->hasMany('xepan\commerce\CategoryItemAssociation','item_id');
		//Member Design
		$item_j->hasMany('xepan\commerce\Item_Template_Design','item_id');


	}


	function submit(){
		$this['status']='Draft';
		$this->saveAndUnload();
	}

	
	function published(){
		$this['status']='Submitted';
		$this->saveAndUnload();
	}

	function associateSpecification(){
		if(!$this->loaded())
			throw new \Exception("Model Must Loaded");
			
		$asso = $this->add('xepan\commerce\Model_Item_CustomField_Association')
				->addCondition('item_id',$this->id)
				->addCondition('can_effect_stock',false)
				;
		$asso->addExpression('customfield_type')->set($asso->refSQL('customfield_generic_id')->fieldQuery('type'));
		$asso->addCondition('customfield_type','Specification');
		$asso->tryLoadAny();
		return $asso;

	}

	function associateCustomField(){
		if(!$this->loaded())
			throw new \Exception("Model Must Loaded");
			
		$asso = $this->add('xepan\commerce\Model_Item_CustomField_Association')
				->addCondition('item_id',$this->id)
				;

		$asso->addExpression('customfield_type')->set($asso->refSQL('customfield_generic_id')->fieldQuery('type'));
		$asso->addCondition('customfield_type','CustomField');
		$asso->tryLoadAny();
		
		return $asso;		
	}

	function getAssociatedCategories(){

		$associated_categories = $this->ref('xepan\commerce\CategoryItemAssociation')
								->_dsql()->del('fields')->field('category_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_categories)),false);
	}

	function getAssociatedDepartment(){
		$associated_departments = $this->ref('xepan\commerce\Item_Department_Association')
								->_dsql()->del('fields')->field('department_id')->getAll();
		return iterator_to_array(new \RecursiveIteratorIterator(new \RecursiveArrayIterator($associated_departments)),false);
	}

	function stockEffectCustomFields(){
		if(!$this->loaded())
			throw new \Exception("Item Model Must Loaded before getting stockeffectcustomfields");
		
		$stock_effect_cf = $this->add('xepan\commerce\Model_Item_CustomField_Association')
		->addCondition('item_id',$this->id)
		->addCondition('can_effect_stock',true)
		->tryLoadAny()
		;

		return $stock_effect_cf;
	}

	function specification($specification=null){
		if(!$this->loaded())
			throw new \Exception("Model must loaded", 1);

		$specs_assos = $this->add('xepan\commerce\Model_Item_CustomField_Association')->addCondition('item_id',$this->id);
		$specs_assos->addExpression('value')->set(function($m,$q){
			return $m->refSQL('xepan\commerce\Item_CustomField_Value')->addCondition('status','Active')->setLimit(1)->fieldQuery('name');
		});

		
		if($specification){
			$specs_assos->addCondition('name',$specification);
			$specs_assos->tryLoadAny();
			if($specs_assos->loaded()) return $specs_assos['value'];
			return false;
		}

		return $specs_assos;
	}

} 
 
	

