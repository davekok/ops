<?php

declare(strict_types=1);

namespace GitOps;

use GitOps\Attribute\Factory;
use LogicException;
use ReflectionClass;
use ReflectionException;

/**
 * The request handler, will tie a request to a resource to handle it.
 */
class GitOpsRequestHandler
{
	/**
	 * @throws ReflectionException
	 */
	public function handle(GitOpsRequest $request): GitOpsResponse
	{
		$resource = new ReflectionClass($request->resource);
		$method = $resource->getMethod($request->method);
		$parameters = [];
		foreach ($method->getParameters() as $parameter) {
			if (!property_exists($request, $parameter->name)) {
				throw new LogicException("Internal logic exception, unknown argument $parameter->name");
			}
			$value = $request->{$parameter->name} ?? null;
			if ($value === null) {
				if (!$parameter->isDefaultValueAvailable()) {
					throw new LogicException("Internal logic exception, argument $parameter->name is required");
				}
				$parameters[] = $parameter->getDefaultValue();
				continue;
			}

			$type = $parameter->getType();
			if ($type->isBuiltin()) {
				$parameters[] = $value;
				continue;
			}

			$class = new ReflectionClass($type->getName());
			$attrs = $class->getAttributes(Factory::class);
			if (count($attrs) === 0) {
				$parameters[] = new $class($value);
				continue;
			}
			[$factory] = $attrs[0]->getArguments();
			$parameters[] = (new $factory)($value);
		}

		return $method->invokeArgs($resource->newInstance(), $parameters);
	}
}
