<?php

namespace Komoju;
use Symfony\Component\Config\Definition\Exception\Exception;
class WebhookEvent{

    public static function constructEvent($payload, $sigHeader, $secret){
        $res = WebhookSignature::verifyHeader($payload, $sigHeader, $secret);
        if(empty($res)){
            throw new Exception("verify_error");
        }
        $data = \json_decode($payload);
        $jsonError = \json_last_error();
        if (null === $data && \JSON_ERROR_NONE !== $jsonError) {
            $msg = "Invalid payload: {$payload} "
              . "(json_last_error() was {$jsonError})";

            throw new Exception\UnexpectedValueException($msg);
        }
        return $data;
    }
}