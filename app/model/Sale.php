<?php

class Sale extends TRecord
{
    const TABLENAME  = 'sale';
    const PRIMARYKEY = 'id';
    const IDPOLICY   =  'serial'; // {max, serial}

    private $fk_store;
    private $fk_employee_cashier;
    private $fk_cashier;
    private $fk_customer;
    private $fk_salesman;
    private $fk_payment_method;
    private $fk_status;

    

    /**
     * Constructor method
     */
    public function __construct($id = NULL, $callObjectLoad = TRUE)
    {
        parent::__construct($id, $callObjectLoad);
        parent::addAttribute('number');
        parent::addAttribute('products_value');
        parent::addAttribute('payments_value');
        parent::addAttribute('discont_value');
        parent::addAttribute('total_value');
        parent::addAttribute('employee_sale');
        parent::addAttribute('sale_date');
        parent::addAttribute('invoiced');
        parent::addAttribute('invoice_ambient');
        parent::addAttribute('obs');
        parent::addAttribute('sys_obs');
        parent::addAttribute('invoice_number');
        parent::addAttribute('invoice_serie');
        parent::addAttribute('invoice_coupon');
        parent::addAttribute('invoice_xml');
        parent::addAttribute('payment_method');
        parent::addAttribute('store');
        parent::addAttribute('employee_cashier');
        parent::addAttribute('cashier');
        parent::addAttribute('customer');
        parent::addAttribute('salesman');
        parent::addAttribute('status');
            
    }

    /**
     * Method set_store
     * Sample of usage: $var->store = $object;
     * @param $object Instance of Store
     */
    public function set_fk_store(Store $object)
    {
        $this->fk_store = $object;
        $this->store = $object->id;
    }

    /**
     * Method get_fk_store
     * Sample of usage: $var->fk_store->attribute;
     * @returns Store instance
     */
    public function get_fk_store()
    {
        TTransaction::open('pos_system');
        // loads the associated object
        if (empty($this->fk_store))
            $this->fk_store = new Store($this->store);
        TTransaction::close();
        // returns the associated object
        return $this->fk_store;
    }
    /**
     * Method set_user
     * Sample of usage: $var->user = $object;
     * @param $object Instance of User
     */
    public function set_fk_employee_cashier(User $object)
    {
        $this->fk_employee_cashier = $object;
        $this->employee_cashier = $object->id;
    }

    /**
     * Method get_fk_employee_cashier
     * Sample of usage: $var->fk_employee_cashier->attribute;
     * @returns User instance
     */
    public function get_fk_employee_cashier()
    {
        TTransaction::open('pos_system');
        // loads the associated object
        if (empty($this->fk_employee_cashier))
            $this->fk_employee_cashier = new User($this->employee_cashier);
        TTransaction::close();
        // returns the associated object
        return $this->fk_employee_cashier;
    }
    /**
     * Method set_cashier
     * Sample of usage: $var->cashier = $object;
     * @param $object Instance of Cashier
     */
    public function set_fk_cashier(Cashier $object)
    {
        $this->fk_cashier = $object;
        $this->cashier = $object->id;
    }

    /**
     * Method get_fk_cashier
     * Sample of usage: $var->fk_cashier->attribute;
     * @returns Cashier instance
     */
    public function get_fk_cashier()
    {
        TTransaction::open('pos_system');
        // loads the associated object
        if (empty($this->fk_cashier))
            $this->fk_cashier = new Cashier($this->cashier);
        TTransaction::close();
        // returns the associated object
        return $this->fk_cashier;
    }
    /**
     * Method set_user
     * Sample of usage: $var->user = $object;
     * @param $object Instance of User
     */
    public function set_fk_customer(User $object)
    {
        $this->fk_customer = $object;
        $this->customer = $object->id;
    }

