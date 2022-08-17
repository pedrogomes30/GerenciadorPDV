<?php

class customerRest extends AdiantiRecordService
{
    const DATABASE      = 'pos_customer';
    const ACTIVE_RECORD = 'Customer';
    
    public function handle($param)
    {
            $method = strtoupper($_SERVER['REQUEST_METHOD']);
            unset($param['class']);
            unset($param['method']);
            //seletor de redirecionamento de função
            switch($method)
        {
            case 'POST':
                return self::saveCustomer($param);//permite salvar/emitir uma venda a partir do numero da venda.
                break;
            case 'PUT':
                return self::getCustomer($param);//permite obter o array de venda do respectivo PDV.
                break;
            case 'GET':
                return "Indisponivel";
                break;
            case "DELETE":
                return "Indisponivel";
                break;
            default:
                return "Indisponivel";
        }
    }
    /*
    expects a array:
        {
            "name":"customer example",
            "document": "98561245721", (only numbers cpf or cnpj)
            "document_type":true, (true - customer / false-company)
            "email":"example@example.com",
            "city":"rio de janeiro",
            "uf":"RJ",
            "postal_code":"09822156"(only numbers)
            "phone1":"12457896"
            "phone2":"12457896"
            "phone3":"12457896"
            "store_partiner": (only company)
                { 
                    "name":"comapny example",
                    "cnpj":"12545789632"(only numbers cnpj)
                }
        }
    */
    public function saveCustomer($param){
        try{
            TTransaction::open(static::DATABASE);
            $param    = $param['customer'];    
            if(!isset($param['document']) || $param['document'] == '' || $param['document'] == null ) 
                throw new Exception('invalid customer, no document value');
            $document = $param['document'];
            $customer = Customer::where('document','=',$document)->first();
            if(!$customer){
                $customer                   = new Customer();
                $customer->name             = mb_strtoupper($param['name']);
                $customer->document         = preg_replace('/[^0-9]/', '', $document);
                $customer->document_type    = $param['document_type'];
                $customer->email            = $param['email'];
                $customer->city             = $param['city'];
                $customer->uf               = $param['uf'];
                $customer->postal_code      = $param['postal_code'];
                $customer->phone1           = $param['phone1'];
                $customer->phone2           = $param['phone2'];
                $customer->phone3           = $param['phone3'];
                if(isset($param['store_partiner'])){
                    $storePartinerParam     = $param['store_partiner'];
                    $document_parc          = $storePartinerParam['cnpj'];
                    $storePartiner          = StorePartiner::where('cnpj','=',$document_parc)->first();
                    if(!$storePartiner){
                        $storePartiner              = new StorePartiner();
                        $storePartiner->name        = mb_strtoupper($storePartinerParam['name']);
                        $storePartiner->cnpj        = preg_replace('/[^0-9]/', '',$document_parc);
                        $storePartiner->store();
                        $customer->store_partiner   = $storePartiner->id;
                    }else{
                        $customer->store_partiner   = $storePartiner->id;
                    }
                }
                $customer->store();
            }else{
                throw new exception('customer has been registred!');
            }
            TTransaction::close();
            $param['id']    =   $customer->id;
            return $param;
        }catch(Exception $e){
            $error = array();
            $error['error']             = true;
            $error['data']              = $e->getmessage();
            $error['param']             = $param;
            TTransaction::rollback();
            return $error;
        }
    }
    
    /*
        expects a array:
        {
            "document":"12545789653";
        }
    */
    public function getCustomer($param){
        try{
            if(!isset($param['document']) || $param['document'] == '' || $param['document'] == null ) 
                throw new Exception('invalid customer, no document value');
            TTransaction::open(static::DATABASE);
            $document                 = $param['document'];
            $customer                 = Customer::where('document','=',$document)->first();
            $return                   = false;
            if($customer){
                $storePartiner            = null;
                if($customer->store_partiner !== null){
                    $storePartiner        = new StorePartiner($customer->store_partiner);
                }
                $return                   = array();
                $return['name']           = $customer->name;
                $return['document']       = $customer->document;
                $return['document_type']  = $customer->document_type;
                $return['email']          = $customer->email;
                $return['city']           = $customer->city;
                $return['uf']             = $customer->uf;
                $return['postal_code']    = $customer->postal_code;
                $return['phone1']         = $customer->phone1;
                $return['phone2']         = $customer->phone2;
                $return['phone3']         = $customer->phone3;
                $type                     = $customer->system_user == null ? 'cliente' : 'funcionario';
                $type                     = $customer->store_partiner == null ? $type : 'funcionarioParceiro';
                $return['type']           = $type;
                $return['store_partiner'] = [
                    "id"=>$storePartiner->id,
                    "name"=>$storePartiner->name,
                    "cnpj"=>$storePartiner->cnpj
                ];
            }
            TTransaction::close();
            $result = array();
            $result['error']          = false;
            $result['data']           = $return;
            return $result;
        }catch(Exception $e){
            $error = array();
            $error['error']             = true;
            $error['data']              = $e->getmessage();
            TTransaction::rollback();
            return $error;
        }
    }
    
    //HELPER FUNCTIONS
    public function checkCpfCnpj($document){
        $validDoc = new stdClass();
        $validDoc->document = $document;
        $validDoc->type     = false;
        return $validDoc;
    }
}
