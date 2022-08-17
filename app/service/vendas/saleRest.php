<?php

class SaleRest extends AdiantiRecordService
{
    const DATABASE      = 'pos_sale';
    const ACTIVE_RECORD = 'Sale';
    
   public function handle($param)
    {
            $method = strtoupper($_SERVER['REQUEST_METHOD']);
            unset($param['class']);
            unset($param['method']);
            //seletor de redirecionamento de função
            switch($method)
        {
            case 'POST':
                return self::saveSale($param);//permite salvar/emitir uma venda a partir do numero da venda.
                break;
            case 'PUT':
                return self::getSale($param);//permite obter o array de venda do respectivo PDV.
                break;
                case 'GET':                    
                return "Indisponivel";
                break;
            case 'DELETE':
                return self::cancelSale($param);//permite obter o array de venda do respectivo PDV.
                break;
            default:
                return "Indisponivel";
        }
    }
    
    
    //functions
    public function saveSale($param)
    {
        try{
            $param = $param['sale'];
            if( !isset($param['payments']) ||  $param['payments'] == "" || count($param['payments'])== 0  ){
                throw new Exception('invalid payments!');
            }
            if( !isset($param['items']) ||  $param['items'] == "" || count($param['items']) == 0  ){ 
                throw new Exception('invalid items!');
            }
            if( !isset($param['cashier']) ||  $param['cashier'] == "" || count($param['cashier']) == 0  ){ 
                throw new Exception('invalid cashier!');
            }
            if( !isset($param['store']) ||  $param['store'] == "" || count($param['store']) == 0  ){ 
                throw new Exception('invalid store!');
            }
            TTransaction::open(static::DATABASE);
            $antiClone                  = self::checkSaleClone($param['number']);
            if($antiClone){
                $nfceArray = false;
                if(isset($antiClone->sale_invoice_cupon)){
                    $nfceArray = array();
                    $nfceArray['cupom_pdf']         = $antiClone->invoice_coupon;
                    $nfceArray['nfce_xml']          = ''; //no needs in to pos
                    $nfceArray['number']            = $antiClone->invoice_number;
                    $nfceArray['serie']             = $antiClone->invoice_serie;                
                }
                return [
                    "data"=>[
                           "id"=>$antiClone->id,
                           "nfce"=>$nfceArray
                        ]
                    ];
            }
            $sale                       = $antiClone;
            $settings                   = array();
            if(!$sale){
                $sale                   = new Sale();
                $sale->number           = $param['number'];
                $sale->products_value   = $param['products_value'];
                $sale->payments_value   = $param['payments_value'];
                $sale->discont_value    = isset($param['discont_value']) ? $param['discont_value'] : null;
                $sale->total_value      = $param['total_value'];
                $sale->employee_sale    = $param['customer']['type'] ==='funcionario'? true : false;
                $sale->sale_date        = $param['sale_date'];
                $sale->invoiced         = 0;
                $sale->invoice_ambient  = 0;
                $sale->obs              = isset($param['obs']) ? $param['obs'] : null;
                $sale->sys_obs          = isset($param['sys_obs']) ? $param['sys_obs'] : null;
                $sale->invoice_number   = null;
                $sale->invoice_serie    = null;
                $sale->invoice_coupon   = null;
                $sale->invoice_xml      = null;
                $payments               = $param['payments'];
                $sale->payment_method   = isset($payments[1]) ? 6 : $param['payments'][0]['method_id'];
                $sale->store            = $param['store']['store_id'];
                $sale->employee_cashier = $param['employee_cashier']['user_id'];
                $sale->cashier          = $param['cashier']['cashier_id'];
                //customer process
                $customerDoc            = $param['customer']['document'];
                if($customerDoc !== '' || $customerDoc!== null){
                    TTransaction::open('pos_customer');
                    $customer               = null;
                    $customer               = Customer::where('document','=',$customerDoc)->first();
                    if($customer){
                        $sale->customer     = $customer->id;
                    }else{
                        $customer           = new Customer();
                        $customer->document = $customerDoc;
                        $customer->name     = $param['customer']['name'];
                        $customer->email    = $param['customer']['email'];
                        $customer->type     = $param['customer']['type'];
                        //set store partiner
                        $parc               = null;
                        if(isset($param['customer']['store_partiner'])){
                            $parcDoc        = $param['customer']['customer']['store_partiner']['cnpj'];
                            $parc           = StorePartiner::where('cnpj','=',$parcDoc)->first();
                            if(!$parc){
                                $parc       = new StorePartiner();
                                $parc->name = $param['customer']['customer']['store_partiner']['name'];
                                $parc->cnpj = $param['customer']['customer']['store_partiner']['cnpj'];
                                $parc->store();
                            }
                        }
                        $customer->store_partiner = $parc->id;
                        $customer->store();
                        $sale->customer     = $customer->id;
                    }
                    TTransaction::close();
                }
                //salesman process
                $salesmanDoc                = $param['salesman']['document'];
                if($salesmanDoc !== '' || $salesmanDoc!== null){
                    TTransaction::open('pos_customer');
                    $salesman               = null;
                    $salesman               = Customer::where('document','=',$salesmanDoc)->first();
                    $sale->salesman         = $salesman->id;                    
                    TTransaction::close();
                }
                //
                $sale->salesman         = $param['salesman'];
                $sale->status           = 3;
                $sale->store();
                $paymentsArray          = array();
                $itemsArray             = array();
                $settings['invoice']    = isset($param['invoice']) ?$param['invoice'] : false ;
                foreach($payments as $payment){
                    $payment_                   = new SalePayment();
                    $payment_->value            = $payment['method_value'];
                    $payment_->sale_date        = $sale->sale_date;
                    $payment_->store            = $sale->store;
                    $payment_->sale             = $sale->id;
                    $payment_->payment_method   = $payment['method_id'];//$payment['payment_method_id'];
                    $payment_->store();
                    $sale->payments_value       += $payment_->value;
                    $paymentsArray[]            = $payment_;
                }
                //itens
                $items                          = $param['items'];
                TTransaction::open('pos_product');
                $deposit                        = Deposit::where('store','=',$sale->store)->first();
                TTransaction::close();
                foreach($items as $item){
                    $item_                      = new SaleItem();
                    $item_->quantity            = $item['quantity'];
                    $item_->unitary_value       = $item['value'];
                    $item_->total_value         = $item['total'];
                    $item_->sale_date           = $sale->sale_date;
                    $item_->store               = $sale->store;
                    $item_->sale                = $sale->id;
                    $item_->product             = $item['id'];
                    //storage
                    $productStorage             = self::storageContable($deposit,$item_);
                    $item_->deposit             = $productStorage->deposit;
                    $item_->product_storage     = $productStorage->id;
                    $item_->store();
                    $sale->products_value       += $item_->total_value;
                    //cupom
                    $disconts                   = $item['disconts'];
                    if(isset($item['disconts']) && $item['disconts'] !== ""){
                        foreach($disconts as $discont){
                            $itemCupom              = new ItemCupom();
                            $itemCupom->value       = $discont['value'];
                            $itemCupom->cupom       = $discont['cupom'];
                            $itemCupom->sale_item   = $item_->id;
                            $itemCupom->sale        = $sale->id;
                            $itemCupom->code        = $discont['code'];
                            $itemCupom->description = $discont['description'];
                            $itemCupom->store();
                            $item_->discont_value   = $discont['price'];
                        }
                    }
                    $itemsArray[]               = $item_;
                }
                $disconts                       = $param['disconts'];
                if(count($disconts) != 0 ){
                    foreach($disconts as $discont){
                        $discont_               = new SaleDiscont();
                        $discont_->code         = $discont['code'];
                        $discont_->description  = $discont['description'];
                        $discont_->percent      = $discont['percent']===1 ? true: false;
                        $discont_->value        = $discont['value'];
                        $discont_->sale         = $sale->id;
                        $discont_->cupom        = isset($discont['id']) ? $discont['id']:null;
                    }
                }
                $settings['payments']           = $paymentsArray;
                $settings['items']              = $itemsArray;
                
            }
            TTransaction::close();
            /*
                DISCOMMENT TO ACTIVE NFCE EMISSOR
            */
            // $sale                            = nfceEmissor::isInvoice($sale,$settings); 
            $return                             = array();
            if(isset($sale->sale_invoice_cupon)){
                $nfceArray = array();
                $nfceArray['cupom_pdf']         = $sale->invoice_coupon;
                $nfceArray['nfce_xml']          = ''; //no needs in to pos
                $nfceArray['number']            = $sale->invoice_number;
                $nfceArray['serie']             = $sale->invoice_serie;
                $return['nfce']                 = $nfceArray;
            }else{
                $return['error']                    = false;
                $return['data']                     = [
                    'id'=> $sale->id,
                    'nfce'=>false
                ];

            }
            return $return;
        }catch(Exception $e){
            $error = array();
            $error['error']                     = true;
            $error['data']                      = $e->getmessage();
            TTransaction::rollback();
            return $error;
        } 
    }
    
    
    
