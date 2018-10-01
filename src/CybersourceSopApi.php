<?php
namespace Baygroup\Cybersource;

class CybersourceSopApi
{
    protected $access_key;
    protected $secret_key;
    protected $signature_method;
    protected $signed_field_names;
    protected $unsigned_field_names;
    protected $uniqid ;
    protected $sign_date_time;
    protected $locale;
    protected $profile_id;
    protected $params;
    protected $credit_info;
    protected $signature;

    public function __construct($access_key,$secret_key,$signature_method = 'sha256',$locale='en')
    {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->signature_method = $signature_method;
        $this->locale = $locale;
    }


    public function sign($params)
    {
        return $this->signData($this->buildDataToSign($params), $this->secret_key);
    }

    function signData($data, $secretKey)
    {
        return base64_encode(hash_hmac($this->signature_method, $data, $secretKey, true));
    }

    public function buildDataToSign($params) {

        $signedFieldNames = explode(",",$params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $params[$field];
        }
        return $this->commaSeparate($dataToSign);
    }

    public function pay($profile_id,array $params)
    {
        $this->profile_id = $profile_id;
        $this->mergeParamsWithProfileId($params);
        $configArray = $this->generateConfigArray();
        $this->mergeParamsWithConfig($configArray);
        $data = $this->generateSignedValue($this->params);// array_map(array($this, 'generateSignedValue'), $this->params, array_keys($this->params));
        $this->signature  =$this->sign($this->params);
        $this->mergeWithSignature();
        $this->submitForm();

    }

    public function mergeWithSignature()
    {
        $this->params = array_merge($this->params,['signature' => $this->signature]);
    }

    public function mergeParamsWithProfileId(array $params)
    {
        $this->params = array_merge(['profile_id' => $this->profile_id],$params);
    }

    public function mergeParamsWithConfig($params)
    {
        $this->params = array_merge($this->params ,$params);
    }

    public function generateConfigArray()
    {
        $this->uniqid = uniqid();
        $this->sign_date_time = gmdate("Y-m-d\TH:i:s\Z");
        return
            [
                'access_key' => $this->access_key,
                'transaction_uuid' => $this->uniqid,
                'signed_date_time' => $this->sign_date_time,
                'locale' => $this->locale
            ];

    }

    public function generateSignedValue($params)
    {
        $this->signed_field_names = '';
        $this->credit_info = [];
        $credit_card_information =
            [
                'card_type','card_number','card_expiry_date','card_cvn'
            ];

        foreach($params as $index => $value)
        {

            if(in_array($index ,$credit_card_information))
            {
                $this->credit_info = array_merge($this->credit_info,[$index => $value]);
                unset($params[$index]);
                continue;
            }
            $this->signed_field_names .= $index.',';
        }

        $this->params = $params;

        if(count($this->credit_info)) $this->generateUnsignedValue();

        $this->signed_field_names  = substr($this->signed_field_names,0,-1);

        $this->signed_field_names .= ',signed_field_names,unsigned_field_names';
        $this->params = array_merge($this->params,['signed_field_names' => $this->signed_field_names]);

        $this->params = array_merge($this->params,['unsigned_field_names' => $this->unsigned_field_names]);
        //do merge credit info at the end of the params later

        $this->params = array_merge($this->params,$this->credit_info);


        return $this->signed_field_names;
    }

    public function generateUnsignedValue()
    {
        $this->unsigned_field_names = '';

        foreach($this->credit_info as $index => $value)
        {
            $this->unsigned_field_names .= $index.',';
        }

        $this->unsigned_field_names = substr($this->unsigned_field_names,0,-1);

        return $this->unsigned_field_names;
    }

    public function getUnsignedArray()
    {
        return $this->credit_info;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function commaSeparate ($dataToSign)
    {
        return implode(",",$dataToSign);
    }

    public function generateParamHiddenInputFields()
    {
        $params = $this->params;
        $hidden_field = '';
        foreach($params as $name => $value) {
            $hidden_field .= /*$name.*/"<input type=\"hidden\" id=\"" . $name . "\" name=\"" . $name . "\" value=\"" . $value . "\"/>\n <br>";
        }

        return $hidden_field;
    }

    public function attachParamsWithDefaultUnsignedField()
    {
        array_merge($this->params,['unsigned_field_names' => 'card_type','card_number','card_expiry_date','card_cvn']);
    }

    public function submitForm()
    {
        $form = "<form method='POST' action='https://testsecureacceptance.cybersource.com/silent/pay' >";
        $form .=  $this->generateParamHiddenInputFields();
        $form .= "<button type='submit' style='display: none;' id='submit_payment'>Submit</button>";
        $form .= "</form>";

        $form .= "<br>";
        $form .= "<script>";
        $form .= "(function() {
                 document.getElementById('submit_payment').click();
             })();
         ";
        $form .= "</script>";
        return $form;
    }



}
