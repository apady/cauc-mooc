<?php


namespace App\Service\SMS;


class RegisterCodeSender extends SmsSender
{
    /* 验证码*/
    protected $code;

    public function getCode(){
        return  $this->code;
    }

    public function setCode($code){
        $this->code=$code;
        $this->SMSRequest->setTemplateCode("SMS_153332175");
        $this->SMSRequest->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "code"=>$this->code,
        ), JSON_UNESCAPED_UNICODE));

        return $this;
    }

}