    /**
     * Method get_fk_customer
     * Sample of usage: $var->fk_customer->attribute;
     * @returns User instance
     */
    public function get_fk_customer()
    {
        TTransaction::open('pos_system');
        // loads the associated object
        if (empty($this->fk_customer))
            $this->fk_customer = new User($this->customer);
        TTransaction::close();
        // returns the associated object
        return $this->fk_customer;
    }
    /**
     * Method set_user
     * Sample of usage: $var->user = $object;
     * @param $object Instance of User
     */
    public function set_fk_salesman(User $object)
    {
        $this->fk_salesman = $object;
        $this->salesman = $object->id;
    }

    /**
     * Method get_fk_salesman
     * Sample of usage: $var->fk_salesman->attribute;
     * @returns User instance
     */
    public function get_fk_salesman()
    {
        TTransaction::open('pos_system');
        // loads the associated object
        if (empty($this->fk_salesman))
            $this->fk_salesman = new User($this->salesman);
        TTransaction::close();
        // returns the associated object
        return $this->fk_salesman;
    }
    /**
     * Method set_payment_method
     * Sample of usage: $var->payment_method = $object;
     * @param $object Instance of PaymentMethod
     */
    public function set_fk_payment_method(PaymentMethod $object)
    {
        $this->fk_payment_method = $object;
        $this->payment_method = $object->id;
    }

    /**
     * Method get_fk_payment_method
     * Sample of usage: $var->fk_payment_method->attribute;
     * @returns PaymentMethod instance
     */
    public function get_fk_payment_method()
    {
        TTransaction::open('pos_system');
        // loads the associated object
        if (empty($this->fk_payment_method))
            $this->fk_payment_method = new PaymentMethod($this->payment_method);
        TTransaction::close();
        // returns the associated object
        return $this->fk_payment_method;
    }
    /**
     * Method set_status
     * Sample of usage: $var->status = $object;
     * @param $object Instance of Status
     */
    public function set_fk_status(Status $object)
    {
        $this->fk_status = $object;
        $this->status = $object->id;
    }

    /**
     * Method get_fk_status
     * Sample of usage: $var->fk_status->attribute;
     * @returns Status instance
     */
    public function get_fk_status()
    {
    
        // loads the associated object
        if (empty($this->fk_status))
            $this->fk_status = new Status($this->status);
    
        // returns the associated object
        return $this->fk_status;
    }

