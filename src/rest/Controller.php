<?php

namespace yidas\rest;

use yidas\http\Request;
use yidas\http\Response;

/**
 * RESTful API Controller
 * 
 * @author  Nick Tsai <myintaer@gmail.com>
 * @version 1.3.1
 * @link    https://github.com/yidas/codeigniter-rest/
 * @see     https://github.com/yidas/codeigniter-rest/blob/master/examples/RestController.php
 * 
 * Controller extending:
 * ```php
 * class My_controller extends yidas\rest\Controller {}
 * ```
 * 
 * Route setting:
 * ```php
 * $route['resource_name'] = '[Controller]/route';
 * $route['resource_name/(:num)'] = '[Controller]/route/$1';
 * ```
 */
class Controller extends \CI_Controller
{
    /**
     * RESTful API resource routes
     * 
     * public function index() {}
     * protected function store($requestData=null) {}
     * protected function show($resourceID) {}
     * protected function update($resourceID, $requestData=null) {}
     * protected function delete($resourceID=null) {}
     * 
     * @var array RESTful API table of routes & actions
     */
    protected $routes = [
        'index' => 'index',
        'store' => 'store',
        'show' => 'show',
        'update' => 'update',
        'delete' => 'delete',
    ];

    /**
     * Pre-setting format
     * 
     * @var string yidas\http\Response format
     */
    protected $format;

    /**
     * Body Format usage switch
     * 
     * @var bool Default $bodyFormat for json()
     */
    protected $bodyFormat = false;

    /**
     * @var object yidas\http\Request;
     */
    protected $request;

    /**
     * @var object yidas\http\Response;
     */
    protected $response;
    
    
    function __construct() 
    {
        parent::__construct();
        
        // Request initialization
        $this->request = new Request;
        // Response initialization
        $this->response = new Response;

        // Response setting
        if ($this->format) {
		    $this->response->setFormat($this->format);
        }
    }

    /**
     * Route bootstrap
     * 
     * For Codeigniter route setting to implement RESTful API
     * 
     * @param int|string Resource ID
     */
    public function route($resourceID=NULL)
    {
        switch ($this->request->getMethod()) {
            case 'POST':
                if (!$resourceID) {
                    return $this->_action(['store', $this->request->getBodyParams()]);
                }
                break;
            case 'PUT':
            case 'PATCH':
                if ($resourceID) {
                    return $this->_action(['update', $resourceID, $this->request->getBodyParams()]);
                }
                break;
            case 'DELETE':
                if ($resourceID) {
                    return $this->_action(['delete', $resourceID]);
                } else {
                    return $this->_action(['delete']);
                }
                break;
            case 'GET':
            default:
                if ($resourceID) {
                    return $this->_action(['show', $resourceID]);
                } else {
                    return $this->_action(['index']);
                }
                break;
        }
    }

    /**
     * Alias of route()
     */
    public function ajax($resourceID=NULL)
    {
        return $this->route($resourceID);
    }

    /**
     * Output by JSON format with optinal body format
     * 
     * @deprecated 1.3.0
     * @param array|mixed Callback data body, false will remove body key
     * @param bool Enable body format
     * @param int HTTP Status Code
     * @param string Callback message
     * @return string Response body data
     * 
     * @example
     *  json(false, true, 401, 'Login Required', 'Unauthorized');
     */
    protected function json($data=[], $bodyFormat=null, $statusCode=null, $message=null)
    {
        // Check default Body Format setting if not assigning
        $bodyFormat = ($bodyFormat!==null) ? $bodyFormat : $this->bodyFormat;
        
        if ($bodyFormat) {
            // Pack data
            $data = $this->_format($statusCode, $message, $data);
        } else {
            // JSON standard of RFC4627
            $data = is_array($data) ? $data : [$data];
        }

        return $this->response->json($data, $statusCode);
    }

    /**
     * Format Response Data
     * 
     * @deprecated 1.3.0
     * @param int Callback status code
     * @param string Callback status text
     * @param array|mixed|bool Callback data body, false will remove body key 
     * @return array Formated array data
     */
    protected function _format($statusCode=null, $message=null, $body=false)
    {
        $format = [];
        // Status Code field is necessary
        $format['code'] = ($statusCode) 
            ?: $this->response->getStatusCode();
        // Message field
        if ($message) {
            $format['message'] = $message;
        }
        // Body field
        if ($body !== false) {
            $format['data'] = $body;
        }
        
        return $format;
    }

    /**
     * Pack array data into body format
     * 
     * You could override this method for your application standard
     * 
     * @param array|mixed $data Original data
     * @param int HTTP Status Code
     * @param string Callback message
     * @return array Packed data
     * @example
     *  $packedData = pack(['bar'=>'foo], 401, 'Login Required');
     */
    protected function pack($data, $statusCode=200, $message=null)
    {
        $packBody = [];

        // Status Code
        if ($statusCode) {
            
            $packBody['code'] = $statusCode;
        }
        // Message
        if ($message) {
            
            $packBody['message'] = $message;
        }
        // Data
        if (is_array($data) || is_string($data)) {
            
            $packBody['data'] = $data;
        }
        
        return $packBody;
    }

    /**
     * Default Action
     */
    protected function _defaultAction()
    {
        /* Response sample code */
        // $response->data = ['foo'=>'bar'];
		// $response->setStatusCode(401);
        
        // Codeigniter 404 Error Handling
        show_404();
    }

    /**
     * Action processor for route
     * 
     * @param array Elements contains method for first and params for others 
     */
    private function _action($params)
    {
        // Shift and get the method
        $method = array_shift($params);

        if (!isset($this->routes[$method])) {
            $this->_defaultAction();
        }

        // Get corresponding method name
        $method = $this->routes[$method];

        if (!method_exists($this, $method)) {
            $this->_defaultAction();
        }

        return call_user_func_array([$this, $method], $params);
    }
}
