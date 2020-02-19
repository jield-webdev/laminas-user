<?php

namespace ZfcUser\Db\Adapter;

use Laminas\Db\Adapter\Adapter;

interface MasterSlaveAdapterInterface
{
    /**
     * @return Adapter
     */
    public function getSlaveAdapter();
}
