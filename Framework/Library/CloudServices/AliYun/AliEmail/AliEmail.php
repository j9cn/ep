<?php
/**
 * Created by PhpStorm.
 * User: J.H
 * Date: 2018/5/4
 * Time: 23:52
 */

namespace EP\Library\CloudServices\AliYun\AliEmail;


use EP\Exception\ELog;
use EP\Library\CloudServices\AliYun\Common\BaseApi;

class AliEmail extends BaseApi
{
    function __construct(
        $config = array('accessKeyId' => '', 'accessKeySecret' => ''), $account = array('name' => '', 'alia' => '')
    ) {
        parent::__construct();
        $this->AccessKeyId = $config['accessKeyId'];
        $this->AccessKeySecret = $config['accessKeySecret'];
        $this->Version = '2015-11-23';

        if (!empty($account['name'])) {
            $this->useAccount($account['name'], $account['alia']);
        }
    }

    /**
     * 群发模版邮件
     *
     * @param $templateName
     * @param $receiversName
     *
     * @return $this
     */
    function batchSendMail($templateName, $receiversName)
    {
        $this->setParam('Action', 'BatchSendMail');
        $this->setParam('TemplateName', $templateName);
        $this->setParam('ReceiversName', $receiversName);
        $this->setParam('AddressType', 0);
        $this->buildParams();
        return $this;
    }

    /**
     * 发送邮件
     *
     * @param string|array $to
     * @param string $subject
     * @param string $body
     * @param bool $isHtml
     * @param string $replyTo 'true|false'
     *
     * @return $this
     */
    function singleSendMail($to, $subject = '标题', $body = '内容', $isHtml = true, $replyTo = 'false')
    {
        if (empty($to)) {
            ELog::error('无效收件人');
        }
        $this->setParam('Action', 'SingleSendMail');
        $this->setParam('ReplyToAddress', $replyTo);
        $this->setParam('AddressType', 1);

        if (is_array($to)) {
            $to = implode(',', $to);
        }
        $this->setParam('ToAddress', $to);
        $this->setParam('Subject', $subject);
        if ($isHtml) {
            $this->htmlBody($body);
        } else {
            $this->textBody($body);
        }
        $this->buildParams();
        return $this;
    }

    /**
     * 使用指定帐号发信
     *
     * @param string $account
     * @param string $alias
     *
     * @return $this
     */
    function useAccount($account = 'xxx@xx.com', $alias = '')
    {
        $this->setParam('AccountName', $account);
        if ($alias) {
            $this->setParam('FromAlias', $alias);
        }
        return $this;
    }


    private function htmlBody($htmlBody = '')
    {
        $this->setParam('HtmlBody', $htmlBody);
    }

    private function textBody($textBody = '')
    {
        $this->setParam('TextBody', $textBody);
    }
}