<?php
namespace AliyunMNS\Model;

use AliyunMNS\Constants;

/**
 * Please refer to
 * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/intro&intro
 * for more details
 */
class MessageAttributes
{
    // if both SmsAttributes and BatchSmsAttributes are set, only one will take effect
    private $attributes;

    public function __construct(
        $attributes = NULL)
    {
        $this->attributes = $attributes;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function writeXML(\XMLWriter $xmlWriter)
    {
        $xmlWriter->startELement(Constants::MESSAGE_ATTRIBUTES);
        if ($this->attributes != NULL)
        {
            if (is_array($this->attributes))
            {
                foreach ($this->attributes as $subAttributes)
                {
                    $subAttributes->writeXML($xmlWriter);
                }
            }
            else
            {
                $this->attributes->writeXML($xmlWriter);
            }
        }
        $xmlWriter->endElement();
    }
}

?>
