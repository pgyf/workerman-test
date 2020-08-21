<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace core\common;

/**
 * Description of File
 *
 * @author phpyii
 */
class File {
    
    /**
     * 获取文件后缀
     * @param type $mimeContentType
     * @return string
     */
    public static function getFileExt($mimeContentType) {
        $ext = '.txt';
        switch ($mimeContentType) {
            case 'application/zip':
                $ext = '.zip';
                break;
        }
        return $ext;
    }
}
