<?php


namespace xepan\commerce;

class View_QSPAddressJS extends \View {
	function render(){

		if($_GET['changed_contact_id']){
			$contact = $this->add('xepan\base\Model_Contact');
			$contact->load($_GET['changed_contact_id']);
			$js=[];

			// billing address
			$js[] = $this->js()->_selector('.billing_address')->find('input')->val($contact['billing_address']?:$contact['address']);
			$js[] = $this->js()->_selector('.billing_city')->find('input')->val($contact['billing_city']?:$contact['city']);
			$js[] = $this->js()->_selector('.billing_state')->find('input')->val($contact['billing_state']?:$contact['state']);
			$js[] = $this->js()->_selector('.billing_country')->find('input')->val($contact['billing_country']?:$contact['country']);
			$js[] = $this->js()->_selector('.billing_pincode')->find('input')->val($contact['billing_city']?:$contact['pincode']);
			$js[] = $this->js()->_selector('.billing_tel')->find('input')->val($contact['billing_tel']?:$contact->ref('Phones')->tryLoadAny()->get('value'));
			$js[] = $this->js()->_selector('.billing_email')->find('input')->val($contact['billing_email']?:$contact->ref('Emails')->tryLoadAny()->get('value'));
			
			// shipping address
			$js[] = $this->js()->_selector('.shipping_address')->find('input')->val($contact['shipping_address']?:$contact['address']);
			$js[] = $this->js()->_selector('.shipping_city')->find('input')->val($contact['shipping_city']?:$contact['city']);
			$js[] = $this->js()->_selector('.shipping_state')->find('input')->val($contact['shipping_state']?:$contact['state']);
			$js[] = $this->js()->_selector('.shipping_country')->find('input')->val($contact['shipping_country']?:$contact['country']);
			$js[] = $this->js()->_selector('.shipping_pincode')->find('input')->val($contact['shipping_city']?:$contact['pincode']);
			$js[] = $this->js()->_selector('.shipping_tel')->find('input')->val($contact['shipping_tel']?:$contact->ref('Phones')->tryLoadAny()->get('value'));
			$js[] = $this->js()->_selector('.shipping_email')->find('input')->val($contact['shipping_email']?:$contact->ref('Emails')->tryLoadAny()->get('value'));
			
			$this->js(true,$js);
		}

		parent::render();
	}
}