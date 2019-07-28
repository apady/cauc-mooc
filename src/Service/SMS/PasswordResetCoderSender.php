<?php

namespace App\Service\SMS;


class PasswordResetCoderSender extends RegisterCodeSender
{
    public function setCode($code){
        $this->code=$code;
        $this->SMSRequest->setTemplateCode("SMS_153327270");
        $this->SMSRequest->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "code"=>$this->code,
        ), JSON_UNESCAPED_UNICODE));

        return $this;
    }
}