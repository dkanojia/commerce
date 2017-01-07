<?php
namespace xepan\commerce;
class View_QSP extends \View{

	public $qsp_model;
	public $qsp_view_field = null;
	public $qsp_form_field = null;
	public $document_label="Document";
	public $document_item;
	public $document = null;

	public $master_template = 'view/qsp/master';
	public $detail_template = 'view/qsp/details';

	function init(){
		parent::init();

		$action = $this->api->stickyGET('action')?:'view';

		// $layout_v=$this->add('View',null,null,$subject_temp);
		// $body_v->getHtml();

		if($this->master_template instanceof \GiTemplate){
			$this->document = $document = $this->add('xepan\hr\View_Document',
				['action'=>$action,'page_reload'=>true],
				null,
				$this->master_template
				);
		}else{	
			$this->document = $document = $this->add('xepan\hr\View_Document',
				['action'=>$action,'page_reload'=>true],
				null,
				[$this->master_template]
				);
		}


		$contact_m = $this->qsp_model->getElement('contact_id')->getModel();
		$contact_m->addExpression('name_with_organization')->set('CONCAT(first_name," ",last_name," :: [",IFNULL(organization,""),"]")');
		$contact_m->title_field = 'name_with_organization';

		$document->addMethod('format_round_amount',function($obj,$value){
			return $value==0?"0.00":(string)round($value,2);
		});
		$document->addMethod('format_discount_amount',function($obj,$value){
			return $value==0?"0.00":(string)round($value,2);
		});


		$document->setIdField('document_id');
		$document->setModel($this->qsp_model,$this->qsp_view_field,$this->qsp_form_field);

		if($this->qsp_model['contact_id']){
			$contact = $this->add('xepan\base\Model_Contact')->load($this->qsp_model['contact_id']);
			$document->template->trySetHTML('contacts',$contact['contacts_comma_seperated']);
			$document->template->trySetHTML('emails',$contact['emails_str']);
			$document->template->trySetHTML('organization',$contact['organization']);

			if($contact['type'] === "Customer" ){
				$customer = $this->add('xepan\commerce\Model_Customer')->load($contact->id);
				$document->template->trySetHTML('customer_tin_no',$customer['tin_no']);
				$document->template->trySetHTML('customer_pan_no',$customer['pan_no']);
			}

			$order_no = '-';
			$order_date = '-';
			if($this->qsp_model['type'] === "SalesInvoice" and $this->qsp_model['related_qsp_master_id']){
				$temp_invoice = $this->add('xepan\commerce\Model_SalesInvoice')->load($this->qsp_model['id']);
				try{
					$sale_order = $temp_invoice->saleOrder();
					$order_no = $sale_order['document_no'];
					$order_date = $sale_order['created_at'];
				}catch(\Exception $e){

				}
			}
			$document->template->trySetHTML('order_no',$order_no);
			$document->template->trySetHTML('order_date',$order_date);

		}		

		$round_amount_standard = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'round_amount_standard'=>'DropDown'
							],
					'config_key'=>'COMMERCE_TAX_AND_ROUND_AMOUNT_CONFIG',
					'application'=>'commerce'
			]);
		$round_amount_standard->tryLoadAny();

		$document->form->getElement('discount_amount')->js('change')->_load('xepan-QSIP')->univ()->calculateQSIP($round_amount_standard['round_amount_standard']);

		$billing_country_field = $document->form->getElement('billing_country_id');
		$billing_state_field = $document->form->getElement('billing_state_id');
		$shipping_country_field = $document->form->getElement('shipping_country_id');
		$shipping_state_field = $document->form->getElement('shipping_state_id');
		
		//shipping state change according to selected country
		if($this->app->stickyGET('s_country_id')){
			$shipping_state_field->getModel()->addCondition('country_id',$_GET['s_country_id'])->setOrder('name','asc');
		}
		$shipping_country_field->js('change',$shipping_state_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$shipping_state_field->name]),'s_country_id'=>$shipping_country_field->js()->val()]));

		//billing state change according to selected country
		if($this->app->stickyGET('b_country_id')){
			$model = $billing_state_field->getModel()->addCondition('country_id',$_GET['b_country_id'])->setOrder('name','asc');
		}
		$billing_country_field->js('change',$billing_state_field->js()->reload(null,null,[$this->app->url(null,['cut_object'=>$billing_state_field->name]),'b_country_id'=>$billing_country_field->js()->val()]));

		if($this->qsp_model->loaded()){

			if($this->detail_template instanceof \GiTemplate){								
				$this->document_item = $qsp_details = $document
														->addMany(
																'Items',
																['no_records_message'=>'No item detail found'],
																'item_info',
																$this->detail_template,
																'xepan\commerce\Grid_QSP',
																'xepan\commerce\CRUD_QSP'
															);
			}else{
				$this->document_item = $qsp_details = $document
															->addMany(
																	'Items',
																	['no_records_message'=>'No item detail found'],
																	'item_info',
																	[$this->detail_template],
																	'xepan\commerce\Grid_QSP',
																	'xepan\commerce\CRUD_QSP'
																);
					
			}
			// if(isset($this->document_item->form) && $this->document_item->form instanceof \Form){
				
			// 	$this->document_item->form->setLayout('view\qsp\detail_form');
			// }	

			$detail_model = $this->qsp_model->ref('Details');
			$detail_model->getElement('item_id')->getModel()->addCondition('is_designable',false);
			
			// if($qsp_details->isEditing()){
			// 	$form = $qsp_details->form;
			// 	// $form->setLayout('view\form\qspdetail');
			// }
			
			// $qsp_details->setModel($detail_model);
			$qsp_details->addHook('formSubmit',function($crud,$form){
				if($crud->isEditing()){
					// check unit conversion is valid or not
					$item_m = $this->add('xepan\commerce\Model_Item')->load($form['item_id']);
					$uc_model = $this->add('xepan\commerce\Model_UnitConversion');
					if(!$uc_model->isConversionExist($item_m['qty_unit_id'],$form['qty_unit_id'],$item_m['qty_unit_group_id'])){
						$form->js()->univ()->frameURL('Add New Unit Conversion',$this->app->url('xepan_commerce_unitconversion',['to_become_id'=>$item_m['qty_unit_id'],'one_of_id'=>$form['qty_unit_id']]))->execute();
						return true;
					}
				}
			});

			$qsp_details->setModel($detail_model,['qsp_master_id','qsp_master','item_id','item','price','quantity','qty_unit_id','qty_unit','taxation_id','taxation','shipping_charge','shipping_duration','express_shipping_charge','express_shipping_duration','tax_percentage','is_shipping_inclusive_tax','narration','extra_info','is_shipping_inclusive_tax','qty_unit','amount_excluding_tax','tax_amount','total_amount','customer_id','customer','name','qsp_status','qsp_type','sub_tax','received_qty','amount_excluding_tax_and_shipping']);

			//comman vat and it's amount
			if($action!='add'){
				if( $this->document_item instanceof \Grid or ($this->document_item instanceof \CRUD && !$this->document_item->isEditing()) or $action=="pdf"){
					if(count($this->qsp_model->getCommnTaxAndAmount())){
						$lister = $document->add('Lister',null,'common_vat',[$this->master_template,'common_vat']);
						$lister->setSource($this->qsp_model->getCommnTaxAndAmount());
						
						$lister->addHook('formatRow',function($g){
							$g->current_row_html['net_amount_sum'] = number_format((float)$g->current_row['net_amount_sum'], 2, '.', '');
							$g->current_row_html['taxation_sum'] = number_format((float)$g->current_row['taxation_sum'], 2, '.', '');
						});

						$document->template->trySetHTML('common_vat',$lister->getHtml());
					}else{
						$document->template->tryDel('common_vat_wrapper');
					}
				}
				$document->add('View',null,'amountinwords')->set($this->qsp_model->amountInWords($this->qsp_model['net_amount'],$this->qsp_model['currency_id']));
			}
			
			if($detail_model->count()->getOne()){
				$item_m = $this->add('xepan\commerce\Model_Item');
				$detail_j = $item_m->join('qsp_detail.item_id');
				$detail_j->addField('detail_id','id');
				$item_m->addCondition('detail_id','in',$detail_model->fieldQuery('id'));
				// $item_tnc_l = $document->add('CompleteLister',null,'terms_and_conditions',[$this->master_template,'terms_and_conditions']);
				// $item_tnc_l->setModel($item_m);
			}

			
			$qs = $this->add('xepan\commerce\View_QSPDetailJS');
			if($qsp_details instanceof \CRUD && $qsp_details->isEditing()){
				$form = $qsp_details->form;
				$form->setLayout('view\form\qspdetail');
				
				$item_field = $form->getElement('item_id');
				$price_field = $form->getElement('price');
				$shipping_charge = $form->getElement('shipping_charge');
				$shipping_duration = $form->getElement('shipping_duration');
				$express_shipping_charge = $form->getElement('express_shipping_charge');
				$express_shipping_duration = $form->getElement('express_shipping_duration');
				$qty_field = $form->getElement('quantity');

				$tax_field = $form->getElement('taxation_id');
				$tax_percentage = $form->getElement('tax_percentage');
				
				$field_unit = $form->getElement('qty_unit_id');
				$field_unit->getModel()->title_field = "name_with_group";
				// $sale_price=$form->getElement('sale_amount');
				// $original_price=$form->getElement('original_amount');
				
				if($item_id=$_GET['item_id']){
					$item_m = $this->add('xepan\commerce\Model_Item');
					// $item_m->addExpression('unit_group_id')->set(function($m,$q){
					// 	return $q->expr('IFNULL([0],0)',[$m->refSQL('qty_unit_id')->fieldQuery('unit_group_id')]);
					// });

					$item_m->load($item_id);

					$price_field->set($item_m->get('sale_price'));
					// var_dump($item_m->shippingCharge($form['price'],1));
					$price=$_GET['price'];
					$qty=$_GET['qty'];
					$shipping_charge->set($item_m->shippingCharge($price,$qty)['shipping_charge']);
					$shipping_duration->set($item_m->shippingCharge($price,$qty)['shipping_duration']);
					$express_shipping_charge->set($item_m->shippingCharge($price,$qty)['express_shipping_charge']);
					$express_shipping_duration->set($item_m->shippingCharge($price,$qty)['express_shipping_duration']);
					
					$model = $field_unit->getModel();
					$model->addCondition('unit_group_id',$item_m['qty_unit_group_id']);
					$field_unit->set($item_m['qty_unit_id']);
				}
				$item_reload_field_array=[
						$form->js()->atk4_form(
							'reloadField','price',[
										$this->app->url(),
											'item_id'=>$item_field->js()->val(),
										]
						),

						$form->js()->atk4_form(
							'reloadField','shipping_charge',[
										$this->app->url(),
											'item_id'=>$item_field->js()->val(),
											'price'=>$price_field->js()->val(),
											'qty'=>$qty_field->js()->val()
										]
						),

						$form->js()->atk4_form(
							'reloadField','shipping_duration',[
										$this->app->url(),
											'item_id'=>$item_field->js()->val(),
											'price'=>$price_field->js()->val(),
											'qty'=>$qty_field->js()->val()
										]
						),

						$form->js()->atk4_form(
							'reloadField','express_shipping_charge',[
										$this->app->url(),
											'item_id'=>$item_field->js()->val(),
											'price'=>$price_field->js()->val(),
											'qty'=>$qty_field->js()->val()
										]
						),

						$form->js()->atk4_form(
							'reloadField','express_shipping_duration',[
										$this->app->url(),
											'item_id'=>$item_field->js()->val(),
											'price'=>$price_field->js()->val(),
											'qty'=>$qty_field->js()->val()
										]
						),

						$form->js()->atk4_form(
							'reloadField','qty_unit_id',[
									$this->app->url(),
									'item_id'=>$item_field->js()->val()
								]
							)

					];

				$item_field->other_field->js('change',$item_reload_field_array);

				// if($qty = $_GET['qty']){
				// 	$qty_price = ($_GET['price'] * $qty);
				// 	$price_field->set($qty_price);
				// }

				// $qty_field->js('change',$form->js()->atk4_form(
				// 	'reloadField','price',
				// 	[
				// 		$this->app->url(),
				// 		'item_id'=>$item_field->js()->val(),
				// 		'qty'=>$qty_field->js()->val(),
				// 		'price'=>$price_field->js()->val()
				// 	]
				// ));

				/*Text Calculation*/
				if($id=$_GET['tax_id']){
					$tax_percentage->set(
						$this->add('xepan\commerce\Model_Taxation')
						->load($id)
						->get('percentage')
						);
					return;
				}


				$tax_field->js('change',$form->js()->atk4_form(
					'reloadField','tax_percentage',
					[
					$this->app->url(),
					'tax_id'=>$tax_field->js()->val()
					]
					));

				// load only saleable or purchasable and published item
				$item_model = $form->getElement('item_id')->getModel();

				$item_model->addCondition(
						$item_model->dsql()->orExpr()
						->where('is_saleable',true)
						->where('is_purchasable',true)
					);
				$item_model->addCondition('status',"Published");
			}
		}
	}
}