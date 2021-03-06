<?php

namespace REBELinBLUE\Deployer\Tests\Unit\Repositories;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Mockery as m;
use REBELinBLUE\Deployer\Jobs\SetupProject;
use REBELinBLUE\Deployer\Jobs\UpdateGitMirror;
use REBELinBLUE\Deployer\Project;
use REBELinBLUE\Deployer\Repositories\Contracts\ProjectRepositoryInterface;
use REBELinBLUE\Deployer\Repositories\EloquentProjectRepository;
use REBELinBLUE\Deployer\Repositories\EloquentRepository;
use REBELinBLUE\Deployer\Tests\TestCase;

/**
 * @coversDefaultClass \REBELinBLUE\Deployer\Repositories\EloquentProjectRepository
 */
class EloquentProjectRepositoryTest extends TestCase
{
    /**
     * @covers ::__construct
     */
    public function testExtendsEloquentRepository()
    {
        $model      = m::mock(Project::class);
        $repository = new EloquentProjectRepository($model);

        $this->assertInstanceOf(EloquentRepository::class, $repository);
    }

    /**
     * @covers ::__construct
     */
    public function testImplementsProjectRepositoryInterface()
    {
        $model      = m::mock(Project::class);
        $repository = new EloquentProjectRepository($model);

        $this->assertInstanceOf(ProjectRepositoryInterface::class, $repository);
    }

    /**
     * @covers ::getAll
     */
    public function testGetAll()
    {
        $expected = m::mock(Project::class);
        $expected->shouldReceive('get')->andReturnSelf();

        $model  = m::mock(Project::class);
        $model->shouldReceive('orderBy')->once()->with('name')->andReturn($expected);

        $repository = new EloquentProjectRepository($model);
        $actual     = $repository->getAll();

        $this->assertSame($expected, $actual);
    }

    /**
     * @covers ::getByHash
     */
    public function testGetByHash()
    {
        $hash = 'a-project-hash';

        $expected = m::mock(Project::class);
        $expected->shouldReceive('firstOrFail')->andReturnSelf();

        $model = m::mock(Project::class);
        $model->shouldReceive('where')->once()->with('hash', $hash)->andReturn($expected);

        $repository = new EloquentProjectRepository($model);
        $actual     = $repository->getByHash($hash);

        $this->assertSame($expected, $actual);
    }

    /**
     * @covers ::getByHash
     */
    public function testGetByHashShouldThrowModelNotFoundException()
    {
        $hash = 'a-Project-hash';

        $this->expectException(ModelNotFoundException::class);

        $expected = m::mock(Project::class);
        $expected->shouldReceive('firstOrFail')->andThrow(ModelNotFoundException::class);

        $model = m::mock(Project::class);
        $model->shouldReceive('where')->once()->with('hash', $hash)->andReturn($expected);

        $repository = new EloquentProjectRepository($model);
        $repository->getByHash($hash);
    }

    /**
     * @covers ::refreshBranches
     */
    public function testRefreshBranches()
    {
        $id = 1;

        $this->expectsJobs(UpdateGitMirror::class);

        $model = m::mock(Project::class);
        $model->shouldReceive('findOrFail')->once()->with($id)->andReturnSelf();

        $repository = new EloquentProjectRepository($model);
        $repository->refreshBranches($id);
    }

    /**
     * @covers ::refreshBranches
     */
    public function testRefreshBranchesThrowsModelNotFoundException()
    {
        $id = 1;
        $this->doesntExpectJobs(UpdateGitMirror::class);

        $this->expectException(ModelNotFoundException::class);

        $model = m::mock(Project::class);
        $model->shouldReceive('findOrFail')->once()->with($id)->andThrow(ModelNotFoundException::class);

        $repository = new EloquentProjectRepository($model);
        $repository->refreshBranches($id);
    }

    /**
     * @covers ::updateById
     */
    public function testUpdateByIdRemovesBlankPrivateKey()
    {
        $id     = 1;
        $fields = ['foo' => 'bar', 'private_key' => ''];
        $update = ['foo' => 'bar']; // This is what is expected to be passed to update

        $expected = m::mock(Project::class);
        $expected->shouldReceive('update')->once()->with($update);

        $model = m::mock(Project::class);
        $model->shouldReceive('findOrFail')->once()->with($id)->andReturn($expected);

        $repository = new EloquentProjectRepository($model);
        $actual     = $repository->updateById($fields, $id);

        $this->assertSame($expected, $actual);
    }

    /**
     * @covers ::updateById
     */
    public function testUpdateByIdClearPublicKeyWhenPrivateKeyIsProvided()
    {
        $id     = 1;
        $fields = ['foo' => 'bar', 'private_key' => 'a-new-key'];

        $expected = m::mock(Project::class);
        $expected->shouldReceive('update')->once()->with($fields);
        $expected->shouldReceive('setAttribute')->once()->with('public_key', '');

        $model = m::mock(Project::class);
        $model->shouldReceive('findOrFail')->once()->with($id)->andReturn($expected);

        $repository = new EloquentProjectRepository($model);
        $actual     = $repository->updateById($fields, $id);

        $this->assertSame($expected, $actual);
    }

    /**
     * @covers ::create
     */
    public function testCreate()
    {
        $this->doesntExpectJobs(SetupProject::class);

        $expected = 'a-model';
        $fields   = ['foo' => 'bar'];

        $model = m::mock(Project::class);
        $model->shouldReceive('create')->once()->with($fields)->andReturn($expected);

        $repository = new EloquentProjectRepository($model);
        $actual     = $repository->create($fields);

        $this->assertSame($expected, $actual);
    }

    /**
     * @covers ::create
     */
    public function testCreateWithTemplate()
    {
        $this->markTestSkipped('not working - same issue as ServerRepository');

        $this->expectsJobs(SetupProject::class);

        $expected = m::mock(Project::class);

//        $expected = 'string';

        $fields = ['foo' => 'bar', 'template_id' => 1];
        $create = ['foo' => 'bar']; // This is what is expected to be passed to create

        $model = m::mock(Project::class);
        $model->shouldReceive('create')->once()->with($create)->andReturn($expected);

        $repository = new EloquentProjectRepository($model);
        $actual     = $repository->create($fields);

        $this->assertSame($expected, $actual);
    }
}
