<?php

namespace oat\taoMediaManager\model;


interface FileManagement {


    /**
     * @param string $filePath the entire path to the file
     * @return string a link to the file in order to retrieve it later
     */
    public function storeFile($filePath);

    /**
     *
     * @param string $link the link provided by storeFile
     * @return the file that match the link
     */
    public function retrieveFile($link);

} 