<?php

namespace xepan\commerce;

class Tool_Item_AddToCartButton extends \View{
	public $options=["show_multi_step_form"=>false];
	public $item_member_design;
	function init(){
		parent::init();

		$this->form = $form = $this->add('Form',null,null,['form/stacked']);
		
	}

	function setModel($model){
		
		$form = $this->form;

		$custom_fields = $model->activeAssociateCustomField();
		$custom_fields->setOrder('order','asc');

		$groups = [];
		//Populating custom fields
		$count = 1;
		foreach ($custom_fields as $custom_field) {

			if(!isset($groups[$custom_field['group']])){
				$fieldset = $groups[$custom_field['group']] = $form->add('HtmlElement')->setElement('fieldset');
				$fieldset->add('HtmlElement')->setElement('legend')->set($custom_field['group']);
			}

			$fieldset = $groups[$custom_field['group']];

			if(strtolower($custom_field['display_type']) === "dropdown" ){
				$field = $fieldset->addField('xepan\commerce\DropDown',$count,$custom_field['name']);
				$field->setModel($this->add('xepan\commerce\Model_Item_CustomField_Value',['id_field'=>'name'])->addCondition('customfield_association_id',$custom_field->id));
				
				$field->addClass("required");
			}else if(strtolower($custom_field['display_type']) === 'color'){
				$field = $fieldset->addField('xepan\commerce\DropDown',$count,$custom_field['name']);
				$field->setModel($this->add('xepan\commerce\Model_Item_CustomField_Value',['id_field'=>'name'])->addCondition('customfield_association_id',$custom_field->id));
				
			}else if(strtolower($custom_field['display_type']) === "line"){
				$field = $fieldset->addField('Line',$count,$custom_field['name']);
				
			}

			$count++;
		}

		// add Quantity Set in respective group
		$fieldset = $groups[$model['quantity_group']];
		if(!isset($fieldset)){
			// $fieldset = $form;
			$fieldset = $form->add('HtmlElement')->setElement('fieldset');
			$fieldset->add('HtmlElement')->setElement('legend')->set($model['quantity_group']);
		}

		if($model['qty_from_set_only']){
			$qty_set_model = $this->add('xepan\commerce\Model_Item_Quantity_Set',['id_field'=>'qty']);
			$qty_set_model->addCondition('item_id',$model->id);
			$qty_set_model->setOrder('qty','asc');
			$qty_set_model->_dsql()->group('name');
			$field_qty = $fieldset->addField('xepan\commerce\DropDown','qty')->setModel($qty_set_model);
		}else
			$field_qty = $fieldset->addField('Number','qty')->set(1);

		// add File Upload into respective groups

		if($model['is_allowuploadable'] and $model['upload_file_label']){

			$images_count = 0;
			$upload_array = explode(',', $model['upload_file_label']);
			$images_count = count($upload_array);
			if(!$images_count)
				return;

			$fieldset = $groups[$model['upload_file_group']];
			if(!isset($fieldset)){
				$fieldset = $form->add('HtmlElement')->setElement('fieldset');
				$fieldset->add('HtmlElement')->setElement('legend')->set($model['upload_file_group']);
			}

			foreach ($upload_array as $field_label) {
				$field_name = preg_replace('/\s+/', '', $field_label);

				$multi_upload_field = $fieldset->addField('xepan\base\Upload',$field_name)
						->allowMultiple(1)
						->setFormatFilesTemplate('view/tool/xepan_commerce_file_upload');
				$multi_upload_field->setAttr('accept','.jpeg,.png,.jpg');
				$multi_upload_field->setModel('xepan\filestore\Image');
				$multi_upload_field->addClass('required');
			}
		}

		//submit button
		$addtocart_btn = $form->addSubmit($this->options['button_name']?:'Add To Cart')->addClass('btn-block btn btn-primary');
		$getprice_btn = $form->addSubmit('get price')->addStyle('display','none')->addClass('btn-block btn btn-primary');
		
		if(!$this->options['show_addtocart_button'])
			$addtocart_btn->addStyle('display','none');
		//change event handeling
		$form->on('change','select, input:not([type="file"])',$form->js()->submit());
		// $fields_qty->js('change',$getprice_btn->js(true)->trigger('click'));
		// $field_qty->js('change',$form->js()->submit());

		if($form->isSubmitted()){			
			//get price according to selected custom field
			// $custom_field_array = [];
			$department_custom_field = [];
			$count = 1;
			foreach ($custom_fields as $custom_field) {
				// $custom_field_array[$custom_field['name']] = $form[$count];

				$department_id = $custom_field['department_id']?:0;

				if(!isset($department_custom_field[$department_id]))
					$department_custom_field[$department_id] = ['department_name'=>$custom_field['department']];

				if(!isset($department_custom_field[$department_id][$custom_field['customfield_generic_id']])){
					$value_id = $this->add('xepan\commerce\Model_Item_CustomField_Value')
									->addCondition('customfield_association_id',$custom_field->id)
									->addCondition('name',$form[$count])
									->tryLoadAny()->id;
					$temp = [
						"custom_field_name"=>$custom_field['name'],
						"custom_field_value_id"=>$value_id?$value_id:$form[$count],
						"custom_field_value_name"=>$form[$count],
						];
					$department_custom_field[$department_id][$custom_field['customfield_generic_id']] = $temp;
				}
				
				$count++;
			}
			
			//populate price according to selected customfield
			$price_array = $model->getAmount($department_custom_field,$form['qty']);			
			//
			if($form->isClicked($addtocart_btn)){
				if(!$this->item_member_design)
					$this->item_member_design = 0;

				//selected custom field options array
				$other_fields=null;
				$file_upload_id=0;

				// Custom Field Uploaded Image management
				$upload_images_array = [];
				if(isset($upload_array)){
					foreach ($upload_array as $field_label) {
						$field_name = preg_replace('/\s+/', '', $field_label);
						// field error if is not mandatory
						if(!$form[$field_name])
							$form->error($field_name,'mandatory');
						
						$upload_images_array[] = $form[$field_name];
					}
				}

				$cart = $this->add('xepan\commerce\Model_Cart');
				$cart->addItem($model->id,$form['qty'],$this->item_member_design,$department_custom_field,$upload_images_array);
				$js = [
						$form->js()->_selector('.xepan-commerce-tool-cart')->trigger('reload'),
					];
				$form->js(null,$js)->univ()->successMessage('Added to cart ' . $model['name'])->execute();
				// $form->js(null,$js)->execute();
			}else{

				//shipping price added on item amount if option setted from item list options
				if($this->options['show_shipping_charge'] and $this->options['shipping_charge_with_item_amount']){
					$price_array['sale_amount'] = $price_array['sale_amount'] + $price_array['shipping_charge'];
					$price_array['original_amount'] = $price_array['original_amount'] + $price_array['shipping_charge'];
				}

				$js = [
						$form->js()->closest('.xshop-item')->find('.xepan-commerce-tool-item-sale-price')->html($price_array['sale_amount']),
						$form->js()->closest('.xshop-item')->find('.xepan-commerce-tool-item-original-price')->html($price_array['original_amount']),
						$form->js()->closest('.xshop-item')->find('.xepan-commerce-tool-item-shipping-charge')->html($price_array['shipping_charge']),
						$form->js()->_selector('.xepan-commerce-item-image')
								->reload(
										[
											'commerce_item_id'=>$model->id],
											null,
											[
												$this->app->url(null,['custom_field'=>json_encode($department_custom_field)]),
												'cut_object'=>$this->js()->_selector('.xepan-commerce-item-image')->attr('id')
											]
										)
					];
				$form->js(null,$js)->execute();
			}
		}

		if(count($groups) > 1 or $this->options['show_multi_step_form']){
			$this->js(true)->find('form')->_load('tool/formToWizard')->formToWizard(array("submitButton"=>$addtocart_btn->js(true)->attr('id')));
		}else{
			$this->js(true)->find('form legend')->hide();
		}
		
		return parent::setModel($model);
	}

	function defaultTemplate(){
		return ['view/tool/addtocartbutton'];
	}
}