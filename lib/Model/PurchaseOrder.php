<?php

namespace xepan\commerce;

class Model_PurchaseOrder extends \xepan\commerce\Model_QSP_Master{

   public $status = ['Draft','Submitted','Approved','InProgress','Redesign','Canceled','Rejected','PartialComplete','Completed'];

   public $actions = [
   'Draft'=>['view','edit','delete','submit','manage_attachments'],
   'Submitted'=>['view','edit','delete','reject','approve','manage_attachments','createInvoice','print_document'],
   'Approved'=>['view','edit','delete','reject','markinprogress','manage_attachments','createInvoice','print_document'],
   'InProgress'=>['view','edit','delete','cancel','markhascomplete','manage_attachments','sendToStock'],
   'Redesign'=>['view','edit','delete','submit','reject','manage_attachments'],
   'Canceled'=>['view','edit','delete','manage_attachments'],
   'Rejected'=>['view','edit','delete','submit','manage_attachments'],
   'PartialComplete'=>['view','edit','delete','markhascomplete','manage_attachments'],
   'Completed'=>['view','edit','delete','manage_attachments','print_document']
   ];
   
   function init(){
      parent::init();

      $this->addCondition('type','PurchaseOrder');
      $this->getElement('document_no')->defaultValue($this->newNumber());

  }

  function print_document(){
    $this->print_QSP();
  }
  
  function page_send($page){
    $this->send_QSP($page);
  }

  function submit(){
      $this['status']='Submitted';
      $this->app->employee
      ->addActivity("Purchase Order no. '".$this['document_no']."' has submitted", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
      ->notifyWhoCan('reject,approve,createInvoice','Submitted');
      $this->saveAndUnload();
  }

  function reject(){
      $this['status']='Rejected';
      $this->app->employee
      ->addActivity("Purchase Order no. '".$this['document_no']."' rejected", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
      ->notifyWhoCan('submit','Rejected');
      $this->saveAndUnload();
  }

  function approve(){
      $this['status']='Approved';
      $this->app->employee
      ->addActivity("Purchase Order no. '".$this['document_no']."' approved, invoice can be created", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
      ->notifyWhoCan('reject,markinprogress,createInvoice','Approved');
      $this->saveAndUnload();
  }

  function markinprogress(){
    $this['status']='InProgress';
    $this->app->employee
    ->addActivity("Purchase Order no. '".$this['document_no']."' proceed for dispatching", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
    ->notifyWhoCan('markhascomplete,sendToStock','InProgress');
    $this->saveAndUnload();
  }

  function cancel(){
    $this['status']='Canceled';
    $this->app->employee
    ->addActivity("Purchase Order no. '".$this['document_no']."' canceled", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
    ->notifyWhoCan('delete','Canceled');
    $this->saveAndUnload();
  }

  function markhascomplete(){
    $this['status']='Completed';
    $this->app->employee
    ->addActivity("Purchase Order no. '".$this['document_no']."' successfully dispatched", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
    ->notifyWhoCan('delete','Completed');
    $this->saveAndUnload();

  }

function page_sendToStock($page){

    $page->add('View_Info')->set('Please Select Item to send to Stock');

    $form = $page->add('Form',null,null,['form/empty']);
    foreach ($this->items() as  $item_row) {
        $form->addField('CheckBox',$item_row['item_id'],$item_row['item']);
        $form->addField('hidden','qsp_detail_'.$item_row->id)->set($item_row->id);

        $form->addField('Number','qty_'.$item_row->id,'qty');
        $warehouse_f=$form->addField('DropDown','warehouse_'.$item_row->id,'warehouse');
        $warehouse=$page->add('xepan\commerce\Model_Store_Warehouse');
        $warehouse_f->setModel($warehouse);
    }

    $form->addSubmit('Send');

    if($form->isSubmitted()){

        $warehouse=[];
        $transaction=[];

        foreach ($this->items() as  $item_row) {

            if(!isset($warehouse[$form['warehouse_'.$item_row->id]] )){
                $w = $warehouse[$form['warehouse_'.$item_row->id]] = $this->add('xepan\commerce\Model_Store_Warehouse')->load($form['warehouse_'.$item_row->id]);
                $transaction[$form['warehouse_'.$item_row->id]] = $w->newTransaction($this->id,null,$this['contact_id'],"Purchase");
            }

                        // throw new \Exception($form['item_'.$item_row->id]);
            if($form[$item_row['item_id']]){
                $transaction[$form['warehouse_'.$item_row->id]]
                ->addItem($form['qsp_detail_'.$item_row->id],$form['qty_'.$item_row->id],null,null,null);
            }
        }       
        $this['status']='PartialComplete';
        $this->app->employee
          ->addActivity("Purchase Order no. '".$this['document_no']."' related products successfully send to stock", $this->id/* Related Document ID*/, $this['contact_id'] /*Related Contact ID*/)
          ->notifyWhoCan('delete','Completed');
        $this->saveAndUnload();
        $form->js()->univ()->successMessage('Item Send To Store')->closeDialog();
        return true;
    }

}

}
