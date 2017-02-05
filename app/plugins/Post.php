<?php
use \Yaf\Response_Abstract;
use \Yaf\Request_Abstract;
/**
 *
 * @author: ronnie
 * @since: 2017/02/06 00:33
 * @copyright: 2017@firegit.com
 * @filesource: Layout.php
 */
class PostPlugin extends \Yaf\Plugin_Abstract
{
    public function dispatchLoopShutDown(Request_Abstract $request, Response_Abstract $response)
    {
    }

    public function dispatchLoopStartup(Request_Abstract $request, Response_Abstract $response)
    {
    }


    public function postDispatch(Request_Abstract $request, Response_Abstract $response)
    {
        $datas = \Yaf\Registry::get('postDatas');
        if (!$datas) {
            $datas = [];
        }
        $output = [
            'data' => $datas,
            'status' => 'ok',
        ];
        header('Content-type: application/json; charset=utf-8');
        echo json_encode($output, JSON_UNESCAPED_UNICODE);
    }
}
