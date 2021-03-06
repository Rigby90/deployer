<?php

namespace REBELinBLUE\Deployer\Tests\Unit\Services\Webhooks;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Mockery as m;
use REBELinBLUE\Deployer\Services\Webhooks\Beanstalkapp;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * @coversDefaultClass \REBELinBLUE\Deployer\Services\Webhooks\Beanstalkapp
 */
class BeanstalkappTest extends WebhookTestCase
{
    /**
     * @covers ::isRequestOrigin
     * @covers ::__construct
     */
    public function testIsRequestOriginValid()
    {
        $request = $this->mockRequestIsFromBeanstalk(true);

        $beanstalkapp = new Beanstalkapp($request);
        $this->assertTrue($beanstalkapp->isRequestOrigin());
    }

    /**
     * @covers ::isRequestOrigin
     */
    public function testIsRequestOriginInvalid()
    {
        $request = $this->mockRequestIsFromBeanstalk(false);

        $beanstalkapp = new Beanstalkapp($request);
        $this->assertFalse($beanstalkapp->isRequestOrigin());
    }

    /**
     * @dataProvider getBranch
     * @covers ::handlePush
     */
    public function testHandlePushEventValid($branch)
    {
        $reason = 'Commit Log';
        $url    = 'http://www.example.com/';
        $commit = 'ee5a7ef0b320eda038d0d376a6ce50c44475efae';
        $name   = 'John Smith';
        $email  = 'john.smith@example.com';

        $request = $this->mockRequestWithBeanstalkPayload([
            'branch'  => $branch,
            'after'   => $commit,
            'commits' => [
                [
                    'committed_at'  => Carbon::now()->format('Y-m-d H:i:s'),
                    'changeset_url' => $url,
                    'message'       => $reason,
                    'author'        => [
                        'name'  => $name,
                        'email' => $email,
                    ],
                ],
            ],
        ]);

        $beanstalkapp = new Beanstalkapp($request);
        $actual       = $beanstalkapp->handlePush();

        $this->assertWebhookDataIsValid($actual, $branch, 'Beanstalkapp', $reason, $url, $commit, $name, $email);
    }

    /**
     * @dataProvider getUnsupportedEvents
     * @covers ::handlePush
     */
    public function testHandleUnsupportedEvent($event)
    {
        $request = $this->mockEventRequestFromBeanstalk($event);

        $beanstalkapp = new Beanstalkapp($request);
        $this->assertFalse($beanstalkapp->handlePush());
    }

    public function getUnsupportedEvents()
    {
        return array_chunk([
            'commit', 'comment', 'deploy', 'create_branch', 'delete_branch', 'create_tag', 'delete_tag',
            'request_code_review', 'cancel_code_review', 'reopen_code_review', 'approve_code_review',
        ], 1);
    }

    private function mockRequestIsFromBeanstalk($isValid)
    {
        $userAgent = $isValid ? 'beanstalkapp.com' : 'something-else';

        $header = m::mock(HeaderBag::class);
        $header->shouldReceive('get')->once()->with('User-Agent')->andReturn($userAgent);

        $request          = m::mock(Request::class);
        $request->headers = $header;

        return $request;
    }

    private function mockEventRequestFromBeanstalk($event)
    {
        $payload = m::mock(ParameterBag::class);
        $payload->shouldReceive('has')->once()->with('trigger')->andReturn(true);
        $payload->shouldReceive('get')->once()->with('trigger')->andReturn($event);

        $request = m::mock(Request::class);
        $request->shouldReceive('json')->once()->andReturn($payload);

        return $request;
    }

    private function mockRequestWithBeanstalkPayload(array $data)
    {
        $payload = m::mock(ParameterBag::class);
        $payload->shouldReceive('has')->once()->with('trigger')->andReturn(true);
        $payload->shouldReceive('get')->once()->with('trigger')->andReturn('push');
        $payload->shouldReceive('get')->once()->with('payload')->andReturn($data);

        $request = m::mock(Request::class);
        $request->shouldReceive('json')->once()->andReturn($payload);

        return $request;
    }
}
