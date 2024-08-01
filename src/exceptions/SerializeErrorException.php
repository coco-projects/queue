<?php

    namespace Coco\queue\exceptions;

    class SerializeErrorException extends \RuntimeException
    {
        public function __construct($error, $mission)
        {
            $error = '[反序列化出错] : ' . "[{$error}][{$mission}]";
            parent::__construct($error);
        }

    }

