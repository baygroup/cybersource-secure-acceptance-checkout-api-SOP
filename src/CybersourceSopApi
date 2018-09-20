<?php
namespace Baygroup\Cybersource;

class CybersourceSopApi
{
    protected $access_key;
    protected $secret_key;
    protected $signature_method;
    protected $signed_field_names;
    protected $unsigned_field_names;

    public function __construct($access_key,$secret_key,$signature_method = 'sha256')
    {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->signature_method = $signature_method;
    }


    public function sign ($params)
    {
        return $this->signData(buildDataToSign($params), $this->secret_key);
    }

    public function signData($data, $secretKey = 'default')
    {

        if($secretKey == 'default') $secretKey = $this->secret_key;

        return \base64_encode(hash_hmac($this->signature_method, $data, $secretKey, true));
    }

    public function buildDataToSign(array $params)
    {
        $signedFieldNames = explode(",",$params["signed_field_names"]);
        foreach ($signedFieldNames as $field) {
            $dataToSign[] = $field . "=" . $params[$field];
        }
        return $this->commaSeparate($dataToSign);
    }

    public function commaSeparate ($dataToSign)
    {
        return implode(",",$dataToSign);
    }

    public function sop_api_key($locale= 'en')
    {
        $hidden_input = "<input type='hidden' name='access_key' value='".$this->access_key."'><br>
                  <input type='hidden' name='transaction_uuid' value='".uniqid()."'><br>
                  <input type='hidden' name='locale' value='".$locale."'><br>
                  <input type='hidden' name='signed_date_time' value='".\gmdate("Y-m-d\TH:i:s\Z")."'>
                 ";

        return $hidden_input;
    }

    public function generate_signed_data_fields(array $form_fields)
    {
        $this->signed_field_names = '';

        $point = 0;
        foreach($form_fields as $key => $value)
        {
            if($point == 0 ) $this->signed_field_names = $key;
            $this->signed_field_names .= ','.$key;
            $point ++;
        }

        $this->signed_field_names = substr($this->signed_field_names, 0,-1);
        $this->signed_field_names .= $this->signed_field_names .',signed_field_names,unsigned_field_names';

        return $this->signed_field_names;
    }

    public function generate_unsigned_data_fields(array $form_fields)
    {
        $this->unsigned_field_names = '';

        $point = 0;
        foreach($form_fields as $key => $value)
        {
            if($point == 0 ) $this->unsigned_field_names = $key;
            $this->unsigned_field_names .= ','.$key;
            $point ++;
        }

        $this->unsigned_field_names = substr($this->unsigned_field_names, 0,-1);

        return $this->unsigned_field_names;
    }

}
