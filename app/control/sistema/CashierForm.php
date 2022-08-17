<?php

class CashierForm extends TPage
{
    protected $form;
    private $formFields = [];
    private static $database = 'pos_system';
    private static $activeRecord = 'Cashier';
    private static $primaryKey = 'id';
    private static $formName = 'form_CashierForm';

    /**
     * Form constructor
     * @param $param Request
     */
    public function __construct( $param )
    {
        parent::__construct();

        if(!empty($param['target_container']))
        {
            $this->adianti_target_container = $param['target_container'];
        }

        // creates the form
        $this->form = new BootstrapFormBuilder(self::$formName);
        // define the form title
        $this->form->setFormTitle("Cadastro de cashier");


        $id = new TEntry('id');
        $name = new TEntry('name');
        $cashier_type = new TEntry('cashier_type');
        $user_authenticated = new TDBCombo('user_authenticated', 'pos_system', 'User', 'id', '{id}','id asc'  );
        $store = new TDBCombo('store', 'pos_system', 'Store', 'id', '{social_name}','social_name asc'  );

        $name->addValidation("Name", new TRequiredValidator()); 
        $cashier_type->addValidation("android / pos desktop", new TRequiredValidator()); 
        $store->addValidation("Store", new TRequiredValidator()); 

        $id->setEditable(false);
        $name->setMaxLength(20);
        $cashier_type->setValue('1');

        $store->enableSearch();
        $user_authenticated->enableSearch();

        $id->setSize(100);
        $name->setSize('100%');
        $store->setSize('100%');
        $cashier_type->setSize('100%');
        $user_authenticated->setSize('100%');

        $row1 = $this->form->addFields([new TLabel("Id:", null, '14px', null, '100%'),$id],[new TLabel("Name:", '#ff0000', '14px', null, '100%'),$name]);
        $row1->layout = ['col-sm-6','col-sm-6'];

        $row2 = $this->form->addFields([new TLabel("android / pos desktop:", '#ff0000', '14px', null, '100%'),$cashier_type],[new TLabel("User authenticated:", null, '14px', null, '100%'),$user_authenticated]);
        $row2->layout = ['col-sm-6','col-sm-6'];

        $row3 = $this->form->addFields([new TLabel("Store:", '#ff0000', '14px', null, '100%'),$store],[]);
        $row3->layout = ['col-sm-6','col-sm-6'];

        // create the form actions
        $btn_onsave = $this->form->addAction("Salvar", new TAction([$this, 'onSave']), 'fas:save #ffffff');
        $this->btn_onsave = $btn_onsave;
        $btn_onsave->addStyleClass('btn-primary'); 

        $btn_onclear = $this->form->addAction("Limpar formulário", new TAction([$this, 'onClear']), 'fas:eraser #dd5a43');
        $this->btn_onclear = $btn_onclear;

        $btn_onshow = $this->form->addAction("Voltar", new TAction(['CashierList', 'onShow']), 'fas:arrow-left #000000');
        $this->btn_onshow = $btn_onshow;

        // vertical box container
        $container = new TVBox;
        $container->style = 'width: 100%';
        $container->class = 'form-container';
        if(empty($param['target_container']))
        {
            $container->add(TBreadCrumb::create(["Sistema","Cadastro de cashier"]));
        }
        $container->add($this->form);

        parent::add($container);

    }

    public function onSave($param = null) 
    {
        try
        {
            TTransaction::open(self::$database); // open a transaction

            $messageAction = null;

            $this->form->validate(); // validate form data

            $object = new Cashier(); // create an empty object 

            $data = $this->form->getData(); // get form data as array
            $object->fromArray( (array) $data); // load the object with data

            $object->store(); // save the object 

            $loadPageParam = [];

            if(!empty($param['target_container']))
            {
                $loadPageParam['target_container'] = $param['target_container'];
            }

            // get the generated {PRIMARY_KEY}
            $data->id = $object->id; 

            $this->form->setData($data); // fill form data
            TTransaction::close(); // close the transaction

            TToast::show('success', "Registro salvo", 'topRight', 'far:check-circle');
            TApplication::loadPage('CashierList', 'onShow', $loadPageParam); 

        }
        catch (Exception $e) // in case of exception
        {
            //</catchAutoCode> 

            new TMessage('error', $e->getMessage()); // shows the exception error message
            $this->form->setData( $this->form->getData() ); // keep form data
            TTransaction::rollback(); // undo all pending operations
        }
    }

    public function onEdit( $param )
    {
        try
        {
            if (isset($param['key']))
            {
                $key = $param['key'];  // get the parameter $key
                TTransaction::open(self::$database); // open a transaction

                $object = new Cashier($key); // instantiates the Active Record 

                $this->form->setData($object); // fill the form 

                TTransaction::close(); // close the transaction 
            }
            else
            {
                $this->form->clear();
            }
        }
        catch (Exception $e) // in case of exception
        {
            new TMessage('error', $e->getMessage()); // shows the exception error message
            TTransaction::rollback(); // undo all pending operations
        }
    }

    /**
     * Clear form data
     * @param $param Request
     */
    public function onClear( $param )
    {
        $this->form->clear(true);

    }

    public function onShow($param = null)
    {

    } 

}

