<?php
/**
 * e107 website system
 *
 * Copyright (C) 2008-2018 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 */

namespace e107\Configuration;

use Codeception\Stub\Expected;
use e107\Configuration\Storage\ConfigurationStorageInterface;

class ConfigurationTest extends \Codeception\Test\Unit
{
	private static function makeConfiguration()
	{
		return new InMemoryConfiguration();
	}

	public function testClearRootLevel()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => 'bar'
		];
		$configuration->set('', $data);

		$output = $configuration->clear('foo');

		$this->assertNull($configuration->get('foo'));
		$this->assertEmpty($configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testClearNestedLevel()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => [
				'bar' => 'baz'
			]
		];
		$configuration->set('', $data);

		$output = $configuration->clear('foo/bar');

		$this->assertEquals(['foo' => []], $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testClearInvalidKey()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => 'just a string'
		];
		$configuration->set('', $data);

		$this->expectException(Exceptions\ConfigurationKeyException::class);

		$configuration->clear('foo/bar');
	}

	public function testIsSaved()
	{
		$configuration = self::makeConfiguration();
		$storage = NULL;
		try {
			$storage = self::makeEmpty(ConfigurationStorageInterface::class, [
				'write' => Expected::once()
			]);
		} catch (\Exception $e) {
			$this->fail("Could not make mock configuration storage backend: ".$e->getMessage());
		}
		$configuration->setStorage($storage);

		$this->assertFalse($configuration->isSaved());

		$configuration->save();

		$this->assertTrue($configuration->isSaved());
	}

	public function testSavePassesCorrectDataToStorage()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => [
				'bar' => 'baz',
				'bof' => [
					'one',
					'two',
					'three'
				]
			]
		];
		$storage = NULL;
		try {
			$storage = self::makeEmpty(ConfigurationStorageInterface::class, [
				'write' => Expected::once(function($input) use ($data) {
					$this->assertEquals($data, $input);
				})
			]);
		} catch (\Exception $e) {
			$this->fail("Could not make mock configuration storage backend: ".$e->getMessage());
		}
		$configuration->setStorage($storage);

		$configuration->set('', $data);

		$configuration->save();
	}

	public function testSaveErrorWhileWriting()
	{
		$configuration = self::makeConfiguration();
		$storage = NULL;
		try {
			$storage = self::makeEmpty(ConfigurationStorageInterface::class, [
				'write' => Expected::once(function($input) {
					throw new \RuntimeException('This is used to pass the test.');
				})
			]);
		} catch (\Exception $e) {
			$this->fail("Could not make mock configuration storage backend: ".$e->getMessage());
		}
		$configuration->setStorage($storage);

		$this->expectException(\RuntimeException::class);

		$configuration->save();
	}

	public function testPopulateWithString()
	{
		$configuration = self::makeConfiguration();
		$data = 'Stringy McStringface';

		$output = $configuration->populate($data);

		$this->assertEquals($data, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testPopulateWithArray()
	{
		$configuration = self::makeConfiguration();
		$data = ['foo' => 'bar'];

		$output = $configuration->populate($data);

		$this->assertEquals($data, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testPopulateWithAnotherConfigurationCopiesConfiguration()
	{
		$configuration = self::makeConfiguration();
		$configuration2 = self::makeConfiguration();
		$data = ['foo' => 'bar'];
		$configuration->populate($data);

		$output = $configuration2->populate($configuration);
		$configuration->clear();

		$this->assertEquals($data, $configuration2->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testPopulateWithMalformedArray()
	{
		$configuration = self::makeConfiguration();
		$data = ['foo/nope' => 'bar'];

		$this->expectException(Exceptions\ConfigurationValueException::class);

		$configuration->populate($data);
	}

	public function testPopulateWithBadType()
	{
		$configuration = self::makeConfiguration();
		$data = $this;

		$this->expectException(Exceptions\ConfigurationValueException::class);

		$configuration->populate($data);
	}

	public function testSetRootLevel()
	{
		$configuration = self::makeConfiguration();
		$expected = [
			'foo' => 'bar',
			'racecar' => 'racecar'
		];

		$configuration->set('foo', 'bar');
		$output = $configuration->set('racecar', 'racecar');

		$this->assertEquals($expected, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testSetAutoNesting()
	{
		$configuration = self::makeConfiguration();
		$expected = [
			'a' => [
				'b' => [
					'c' => 'value'
				]
			]
		];

		$output = $configuration->set('a/b/c', 'value');

		$this->assertEquals($expected, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testSetReplacesValue()
	{
		$configuration = self::makeConfiguration();
		$expected = [
			'a' => [
				'b' => [
					'c' => 'value',
					'd' => 'other value'
				]
			]
		];

		$configuration->set('a/b/c', 'something');
		$configuration->set('a/b/d', 'other value');
		$output = $configuration->set('a/b/c', 'value');

		$this->assertEquals($expected, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testSetInvalidKey()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => 'just a string'
		];
		$configuration->set('', $data);

		$this->expectException(Exceptions\ConfigurationKeyException::class);

		$configuration->set('foo/bar', 'value');
	}

	public function testSetWithValueThatHasInvalidKey()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => [
				'bar/nope' => 'fail'
			]
		];

		$this->expectException(Exceptions\ConfigurationValueException::class);

		$configuration->set('', $data);
	}

	public function testSetWithValueThatHasUnserializableValue()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => [
				'bar' => $this
			]
		];

		$this->expectException(Exceptions\ConfigurationValueException::class);

		$configuration->set('', $data);
	}

	public function testGet()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'string_key' => 'string_value',
			'array_key' => [
				'nested_key' => 'nested_value'
			]
		];

		$configuration->set('', $data);

		$this->assertEquals('string_value', $configuration->get('string_key'));
		$this->assertEquals(['nested_key' => 'nested_value'], $configuration->get('array_key'));
		$this->assertEquals('nested_value', $configuration->get('array_key/nested_key'));
	}

	public function testGetInvalidKey()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => 'just a string'
		];
		$configuration->set('', $data);

		$this->expectException(Exceptions\ConfigurationKeyException::class);

		$configuration->get('foo/bar');
	}

	public function testAddMergesAssociativeArrays()
	{
		$configuration = self::makeConfiguration();
		$expected = [
			'a' => 'b',
			'c' => 'd',
			'e' => [
				'f' => ['g'],
				'h' => 'i'
			]
		];

		$configuration->set('a', 'b');
		$configuration->add('', ['c' => 'd']);
		$configuration->add('e/f', 'g');
		$output = $configuration->add('e', ['h' => 'i']);

		$this->assertEquals($expected, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testAddAppendsToIndexedArray()
	{
		$configuration = self::makeConfiguration();
		$expected = [
			'a', 'b', 'c',
			'd' => [
				'e', 'f', 'g'
			]
		];

		$configuration->add('', 'a');
		$configuration->add('', 'b');
		$configuration->add('', 'c');
		$configuration->add('d', 'e');
		$configuration->add('d', 'f');
		$output = $configuration->add('d', 'g');

		$this->assertEquals($expected, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testAddAppendsToString()
	{
		$configuration = self::makeConfiguration();
		$expected = [
			'a' => 'bcdefg'
		];

		$configuration->set('a', '');
		$configuration->add('a', 'bcd');
		$configuration->add('a', 'ef');
		$output = $configuration->add('a', 'g');

		$this->assertEquals($expected, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testAddAddsToScalar()
	{
		$configuration = self::makeConfiguration();
		$expected = [
			'year' => 2002
		];

		$configuration->set('year', 1995);
		$output = $configuration->add('year', 7);

		$this->assertEquals($expected, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testAddInvalidKey()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => 'just a string'
		];
		$configuration->set('', $data);

		$this->expectException(Exceptions\ConfigurationKeyException::class);

		$configuration->add('foo/bar', 'anything');
	}

	public function testAddWithValueThatHasInvalidKey()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => [
				'bar/nope' => 'fail'
			]
		];

		$this->expectException(Exceptions\ConfigurationValueException::class);

		$configuration->add('', $data);
	}

	public function testAddWithValueThatHasUnserializableValue()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => [
				'bar' => $this
			]
		];

		$this->expectException(Exceptions\ConfigurationValueException::class);

		$configuration->add('', $data);
	}

	public function testLoadImportsCorrectDataFromStorage()
	{
		$configuration = self::makeConfiguration();
		$data = [
			'foo' => [
				'bar' => 'baz',
				'bof' => [
					'one',
					'two',
					'three'
				]
			]
		];
		$storage = NULL;
		try {
			$storage = self::makeEmpty(ConfigurationStorageInterface::class, [
				'read' => Expected::once($data)
			]);
		} catch (\Exception $e) {
			$this->fail("Could not make mock configuration storage backend: ".$e->getMessage());
		}
		$configuration->setStorage($storage);

		$output = $configuration->load();

		$this->assertEquals($data, $configuration->get());
		$this->assertInstanceOf(ConfigurationInterface::class, $output);
	}

	public function testLoadErrorWhileReading()
	{
		$configuration = self::makeConfiguration();
		$storage = NULL;
		try {
			$storage = self::makeEmpty(ConfigurationStorageInterface::class, [
				'read' => Expected::once(function() {
					throw new \RuntimeException('This is used to pass the test.');
				})
			]);
		} catch (\Exception $e) {
			$this->fail("Could not make mock configuration storage backend: ".$e->getMessage());
		}
		$configuration->setStorage($storage);

		$this->expectException(\RuntimeException::class);

		$configuration->load();
	}
}
