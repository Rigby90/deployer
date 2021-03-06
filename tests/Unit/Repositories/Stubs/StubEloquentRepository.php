<?php

namespace REBELinBLUE\Deployer\Tests\Unit\Repositories\Stubs;

use REBELinBLUE\Deployer\Repositories\EloquentRepository;

class StubEloquentRepository extends EloquentRepository
{
    public function __construct(StubModel $model)
    {
        $this->model = $model;
    }
}