    public function getSale($param)
    {
        /* -- expect this Json and always will return sales form token user,
            {
               "sale_id":"",
               "sale_number":"",
               "date_start": "",
               "date_end": "",
               "invoice": false
            }
        */ 
        try{
            $sale                   = null;
            $invoice                = isset($param['invoice']) ? true : false;
            $userId                 = TSession::getValue('userid');
            $userName               = TSession::getValue('username');
            $return                 = array();
            TTransaction::open('pos_system');
            $user                   = User::where('system_user','=',$userId)->first();
            $store                  = new Store($user->current_store);
            //request with sale_id
            if(isset($param['sale_id']) && $param['sale_id'] !== "" ){
                $search          = $param['sale_id'];
                $return['error'] = false;
                TTransaction::open(static::DATABASE);
                $sale            = Sale::where('id','=',$search)->first();
                $return['data']  = self::generateArraySaleWithAdds($sale,$invoice);
                TTransaction::close();
                return $return;
            }
            // $return                 = self::generateArraySaleWithAdds($sale,$invoice)
            //request with sale_number
            if(isset($param['sale_number']) && $param['sale_number'] !== "" ){
                $search          = $param['sale_number'];
                $return['error'] = false;
                TTransaction::open(static::DATABASE);
                $sale            = Sale::where('number','=',$search)->first();
                $return['data']  = self::generateArraySaleWithAdds($sale,$invoice);
                Transaction::close();
                return $return;
            }
            //request default, pull lasts 7days sale, from user and store
            if( (!isset($param['sale_id'])      || $param['sale_id'] === "") && 
                (!isset($param['sale_number'])  || $param['sale_number'] === "") &&
                (!isset($param['date_start'])   || $param['date_start'] === "") &&
                (!isset($param['date_end'])     || $param['date_end'] === "") ){
                    $start                  = date('Y-m-d 23:59:00');
                    $end                    = date('Y-m-d 00:00:00',strtotime('-7 days'));
                    $system_user            = TSession::getValue('userid');
                    $return                 = null;
                    TTransaction::open('pos_system');
                    $user                   = User::where('system_user','=',$userId)->first();
                    TTransaction::close();
                    TTransaction::open(static::DATABASE);
                    $sales = Sale::where('employee_cashier','=',$user->id)
                    ->where('store','=',$user->current_store)
                    ->where('sale_date','<=',$start)
                    ->where('sale_date','>=',$end)
                    ->load();
                    if($sales){
                        foreach($sales as $sale){
                            $arraySale = self::generateArraySaleWithAdds($sale);
                            $return[] = $arraySale;
                        }
                    }
                    TTransaction::close();
                    return [
                        "error"=> false,
                        "data"=>$return
                    ];
                }
            
            
        }catch(Exception $e){
            $error = array();
            $error['error']             = true;
            $error['data']              = $e->getmessage();
            TTransaction::rollback();
            return $error;
        } 
    }
    
    
    /*
        expects this array:
        {
            "sale_id":"",
            "sale_number":""
        }
    */
    public function cancelSale($param)
    {
        try{
            //do not exist delete a sale, but you can cancel, a sale cancel can be in 25mim after sale create.
        }catch(Exception $e){
            $error = array();
            $error['error']             = true;
            $error['data']              = $e->getmessage();
            TTransaction::rollback();
            return $error;
        } 
    }
    
