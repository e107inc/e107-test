<?php
namespace Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class Acceptance extends E107Base
{
	protected $deployer_components = ['db', 'fs'];

	protected function writeLocalE107Config()
	{
		// Noop
		// Acceptance tests will install the app themselves
	}
}
