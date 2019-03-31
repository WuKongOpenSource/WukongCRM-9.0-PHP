<?php
namespace AliyunMNS\Traits;

use AliyunMNS\Constants;

trait MessagePropertiesForSend
{
    protected $messageBody;
    protected $delaySeconds;
    protected $priority;

    public function getMessageBody()
    {
        return $this->messageBody;
    }

    public function setMessageBody($messageBody)
    {
        $this->messageBody = $messageBody;
    }

    public function getDelaySeconds()
    {
        return $this->delaySeconds;
    }

    public function setDelaySeconds($delaySeconds)
    {
        $this->delaySeconds = $delaySeconds;
    }

    public function getPriority()
    {
        return $this->priority;
    }

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function writeMessagePropertiesForSendXML(\XMLWriter $xmlWriter, $base64)
    {
        if ($this->messageBody != NULL)
        {
            if ($base64 == TRUE) {
                $xmlWriter->writeElement(Constants::MESSAGE_BODY, base64_encode($this->messageBody));
            } else {
                $xmlWriter->writeElement(Constants::MESSAGE_BODY, $this->messageBody);
            }
        }
        if ($this->delaySeconds != NULL)
        {
            $xmlWriter->writeElement(Constants::DELAY_SECONDS, $this->delaySeconds);
        }
        if ($this->priority !== NULL)
        {
            $xmlWriter->writeElement(Constants::PRIORITY, $this->priority);
        }
    }
}

?>
