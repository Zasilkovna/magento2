<?php

namespace Packetery\Checkout\Helper\api\Model;


interface IBranchStorage {

    /**
     * @return array
     */
    public function getBranchList();

    /**
     * @param $id
     * @return array
     */
    public function find($id);

    /**
     * @param $branchList
     * @return void
     */
    public function setBranchList($branchList);

    /**
     * @return boolean
     */
    public function isStorageValid();
    
}