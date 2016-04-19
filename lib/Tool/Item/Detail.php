<?php

namespace xepan\commerce;

class Tool_Item_Detail extends \xepan\cms\View_Tool{
	public $options = [
				// 'display_layout'=>'item-description',/*flat*/
					 
				];
	public $item;
	function init(){
		parent::init();

		$item_id = $this->api->stickyGET('commerce_item_id');

		$this->item = $this->add('xepan\commerce\Model_Item')->load($item_id);
		$this->setModel($this->item);
	}

	function setModel($model){
		//tryset html for description 
		$this->template->trySetHtml('item_description', $model['description']);

		//specification
		$spec_grid = $this->add('xepan\base\Grid',null,'specification',["view/tool/item/detail/".$this->options['specification_layout']]);
		$spec_grid->setModel($model->specification(),['name','value']);

		//add personalized button
		if($model['is_designable']){
			// add Personalioze View
			$personalized_page_url = $this->app->url(
										$this->options['personalized_page'],
										['xsnb_design_item_id'=>$model['id']]
									);
			$this->add('Button',null,'personalized_button')
					->set(
							$this->options['personalized_button_label']?:"personalized"
						)
					->js('click',
							$this->js()->univ()->location($personalized_page_url)
						);
		}

		//price calculation or add to cart button setup
		//if item is designable than hide "AddToCart" button
		if($model['is_saleable']){
			$options = [
						'button_name'=>$this->options['addtocart_button_label'],
						'show_addtocart_button'=>$model['is_designable']?0:1

						];

			$cart_btn = $this->add('xepan\commerce\Tool_Item_AddToCartButton',
				[
					'name' => "addtocart_view_".$model->id,
					'options'=>$options
				],'Addtocart'
				);
			$cart_btn->setModel($model);
		}

		//add Item Uploadable		
		if($model['is_allowuploadable'] and $this->options['show_item_upload']){			
			$contact = $this->add('xepan/base/Model_Contact');
      		if(!$contact->loadLoggedIn()){
    			//Todo add login panle here
				$this->add('View_Error',null,'item_upload')->set('add Login Panel Here');
				return;
      		}

      		$member_image=$this->add('xepan/commerce/Model_Designer_Images');
			$images_count = 1;
			if($model['upload_file_label']){
				$upload_array=explode(',', $model['upload_file_label']);
				$images_count = count($upload_array);
			}

			$v = $this->add('View',null,'item_upload');

			$this->api->stickyGET('show_cart');

			if($_GET['show_cart']){
				$v->add('Button')->setLabel('Back')->js('click',$v->js()->reload(array('show_cart'=>0)));

				$options = [
						'button_name'=>$this->options['addtocart_button_label'],
						'show_addtocart_button'=>$model['is_designable']?0:1

						];

				$cart_btn = $v->add('xepan\commerce\Tool_Item_AddToCartButton',
					[
						'name' => "addtocart_view_".$model->id,
						'options'=>$options
					]);
				$cart_btn->setModel($model);

			}else{
				
				$v->add('View')->setHTML($model['item_specific_upload_hint']);
				$up_form = $v->add('Form');
				$multi_upload_field = $up_form->addField('Upload','upload',"")
						->allowMultiple($images_count)
						->setFormatFilesTemplate('view/tool/item/detail/file_upload');
				$multi_upload_field->setAttr('accept','.jpeg,.png,.jpg');
				$multi_upload_field->setModel('filestore/Image');

				$up_form->addSubmit('Next');
				
				if($up_form->isSubmitted()){
					//check for the image count
					$upload_images_array = explode(",",$up_form['upload']);

					if($images_count != count($upload_images_array))
						$up_form->error('upload','upload all images');

					$image_cat_model = $this->add('xepan\commerce\Model_Designer_Image_Category')->loadCategory($model['name']);

					foreach ($upload_images_array as $file_id) {
					    $image_model = $this->add('xepan/commerce/Model_Designer_Images');
						$image_model['file_id'] = $file_id;
						$image_model['designer_category_id'] = $image_cat_model->id;
						$image_model->saveAndUnload();
					}

					$up_form->js(null,$v->js()->reload(array('show_cart'=>1,'file_upload_ids'=>$up_form['upload'])))->execute();
				}
			}

		}

		parent::setModel($model);
	}

	function defaultTemplate(){
		return ['view/tool/'.$this->options['layout']];
	}
	
}