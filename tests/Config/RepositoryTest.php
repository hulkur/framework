<?php

namespace Illuminate\Tests\Config;

use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase;

class RepositoryTest extends TestCase
{
    /**
     * @var \Illuminate\Config\Repository
     */
    protected $repository;

    /**
     * @var array
     */
    protected $config;

    protected function setUp(): void
    {
        $this->repository = new Repository($this->config = [
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'bat',
            'null' => null,
            'associate' => [
                'x' => 'xxx',
                'y' => 'yyy',
            ],
            'array' => [
                'aaa',
                'zzz',
            ],
            'x' => [
                'z' => 'zoo',
                'w' => 'resolved'
            ],
            'resolvable' => 'x.w',
            'not-resolvable' => 'x.a',
        ]);

        parent::setUp();
    }

    public function testConstruct()
    {
        $this->assertInstanceOf(Repository::class, $this->repository);
    }

    public function testHasIsTrue()
    {
        $this->assertTrue($this->repository->has('foo'));
        $this->assertTrue($this->repository->has('{resolvable}'));
    }

    public function testHasIsFalse()
    {
        $this->assertFalse($this->repository->has('not-exist'));
        $this->assertFalse($this->repository->has('{not-resolvable}'));
        $this->assertFalse($this->repository->has('{not-exist-resolvable}'));
    }

    public function testGet()
    {
        $this->assertSame('bar', $this->repository->get('foo'));
        $this->assertSame('resolved', $this->repository->get('{resolvable}'));
    }

    public function testGetWithArrayOfKeys()
    {
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'none' => null,
            'x.w' => 'resolved',
        ], $this->repository->get([
            'foo',
            'bar',
            'none',
            '{resolvable}',
        ]));

        $this->assertSame([
            'x.y' => 'default',
            'x.z' => 'zoo',
            'bar' => 'baz',
            'baz' => 'bat',
            'x.w' => 'resolved',
            'x.a' => 'default',
            '{not-exist-resolvable}' => 'default',
        ], $this->repository->get([
            'x.y' => 'default',
            'x.z' => 'default',
            'bar' => 'default',
            'baz',
            '{resolvable}' => 'default',
            '{not-resolvable}' => 'default',
            '{not-exist-resolvable}' => 'default',
        ]));
    }

    public function testGetMany()
    {
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'none' => null,
            'x.w' => 'resolved',
            'x.a' => null,
            '{not-exist-resolvable}' => null,
        ], $this->repository->getMany([
            'foo',
            'bar',
            'none',
            '{resolvable}',
            '{not-resolvable}',
            '{not-exist-resolvable}',
        ]));

        $this->assertSame([
            'x.y' => 'default',
            'x.z' => 'zoo',
            'bar' => 'baz',
            'baz' => 'bat',
            'x.w' => 'resolved',
            'x.a' => 'default',
            '{not-exist-resolvable}' => 'default',
        ], $this->repository->getMany([
            'x.y' => 'default',
            'x.z' => 'default',
            'bar' => 'default',
            'baz',
            '{resolvable}' => 'default',
            '{not-resolvable}' => 'default',
            '{not-exist-resolvable}' => 'default',
        ]));
    }

    public function testGetWithDefault()
    {
        $this->assertSame('default', $this->repository->get('not-exist', 'default'));
        $this->assertSame('default', $this->repository->get('{not-resolvable}', 'default'));
        $this->assertSame('default', $this->repository->get('{not-exist-resolvable}', 'default'));
    }

    public function testSet()
    {
        $this->repository->set('key', 'value');
        $this->assertSame('value', $this->repository->get('key'));
    }

    public function testSetArray()
    {
        $this->repository->set([
            'key1' => 'value1',
            'key2' => 'value2',
        ]);
        $this->assertSame('value1', $this->repository->get('key1'));
        $this->assertSame('value2', $this->repository->get('key2'));
    }

    public function testPrepend()
    {
        $this->repository->prepend('array', 'xxx');
        $this->assertSame('xxx', $this->repository->get('array.0'));
    }

    public function testPush()
    {
        $this->repository->push('array', 'xxx');
        $this->assertSame('xxx', $this->repository->get('array.2'));
    }

    public function testAll()
    {
        $this->assertSame($this->config, $this->repository->all());
    }

    public function testOffsetExists()
    {
        $this->assertTrue(isset($this->repository['foo']));
        $this->assertFalse(isset($this->repository['not-exist']));
        $this->assertTrue(isset($this->repository['{resolvable}']));
        $this->assertFalse(isset($this->repository['{not-resolvable}']));
        $this->assertFalse(isset($this->repository['{not-exist-resolvable}']));
    }

    public function testOffsetGet()
    {
        $this->assertNull($this->repository['not-exist']);
        $this->assertNull($this->repository['{not-resolvable}']);
        $this->assertNull($this->repository['{not-exist-resolvable}']);
        $this->assertSame('bar', $this->repository['foo']);
        $this->assertSame('resolved', $this->repository['{resolvable}']);
        $this->assertSame([
            'x' => 'xxx',
            'y' => 'yyy',
        ], $this->repository['associate']);
    }

    public function testOffsetSet()
    {
        $this->assertNull($this->repository['key']);

        $this->repository['key'] = 'value';

        $this->assertSame('value', $this->repository['key']);
    }

    public function testOffsetUnset()
    {
        $this->assertArrayHasKey('associate', $this->repository->all());
        $this->assertSame($this->config['associate'], $this->repository->get('associate'));

        unset($this->repository['associate']);

        $this->assertArrayHasKey('associate', $this->repository->all());
        $this->assertNull($this->repository->get('associate'));
    }
}
