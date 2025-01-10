<?php 

declare(strict_types = 1);

namespace F4\PhpStan\Reflection\DB;

use BadMethodCallException;

use F4\DB;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\MethodsClassReflectionExtension;

class DBClassReflectionExtension implements MethodsClassReflectionExtension
{

	public function hasMethod(ClassReflection $classReflection, string $methodName): bool
	{
		// TODO: read the docs and make a proper implementation
		try {
			if($methodName === 'limit' || $methodName === 'offset') {
				(new DB)->__call($methodName, [0]);
			}
			else {
				(new DB)->__call($methodName, []);
			}
		}
		catch(BadMethodCallException $exception) {
			return false;
		};
		return true;
	}

	public function getMethod(ClassReflection $classReflection, string $methodName): MethodReflection
	{
		return new DBMethodReflectionExtension($methodName, $classReflection);
	}

}