    /**
     * Method getSalePayments
     */
    public function getSalePayments()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('sale', '=', $this->id));
        return SalePayment::getObjects( $criteria );
    }
    /**
     * Method getSaleItems
     */
    public function getSaleItems()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('sale', '=', $this->id));
        return SaleItem::getObjects( $criteria );
    }
    /**
     * Method getSaleDisconts
     */
    public function getSaleDisconts()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('sale', '=', $this->id));
        return SaleDiscont::getObjects( $criteria );
    }
    /**
     * Method getItemCupoms
     */
    public function getItemCupoms()
    {
        $criteria = new TCriteria;
        $criteria->add(new TFilter('sale', '=', $this->id));
        return ItemCupom::getObjects( $criteria );
    }

    public function set_sale_payment_fk_sale_to_string($sale_payment_fk_sale_to_string)
    {
        if(is_array($sale_payment_fk_sale_to_string))
        {
            $values = Sale::where('id', 'in', $sale_payment_fk_sale_to_string)->getIndexedArray('id', 'id');
            $this->sale_payment_fk_sale_to_string = implode(', ', $values);
        }
        else
        {
            $this->sale_payment_fk_sale_to_string = $sale_payment_fk_sale_to_string;
        }

        $this->vdata['sale_payment_fk_sale_to_string'] = $this->sale_payment_fk_sale_to_string;
    }

    public function get_sale_payment_fk_sale_to_string()
    {
        if(!empty($this->sale_payment_fk_sale_to_string))
        {
            return $this->sale_payment_fk_sale_to_string;
        }
    
        $values = SalePayment::where('sale', '=', $this->id)->getIndexedArray('sale','{fk_sale->id}');
        return implode(', ', $values);
    }

    public function set_sale_item_fk_sale_to_string($sale_item_fk_sale_to_string)
    {
        if(is_array($sale_item_fk_sale_to_string))
        {
            $values = Sale::where('id', 'in', $sale_item_fk_sale_to_string)->getIndexedArray('id', 'id');
            $this->sale_item_fk_sale_to_string = implode(', ', $values);
        }
        else
        {
            $this->sale_item_fk_sale_to_string = $sale_item_fk_sale_to_string;
        }

        $this->vdata['sale_item_fk_sale_to_string'] = $this->sale_item_fk_sale_to_string;
    }

    public function get_sale_item_fk_sale_to_string()
    {
        if(!empty($this->sale_item_fk_sale_to_string))
        {
            return $this->sale_item_fk_sale_to_string;
        }
    
        $values = SaleItem::where('sale', '=', $this->id)->getIndexedArray('sale','{fk_sale->id}');
        return implode(', ', $values);
    }

    public function set_sale_discont_fk_sale_to_string($sale_discont_fk_sale_to_string)
    {
        if(is_array($sale_discont_fk_sale_to_string))
        {
            $values = Sale::where('id', 'in', $sale_discont_fk_sale_to_string)->getIndexedArray('id', 'id');
            $this->sale_discont_fk_sale_to_string = implode(', ', $values);
        }
        else
        {
            $this->sale_discont_fk_sale_to_string = $sale_discont_fk_sale_to_string;
        }

        $this->vdata['sale_discont_fk_sale_to_string'] = $this->sale_discont_fk_sale_to_string;
    }

    public function get_sale_discont_fk_sale_to_string()
    {
        if(!empty($this->sale_discont_fk_sale_to_string))
        {
            return $this->sale_discont_fk_sale_to_string;
        }
    
        $values = SaleDiscont::where('sale', '=', $this->id)->getIndexedArray('sale','{fk_sale->id}');
        return implode(', ', $values);
    }

    public function set_item_cupom_fk_sale_to_string($item_cupom_fk_sale_to_string)
    {
        if(is_array($item_cupom_fk_sale_to_string))
        {
            $values = Sale::where('id', 'in', $item_cupom_fk_sale_to_string)->getIndexedArray('id', 'id');
            $this->item_cupom_fk_sale_to_string = implode(', ', $values);
        }
        else
        {
            $this->item_cupom_fk_sale_to_string = $item_cupom_fk_sale_to_string;
        }

        $this->vdata['item_cupom_fk_sale_to_string'] = $this->item_cupom_fk_sale_to_string;
    }

    public function get_item_cupom_fk_sale_to_string()
    {
        if(!empty($this->item_cupom_fk_sale_to_string))
        {
            return $this->item_cupom_fk_sale_to_string;
        }
    
        $values = ItemCupom::where('sale', '=', $this->id)->getIndexedArray('sale','{fk_sale->id}');
        return implode(', ', $values);
    }

    public function set_item_cupom_fk_sale_item_to_string($item_cupom_fk_sale_item_to_string)
    {
        if(is_array($item_cupom_fk_sale_item_to_string))
        {
            $values = SaleItem::where('id', 'in', $item_cupom_fk_sale_item_to_string)->getIndexedArray('id', 'id');
            $this->item_cupom_fk_sale_item_to_string = implode(', ', $values);
        }
        else
        {
            $this->item_cupom_fk_sale_item_to_string = $item_cupom_fk_sale_item_to_string;
        }

        $this->vdata['item_cupom_fk_sale_item_to_string'] = $this->item_cupom_fk_sale_item_to_string;
    }

    public function get_item_cupom_fk_sale_item_to_string()
    {
        if(!empty($this->item_cupom_fk_sale_item_to_string))
        {
            return $this->item_cupom_fk_sale_item_to_string;
        }
    
        $values = ItemCupom::where('sale', '=', $this->id)->getIndexedArray('sale_item','{fk_sale_item->id}');
        return implode(', ', $values);
    }

    
}

