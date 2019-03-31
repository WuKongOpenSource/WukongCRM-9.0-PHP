<?php
namespace AliyunMNS;

class Constants
{
    const GMT_DATE_FORMAT = "D, d M Y H:i:s \\G\\M\\T";

    const MNS_VERSION_HEADER = "x-mns-version";
    const MNS_HEADER_PREFIX = "x-mns";
    const MNS_XML_NAMESPACE = "http://mns.aliyuncs.com/doc/v1/";

    const MNS_VERSION = "2015-06-06";
    const AUTHORIZATION = "Authorization";
    const MNS = "MNS";

    const CONTENT_LENGTH = "Content-Length";
    const CONTENT_TYPE = "Content-Type";
    const SECURITY_TOKEN = "security-token";
    const DIRECT_MAIL = "DirectMail";
    const DIRECT_SMS = "DirectSMS";
    const WEBSOCKET = "WebSocket";

    // XML Tag
    const ERROR = "Error";
    const ERRORS = "Errors";
    const DELAY_SECONDS = "DelaySeconds";
    const MAXIMUM_MESSAGE_SIZE = "MaximumMessageSize";
    const MESSAGE_RETENTION_PERIOD = "MessageRetentionPeriod";
    const VISIBILITY_TIMEOUT = "VisibilityTimeout";
    const POLLING_WAIT_SECONDS = "PollingWaitSeconds";
    const MESSAGE_BODY = "MessageBody";
    const PRIORITY = "Priority";
    const MESSAGE_ID = "MessageId";
    const MESSAGE_BODY_MD5 = "MessageBodyMD5";
    const ENQUEUE_TIME = "EnqueueTime";
    const NEXT_VISIBLE_TIME = "NextVisibleTime";
    const FIRST_DEQUEUE_TIME = "FirstDequeueTime";
    const RECEIPT_HANDLE = "ReceiptHandle";
    const RECEIPT_HANDLES = "ReceiptHandles";
    const DEQUEUE_COUNT = "DequeueCount";
    const ERROR_CODE = "ErrorCode";
    const ERROR_MESSAGE = "ErrorMessage";
    const ENDPOINT = "Endpoint";
    const STRATEGY = "NotifyStrategy";
    const CONTENT_FORMAT = "NotifyContentFormat";
    const LOGGING_BUCKET = "LoggingBucket";
    const LOGGING_ENABLED = "LoggingEnabled";
    const MESSAGE_ATTRIBUTES = "MessageAttributes";
    const SUBJECT = "Subject";
    const ACCOUNT_NAME = "AccountName";
    const ADDRESS_TYPE = "AddressType";
    const REPLY_TO_ADDRESS = "ReplyToAddress";
    const IS_HTML = "IsHtml";
    const FREE_SIGN_NAME = "FreeSignName";
    const TEMPLATE_CODE = "TemplateCode";
    const RECEIVER = "Receiver";
    const SMS_PARAMS = "SmsParams";
    const IMPORTANCE_LEVEL = "ImportanceLevel";

    // some MNS ErrorCodes
    const INVALID_ARGUMENT = "InvalidArgument";
    const QUEUE_ALREADY_EXIST = "QueueAlreadyExist";
    const QUEUE_NOT_EXIST = "QueueNotExist";
    const MALFORMED_XML = "MalformedXML";
    const MESSAGE_NOT_EXIST = "MessageNotExist";
    const RECEIPT_HANDLE_ERROR = "ReceiptHandleError";
    const BATCH_SEND_FAIL = "BatchSendFail";
    const BATCH_DELETE_FAIL = "BatchDeleteFail";

    const TOPIC_ALREADY_EXIST = "TopicAlreadyExist";
    const TOPIC_NOT_EXIST = "TopicNotExist";
    const SUBSCRIPTION_ALREADY_EXIST = "SubscriptionAlreadyExist";
    const SUBSCRIPTION_NOT_EXIST = "SubscriptionNotExist";
}

?>
