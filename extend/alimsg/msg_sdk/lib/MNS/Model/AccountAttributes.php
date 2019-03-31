<?php
namespace AliyunMNS\Model;

use AliyunMNS\Constants;

/**
 * Please refer to
 * https://docs.aliyun.com/?spm=#/pub/mns/api_reference/intro&intro
 * for more details
 */
class AccountAttributes
{
    private $loggingBucket;

    public function __construct(
        $loggingBucket = NULL)
    {
        $this->loggingBucket = $loggingBucket;
    }

    public function setLoggingBucket($loggingBucket)
    {
        $this->loggingBucket = $loggingBucket;
    }

    public function getLoggingBucket()
    {
        return $this->loggingBucket;
    }

    public function writeXML(\XMLWriter $xmlWriter)
    {
        if ($this->loggingBucket !== NULL)
        {
            $xmlWriter->writeElement(Constants::LOGGING_BUCKET, $this->loggingBucket);
        }
    }

    static public function fromXML(\XMLReader $xmlReader)
    {
        $loggingBucket = NULL;

        while ($xmlReader->read())
        {
            if ($xmlReader->nodeType == \XMLReader::ELEMENT)
            {
                switch ($xmlReader->name) {
                case 'LoggingBucket':
                    $xmlReader->read();
                    if ($xmlReader->nodeType == \XMLReader::TEXT)
                    {
                        $loggingBucket = $xmlReader->value;
                    }
                    break;
                }
            }
        }

        $attributes = new AccountAttributes($loggingBucket);
        return $attributes;
    }
}

?>
