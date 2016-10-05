<?php

namespace xepan\commerce; 

class page_amountstandard extends \xepan\commerce\page_configurationsidebar{
	public $title = "Amount Standard";
	
	function init(){
		parent::init();
		
		$round_amount_standard = $this->add('xepan\base\Model_ConfigJsonModel',
			[
				'fields'=>[
							'round_amount_standard'=>'DropDown'
							],
					'config_key'=>'COMMERCE_TAX_AND_ROUND_AMOUNT_CONFIG',
					'application'=>'commerce'
			]);
		$round_amount_standard->add('xepan\hr\Controller_ACL');
		$round_amount_standard->tryLoadAny();		

		$form = $this->add('Form');
		$form->setModel($round_amount_standard_);

		$default_round_standard = $form->getElement('round_amount_standard')->setValueList(['None'=>'None','Standard'=>'Standard','Up'=>'Up','Down'=>'Down'])->set($round_amount_standard['round_amount_standard']);
		$form->addSubmit('Save')->addClass('btn btn-primary');

		if($form->isSubmitted()){
			$form->save();
			$form->js(null,$form->js()->reload())->univ()->successMessage('Rounding Amount Standard Updated')->execute();
		}
	}
}