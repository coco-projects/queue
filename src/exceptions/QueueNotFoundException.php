<?php

    namespace Coco\queue\exceptions;

    class QueueNotFoundException extends \RuntimeException
    {
        public function __construct($name)
        {
            $error = '[不存在的队列] : ' . "[{$name}]";
            parent::__construct($error);
        }

    }

