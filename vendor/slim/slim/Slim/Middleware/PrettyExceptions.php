<?php
/**
 * Slim - a micro PHP 5 framework
 *
 * @author      Josh Lockhart <info@slimframework.com>
 * @copyright   2011 Josh Lockhart
 * @link        http://www.slimframework.com
 * @license     http://www.slimframework.com/license
 * @version     2.6.1
 * @package     Slim
 *
 * MIT LICENSE
 *
 * Permission is hereby granted, free of charge, to any person obtaining
 * a copy of this software and associated documentation files (the
 * "Software"), to deal in the Software without restriction, including
 * without limitation the rights to use, copy, modify, merge, publish,
 * distribute, sublicense, and/or sell copies of the Software, and to
 * permit persons to whom the Software is furnished to do so, subject to
 * the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
 * LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
 * WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace Slim\Middleware;

/**
 * Pretty Exceptions
 *
 * This middleware catches any Exception thrown by the surrounded
 * application and displays a developer-friendly diagnostic screen.
 *
 * @package Slim
 * @author  Josh Lockhart
 * @since   1.0.0
 */
class PrettyExceptions extends \Slim\Middleware implements \Utill\MQ\ImessagePublisher
{
    /**
     * @var array
     */
    protected $settings;

    /**
     * Constructor
     * @param array $settings
     */
    public function __construct($settings = array())
    {
        $this->settings = $settings;
    }

    /**
     * Call
     */
    public function call()
    {
        //print_r('---PrettyExceptions call method---');
        try {
            $this->next->call();
        } catch (\Exception $e) {
            //print_r('--pretty exceptions call()--');
            //
            // publis exception on message queue
        /**
         * slim_codebase was turned off during work.
         * When to use rabbitmq will be opened
         * @author Okan CIRAN
         * @since 30.11.2016
         */
         //   $this->publishMessage($e);
            
            $log = $this->app->getLog(); // Force Slim to append log to env if not already
            $env = $this->app->environment();
            $env['slim.log'] = $log;
            //$env['slim.log']->error($e);
            $this->app->contentType('text/html');
            $this->app->response()->status(500);
            $this->app->response()->body($this->renderBody($env, $e));
            //print_r(json_encode(serialize($e)));
            
            
        }
    }
    
    /**
     * message wrapper function
     * @param \Exception $e
     * @author Okan CIRAN
     * @todo Time zone can be made parametric(a class constant call maybe)
     */
    public function publishMessage($e = null, array $params = array()) {
        //date_default_timezone_set('Europe/Istanbul');
        
        $exceptionMQ = new \Utill\MQ\exceptionMQ();
        $exceptionMQ->setChannelProperties(array('queue.name' => $this->app->container['settings']['exceptions.rabbitMQ.queue.name']));
        $message = new \Utill\MQ\MessageMQ\MQMessage();
        ;
        $message->setMessageBody(array('message' => $e->getMessage(), 
                                       'file' => $e->getFile(),
                                       'line' => $e->getLine(),
                                       'trace' => $e->getTraceAsString(),
                                       'time'  => date('l jS \of F Y h:i:s A'),
                                       'serial' => $this->app->container['settings']['request.serial'],
                                       'logFormat' => $this->app->container['settings']['exceptions.rabbitMQ.logging']));
        //print_r($message->getMesssageBody());
        $message->setMessageProperties(array('delivery_mode' => 2,
                                             'content_type' => 'application/json'));
        $exceptionMQ->setMessage($message->setMessage());
        $exceptionMQ->basicPublish();
        
        /**
         * Exception loglarını Message queue ve 
         * service manager üzerinden yönetmek için yazılmıştır.
         * @author Okan CIRAN
         */
        /*$exceptionMQ = $this->app->getMQManager()->get('MQException');
        $exceptionMQ->setChannelProperties(array('queue.name' => $this->container['settings']['restEntry.rabbitMQ.queue.name']));
        $message = new \Utill\MQ\MessageMQ\MQMessage();
        $message->setMessageBody(array('message' => $e->getMessage(), 
                                       'file' => $e->getFile(),
                                       'line' => $e->getLine(),
                                       'trace' => $e->getTraceAsString() ,
                                       'time'  => date('l jS \of F Y h:i:s A'),
                                       'serial' => $this->app->container['settings']['request.serial'],
                                       'logFormat' => $this->app->container['settings']['exceptions.rabbitMQ.logging']));
        $message->setMessageProperties(array('delivery_mode' => 2,
                                             'content_type' => 'application/json'));
        $exceptionMQ->setMessage($message->setMessage());
        $exceptionMQ->basicPublish();*/
        
    }

    /**
     * Render response body
     * @param  array      $env
     * @param  \Exception $exception
     * @return string
     */
    protected function renderBody(&$env, $exception)
    {
        $title = 'Slim Application Error';
        $code = $exception->getCode();
        $message = $exception->getMessage();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = str_replace(array('#', "\n"), array('<div>#', '</div>'), $exception->getTraceAsString());
        $html = sprintf('<h1>%s</h1>', $title);
        $html .= '<p>The application could not run because of the following error:</p>';
        $html .= '<h2>Details</h2>';
        $html .= sprintf('<div><strong>Type:</strong> %s</div>', get_class($exception));
        if ($code) {
            $html .= sprintf('<div><strong>Code:</strong> %s</div>', $code);
        }
        if ($message) {
            $html .= sprintf('<div><strong>Message:</strong> %s</div>', $message);
        }
        if ($file) {
            $html .= sprintf('<div><strong>File:</strong> %s</div>', $file);
        }
        if ($line) {
            $html .= sprintf('<div><strong>Line:</strong> %s</div>', $line);
        }
        if ($trace) {
            $html .= '<h2>Trace</h2>';
            $html .= sprintf('<pre>%s</pre>', $trace);
        }

        return sprintf("<html><head><title>%s</title><style>body{margin:0;padding:30px;font:12px/1.5 Helvetica,Arial,Verdana,sans-serif;}h1{margin:0;font-size:48px;font-weight:normal;line-height:48px;}strong{display:inline-block;width:65px;}</style></head><body>%s</body></html>", $title, $html);
    }
}
