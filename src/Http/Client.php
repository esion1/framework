<?php
/**
 * Kerisy Framework
 *
 * PHP Version 7
 *
 * @author          Jiaqing Zou <zoujiaqing@gmail.com>
 * @copyright      (c) 2015 putao.com, Inc.
 * @package         kerisy/framework
 * @subpackage      Http
 * @since           2015/11/11
 * @version         2.0.0
 */

namespace Kerisy\Http;

use Doctrine\Instantiator\Exception\InvalidArgumentException;
use GuzzleHttp\Client as Gclient;

class Client
{

    public function __construct()
    {
        $this->client = new Gclient;
    }

    public function get($url, $data = array())
    {
        return $this->request('GET', $url, $data);
    }

    public function post($url, $data = array(), $flag = true)
    {
        return $this->request('POST', $url, $data, $flag);
    }

    public function put($url, $data = array())
    {
        return $this->request('FILE', $url, $data);
    }

    public function request($type = 'GET', $url, $data = array(), $flag = true)
    {
        $fromData = array();

        if (is_array($data) && count($data) > 0) {
            if ($type == 'GET') {
                $fromData['query'] = $data;
            } elseif ($type == 'POST') {
                $fromData['form_params'] = $data;
            } elseif ($type == 'FILE') {
                $type = "POST";
                $fromData['multipart'] = $data;
            }
        }

		$response = $this->client->request($type, $url, $fromData);
		
		if ( $response->getStatusCode() != 200 )
		{
			return "Error code " . $response->getStatusCode();
		}
		
        // $res = $this->client->request($type, $url, $fromData)->getBody();
        return $flag ? json_decode($response->getBody(), true) : $response->getBody()->getContents();
    }

}

?>
