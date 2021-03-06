<?php

namespace REBELinBLUE\Deployer\Tests\Unit\View\Presenters;

use Illuminate\Support\Facades\Lang;
use Mockery as m;
use REBELinBLUE\Deployer\Command;
use REBELinBLUE\Deployer\Deployment;
use REBELinBLUE\Deployer\Tests\TestCase;
use REBELinBLUE\Deployer\View\Presenters\DeploymentPresenter;
use RuntimeException;
use stdClass;

/**
 * @coversDefaultClass \REBELinBLUE\Deployer\View\Presenters\DeploymentPresenter
 */
class DeploymentPresenterTest extends TestCase
{
    /**
     * @covers ::presentReadableRuntime
     */
    public function testRuntimeInterfaceIsUsed()
    {
        $this->expectException(RuntimeException::class);

        // Class which doesn't implement the RuntimeInterface
        $presenter = new DeploymentPresenter(new stdClass());
        $presenter->presentReadableRuntime();
    }

    /**
     * @dataProvider getCCTrayStatus
     * @covers ::presentCcTrayStatus
     */
    public function testPresentCcTrayStatusIsCorrect($status, $expected)
    {
        $deployment = $this->mockDeploymentWithStatus($status);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentCcTrayStatus();

        $this->assertSame($expected, $actual);
    }

    public function getCCTrayStatus()
    {
        return [
            [Deployment::COMPLETED, 'Success'],
            [Deployment::COMPLETED_WITH_ERRORS, 'Success'],
            [Deployment::FAILED, 'Failure'],
            [Deployment::ABORTED, 'Failure'],
            [Deployment::PENDING, 'Unknown'],
            [Deployment::DEPLOYING, 'Unknown'],
            [Deployment::ABORTING, 'Unknown'],
            [Deployment::LOADING, 'Unknown'],
            ['invalid-value', 'Unknown'],
        ];
    }

    /**
     * @dataProvider getReadableStatus
     * @covers ::presentReadableStatus
     */
    public function testPresentReadableStatusIsCorrect($status, $expected)
    {
        $deployment = $this->mockDeploymentWithStatus($status);

        Lang::shouldReceive('get')->once()->with($expected)->andReturn($expected);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentReadableStatus();

        $this->assertSame($expected, $actual);
    }

    public function getReadableStatus()
    {
        return [
            [Deployment::COMPLETED, 'deployments.completed'],
            [Deployment::COMPLETED_WITH_ERRORS, 'deployments.completed_with_errors'],
            [Deployment::FAILED, 'deployments.failed'],
            [Deployment::ABORTED, 'deployments.aborted'],
            [Deployment::PENDING, 'deployments.pending'],
            [Deployment::DEPLOYING, 'deployments.deploying'],
            [Deployment::ABORTING, 'deployments.aborting'],
            [Deployment::LOADING, 'deployments.pending'],
            ['invalid-value', 'deployments.pending'],
        ];
    }

    /**
     * @dataProvider getIcons
     * @covers ::presentIcon
     */
    public function testPresentIconIsCorrect($status, $expected)
    {
        $deployment = $this->mockDeploymentWithStatus($status);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentIcon();

        $this->assertSame($expected, $actual);
    }

    public function getIcons()
    {
        return [
            [Deployment::COMPLETED, 'check'],
            [Deployment::COMPLETED_WITH_ERRORS, 'warning'],
            [Deployment::FAILED, 'warning'],
            [Deployment::ABORTED, 'warning'],
            [Deployment::PENDING, 'clock-o'],
            [Deployment::DEPLOYING, 'spinner fa-pulse'],
            [Deployment::ABORTING, 'warning'],
            [Deployment::LOADING, 'clock-o'],
            ['invalid-value', 'clock-o'],
        ];
    }

    /**
     * @dataProvider getCssClasses
     * @covers ::presentCssClass
     */
    public function testPresentCssClassIsCorrect($status, $expected)
    {
        $deployment = $this->mockDeploymentWithStatus($status);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentCssClass();

        $this->assertSame($expected, $actual);
    }

    public function getCssClasses()
    {
        return [
            [Deployment::COMPLETED, 'success'],
            [Deployment::COMPLETED_WITH_ERRORS, 'success'],
            [Deployment::FAILED, 'danger'],
            [Deployment::ABORTED, 'danger'],
            [Deployment::PENDING, 'info'],
            [Deployment::DEPLOYING, 'warning'],
            [Deployment::ABORTING, 'danger'],
            [Deployment::LOADING, 'info'],
            ['invalid-value', 'info'],
        ];
    }

