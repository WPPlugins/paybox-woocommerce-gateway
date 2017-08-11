<?php

class WC_Paybox_Standard_Gateway extends WC_Paybox_Abstract_Gateway {

    protected $defaultTitle = 'Paybox payment';
    protected $defaultDesc = 'xxxx';
    protected $type = 'standard';

    public function __construct() {
        $this->id = 'standard';
        $this->method_title = __('Paybox', 'paybox');
        $this->has_fields = false;
        $this->icon = apply_filters('woocommerce_gateway_icon', plugins_url('paybox-by-verifone-integration/images/logo-small.png'));
        parent::__construct();
    }
}
