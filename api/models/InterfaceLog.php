<?php
declare(strict_types=1);

namespace model;

use repository\BaseModel;

class InterfaceLog extends BaseModel
{
    public function __contrast()
    {
        $this->setSplitTableName(__CLASS__);
    }
}