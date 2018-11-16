<?php
/**
 * e107 website system
 *
 * Copyright (C) 2008-2018 e107 Inc (e107.org)
 * Released under the terms and conditions of the
 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
 *
 */

namespace e107\Configuration\Storage;

function file_get_contents(...$args)
{
	foreach (debug_backtrace(false) as $line) {
		if ($line['function'] == "testReadError")
			return FALSE;
	}
	return \file_get_contents(...$args);
}

function file_put_contents(...$args)
{
	foreach (debug_backtrace(false) as $line) {
		if ($line['function'] == "testWriteError")
			return FALSE;
	}
	return \file_put_contents(...$args);
}

class FileConfigurationStorageTest extends \Codeception\Test\Unit
{
	private function makeFileConfigurationStorage(...$arguments)
	{
		$instance = NULL;
		try {
			$instance = $this->make(AbstractFileConfigurationStorage::class, [
				'fromFile' => function($input) {
					return "ADAPTED $input";
				},
				'toFile' => function($input) {
					return "$input ADAPTED";
				}
			]);
		} catch (\Exception $e) {
			$this->fail("Could not make mock configuration storage backend: ".$e->getMessage());
		}
		if (!empty($arguments)) {
			$instance->__construct(...$arguments);
		}
		return $instance;
	}

	public function testRead()
	{
		$file = tmpfile();
		$data = "test data";
		fwrite($file, $data);
		$path = stream_get_meta_data($file)['uri'];
		$storage = $this->makeFileConfigurationStorage($path);

		$result = $storage->read();

		$this->assertEquals("ADAPTED $data", $result);
	}

	public function testReadError()
	{
		$file = tmpfile();
		$path = stream_get_meta_data($file)['uri'];
		$storage = $this->makeFileConfigurationStorage($path);

		$this->expectException(\RuntimeException::class);

		$storage->read();
	}

	public function testWrite()
	{
		$file = tmpfile();
		$data = "test data";
		$path = stream_get_meta_data($file)['uri'];
		$storage = $this->makeFileConfigurationStorage($path);

		$storage->write($data);
		$result = file_get_contents($path);

		$this->assertEquals("$data ADAPTED", $result);
	}

	public function testWriteError()
	{
		$file = tmpfile();
		$data = "test data";
		$path = stream_get_meta_data($file)['uri'];
		$storage = $this->makeFileConfigurationStorage($path);

		$this->expectException(\RuntimeException::class);

		$storage->write($data);
	}
}
