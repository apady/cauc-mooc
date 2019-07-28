<?php

namespace App\Service\SMS;

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;

abstract class SmsSender{


    private $acsClient;
    //产品域名,开发者无需替换
    private $domain ;

    // 暂时不支持多Region
    private  $region ;

    // 服务结点
    private  $endPointName;

    //产品名称:云通信短信服务API产品,开发者无需替换
    private $product ;

    private $profile;

    private $phoneNumber;


    protected $SMSRequest;


    public function __construct($SMSAccessKeyId,$SMSAccessKeySecret){
        // 加载区域结点配置
        Config::load();
        $this->region= "cn-hangzhou";
        $this->endPointName = "cn-hangzhou";
        $this->product= "Dysmsapi";
        $this->domain= "dysmsapi.aliyuncs.com";
        $this->SMSRequest = new SendSmsRequest();
        $this->SMSRequest->setProtocol("https");
        $this->SMSRequest->setSignName("航大云课");

        // 初始化AcsClient用于发起请求
        if($this->acsClient==null){
            //初始化acsClient,暂不支持region化
            $this->profile = DefaultProfile::getProfile($this->region, $SMSAccessKeyId, $SMSAccessKeySecret);

            // 增加服务结点
            DefaultProfile::addEndpoint($this->endPointName, $this->region, $this->product, $this->domain);
            $this->acsClient = new DefaultAcsClient($this->profile);

        }
    }


    /**
     * 发送短信

     */
    public function sendSms() {

        // 必填，设置短信接收号码
        $this->SMSRequest->setPhoneNumbers($this->getPhoneNumber());
        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($this->SMSRequest);

        return $acsResponse;
    }

    public function setPhoneNumber($phoneNumber){
        $this->phoneNumber=$phoneNumber;

        return $this;
    }


    public function getPhoneNumber(){
       return  $this->phoneNumber;
    }

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    private function getAcsClient() {

        return $this->acsClient;
    }



}