    public function checkSaleClone($saleNumber){
        $check          = Sale::where('number','=',$saleNumber)->first();
        if($check !== null){
            return $check;
        }
        return false;
    }
    //HELPER FUNCTIONS
    
    /*
    this functtion convert a sale object into sale array with your payment and sale items
    call it with on ttransaction open
    this param is aways a unique sale object!
    */
    public function generateArraySaleWithAdds($sale,$invoice=false){
        $saleArray                      = array();
        $items                          = SaleItem::where('sale','=',$sale->id)->load();
        $payments                       = SalePayment::where('sale','=',$sale->id)->load();
        $nfce                           = false;
        //nfce array mount
        if(isset($sale->sale_invoice_cupon)){
            $nfce                       = array();
            $nfce['cupom_pdf']          = $sale->invoice_coupon;
            $nfce['nfce_xml']           = ''; //no needs in to pos
            $nfce['number']             = $sale->invoice_number;
            $nfce['serie']              = $sale->invoice_serie;                
        }
        //get with force invoice
        // if(!$nfce && $invoice){
        //     TTransaction::open(static::DATABASE);
        //     $invoice                    = nfceEmissor::isInvoice($sale,$settings); 
        //     $nfce                       = array();
        //     $nfce['cupom_pdf']          = $invoice->invoice_coupon;
        //     $nfce['nfce_xml']           = ''; //no needs in to pos
        //     $nfce['number']             = $invoice->invoice_number;
        //     $nfce['serie']              = $invoice->invoice_serie;  
        //     TTransaction::close();
        // }
        $saleArray['id']                = $sale->id;
        $saleArray['number']            = $sale->number;
        $saleArray['products_value']    = $sale->products_value;
        $saleArray['payments_value']    = $sale->payments_value;
        $saleArray['discont_value']     = $sale->discont_value;
        $saleArray['total_value']       = $sale->total_value;
        $saleArray['status']            = $sale->fk_status->description;
        $saleArray['employee_sale']     = $sale->employee_sale==1 ? true:false; 
        $saleArray['sale_date']         = $sale->sale_date;
        $saleArray['obs']               = $sale->obs;
        $saleArray['sys_obs']           = $sale->sys_obs;
        $saleArray['qtd_items']         = count($items);
        $saleArray['qtd_payments']      = count($payments);
        $saleArray['payment_method']    = $sale->payment_method;
        $saleArray['nfce']              = $nfce;
        $saleArray['store']             = [
            "store_id"=> $sale->fk_store->id,
            "store_name"=> $sale->fk_store->fantasy_name,
            "store_abbreviation"=> $sale->fk_store->abbreviation
        ];
        $saleArray['cashier']           = [
           "cashier_id"=>$sale->fk_cashier->id,
           "cashier_name"=>$sale->fk_cashier->name
        ];
        $saleArray['employee_cashier']  = [
            "user_id"=>$sale->fk_employee_cashier->fk_system_user->id,
            "user_name"=>$sale->fk_employee_cashier->fk_system_user->name
        ];
        TTransaction::open('pos_customer');
        $customer                       = new Customer($sale->customer);
        $salesman                       = new Customer($sale->salesman);
        TTransaction::close();
        $saleArray['customer']          = [
            "name"=>$customer->name,
            "document"=>$customer->document,
            "document_type"=>$customer->document_type,
            "email"=>$customer->email,
            "type"=>$customer->type,
        ];
        $saleArray['salesman']          = [
            "name"=>$salesman->name,
            "document"=>$salesman->document,
            "document_type"=>$salesman->document_type,
            "email"=>$salesman->email,
            "type"=>$salesman->type,
        ];
        
        //$items
        $itemsArray                     = array();
        foreach($items as $item){
            $itemArray = array();
            $itemArray['id']            = $item->fk_product->id;
            $itemArray['description']   = $item->fk_product->description;
            $itemArray['sku']           = $item->fk_product->sku;
            $itemArray['category']      = $item->fk_product->category;
            $itemArray['website']       = $item->fk_product->website;
            // $itemArray['provider']      = $item->fk_product->fk_provider->fantasy_name;
            $itemArray['price']         = $item->unitary_value;
            $itemArray['quantity']      = $item->quantity;
            $itemArray['value']         = $item->unitary_value * $item->quantity;
            $itemArray['total']         = $item->total_value;
            $itemArray['disconts']      = [];
            $disconts                   = ItemCupom::where('sale_item','=',$item->id)->load();
            if($disconts){
                foreach($disconts as $discont){
                    $itemArrayDiscont   = array();
                    $itemArrayDiscont['code']           = $discont->code;
                    $itemArrayDiscont['description']    = $discont->description;
                }
            }
            $itemsArray[]               = $itemArray;
        }
        $saleArray['items']             = $itemsArray;
        //payments
        $paymentsArray                  = array();
        foreach($payments as $payment){
            $paymentArray               = array();
            $paymentArray['value']      = $payment->value;
            $paymentArray['payment_method']= $payment->fk_payment_method->alias;
            $paymentsArray[]            = $paymentArray;
        }
        $saleArray['payments']          = $paymentsArray;
        return $saleArray;
    }
    
    public function storageContable($deposit,$item){
        TTransaction::open('pos_product');
        $storage        = ProductStorage::where('deposit','=',$deposit->id)
                                        ->where('product','=',$item->product)
                                        ->first();
        if($storage){
            $storage->quantity      -= $item->quantity;
            $storage->store();
        }else{
            $storage                = new ProductStorage();
            $storage->quantity      = $item->quantity*-1;
            $storage->min_storage   = 0;
            $storage->max_storage   = 0;
            $storage->deposit       = $deposit->id;
            $storage->product       = $item->product;
            $storage->store         = $deposit->store;
            $storage->store();
        }
        TTransaction::close();
        return $storage;
    }
}