    /**
     * @dataProvider getTimelineCssClasses
     * @covers ::presentTimelineCssClass
     */
    public function testPresentTimelineCssClass($status, $expected)
    {
        $deployment = $this->mockDeploymentWithStatus($status);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentTimelineCssClass();

        $this->assertSame($expected, $actual);
    }

    public function getTimelineCssClasses()
    {
        return [
            [Deployment::COMPLETED, 'green'],
            [Deployment::COMPLETED_WITH_ERRORS, 'green'],
            [Deployment::FAILED, 'red'],
            [Deployment::ABORTED, 'red'],
            [Deployment::PENDING, 'aqua'],
            [Deployment::DEPLOYING, 'yellow'],
            [Deployment::ABORTING, 'red'],
            [Deployment::LOADING, 'aqua'],
            ['invalid-value', 'aqua'],
        ];
    }

    /**
     * @covers ::presentCommitterName
     */
    public function testPresentCommitterNameReturnsName()
    {
        $expected = 'a-real-name';

        $deployment = m::mock(Deployment::class);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('committer')->andReturn($expected);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentCommitterName();

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider getCommiterName
     * @covers ::presentCommitterName
     */
    public function testPresentCommitterNameReturnsTranslation($committer, $status, $expected)
    {
        $deployment = m::mock(Deployment::class);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('committer')->andReturn($committer);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('status')->andReturn($status);

        Lang::shouldReceive('get')->once()->with($expected)->andReturn($expected);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentCommitterName();

        $this->assertSame($expected, $actual);
    }

    public function getCommiterName()
    {
        return [
            [Deployment::LOADING, Deployment::LOADING, 'deployments.loading'],
            [Deployment::LOADING, Deployment::FAILED, 'deployments.unknown'],
        ];
    }

    /**
     * @covers ::presentShortCommitHash
     */
    public function testPresentShortCommitHashReturnsHash()
    {
        $expected = 'abcdedf';

        $deployment = m::mock(Deployment::class);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('short_commit')->andReturn($expected);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentShortCommitHash();

        $this->assertSame($expected, $actual);
    }

    /**
     * @dataProvider getShortHash
     * @covers ::presentShortCommitHash
     */
    public function testPresentShortCommitHashReturnsTranslation($commit, $status, $expected)
    {
        $deployment = m::mock(Deployment::class);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('short_commit')->andReturn($commit);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('status')->andReturn($status);

        Lang::shouldReceive('get')->once()->with($expected)->andReturn($expected);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentShortCommitHash();

        $this->assertSame($expected, $actual);
    }

    public function getShortHash()
    {
        return [
            [Deployment::LOADING, Deployment::LOADING, 'deployments.loading'],
            [Deployment::LOADING, Deployment::FAILED, 'deployments.unknown'],
        ];
    }

    /**
     * @dataProvider getCommandsUsed
     * @covers ::presentOptionalCommandsUsed
     */
    public function testPresentOptionalCommandsUsed(array $commands, $expected)
    {
        $collection = [];
        foreach ($commands as $id => $optional) {
            $collection[] = $this->mockCommand($id + 1, $optional);
        }
        $commands = collect($collection);

        $deployment = m::mock(Deployment::class);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('commands')->andReturn($commands);

        $presenter = new DeploymentPresenter($deployment);
        $actual    = $presenter->presentOptionalCommandsUsed();

        $this->assertSame($expected, $actual);
    }

    public function getCommandsUsed()
    {
        return [
            [[true], '1'],
            [[false], ''],
            [[true,true], '1,2'],
            [[true,false], '1'],
            [[false,true], '2'],
            [[false,false], ''],
            [[true,true,true], '1,2,3'],
            [[true,false,true], '1,3'],
        ];
    }

    private function mockDeploymentWithStatus($status)
    {
        $deployment = m::mock(Deployment::class);
        $deployment->shouldReceive('getAttribute')->atLeast()->times(1)->with('status')->andReturn($status);

        return $deployment;
    }

    private function mockCommand($id, $optional = false)
    {
        $command = m::mock(Command::class);
        $command->shouldReceive('getAttribute')->atLeast()->times(1)->with('optional')->andReturn($optional);

        if ($optional) {
            $command->shouldReceive('offsetExists')->atLeast()->times(1)->with('id')->andReturn(true);
            $command->shouldReceive('offsetGet')->atLeast()->times(1)->with('id')->andReturn($id);
        }

        return $command;
    }
}
