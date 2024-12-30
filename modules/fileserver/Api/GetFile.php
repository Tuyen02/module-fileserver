<?php

namespace NukeViet\Module\fileserver\Api;

use NukeViet\Api\Api;
use NukeViet\Api\ApiResult;
use NukeViet\Api\IApi;

if (!defined('NV_ADMIN') or !defined('NV_MAINFILE')) {
    die('Stop!!!');
}

class GetFile implements IApi
{
    private $result;

    /**
     * @return number
     */
    public static function getAdminLev()
    {
        return Api::ADMIN_LEV_MOD;
    }

    /**
     * @return string
     */
    public static function getCat()
    {
        return 'fileserver';
    }

    /**
     * {@inheritDoc}
     * @see \NukeViet\Api\IApi::setResultHander()
     */
    public function setResultHander(ApiResult $result)
    {
        $this->result = $result;
    }

    /**
     * {@inheritDoc}
     * @see \NukeViet\Api\IApi::execute()
     */
    public function execute()
    {
        global $nv_Request, $db;

        $file_id = $nv_Request->get_int('file_id', 'post', 0);

        if ($file_id > 0) {
            $data = $db->query("SELECT file_name, file_path FROM " . NV_PREFIXLANG . "_fileserver_files WHERE file_id = " . $file_id)->fetch();
            if (!empty($data)) {
                $this->result->setSuccess();
                $this->result->set('message', $data);
            } else {
                $this->result->setError()
                    ->setCode('1002')
                    ->setMessage('Error: data empty');
            }
        } else {
            $this->result->setError()
                ->setCode('1001')
                ->setMessage('Error: file_id is empty');
        }
        return $this->result->getResult();
    }
}

