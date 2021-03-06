<?php

namespace Utill\MQ;

interface ImessagePublisher{
    
    /**
     * basic message queue publisher
     * @param \Exception $exception
     * @param array $parameters
     * @author Okan CIRAN
     */
    public function publishMessage($exception = null, array $parameters = array());
    
}
