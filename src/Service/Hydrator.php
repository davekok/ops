<?php

declare(strict_types=1);

namespace GitOps\Service;

use AssertionError;
use GitOps\Attribute\ArrayType;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;

class Hydrator
{
    /**
     * @param class-string<Model> $model
     * @param array $data
	 * @return Model
     * @throws ReflectionException
	 * @template Model
     */
    public function hydrateObject(string $model, array $data): object
    {
        $class = new ReflectionClass($model);
        $parameters = $class->getConstructor()?->getParameters() ?? throw new AssertionError("Model is not instantiable");

        $values = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->name;

            if (!isset($data[$name])) {
                assert($parameter->isDefaultValueAvailable(), new AssertionError("Value for $name is required."));
                $values[] = $parameter->getDefaultValue();
				continue;
            }

            $type = $parameter->getType();
            assert($type instanceof ReflectionNamedType, new AssertionError("Value for $name has no type or complex type."));

            $value = $data[$name];

            if (!$type->isBuiltin()) {
                assert(is_array($value), new AssertionError("Expected an object for value $name"));
                $values[] = $this->hydrateObject($type->getName(), $value);
				continue;
            }

            switch ($type->getName()) {
                case "array":
                    assert(is_array($value), new AssertionError("Expected an array for value $name"));
                    $values[] = $this->hydrateArray($parameter, $value);
                    continue 2;

                case "bool":
                    assert(is_bool($value), new AssertionError("Expected a boolean for value $name"));
                    $values[] = $value;
                    continue 2;

                case "float":
                    assert(is_float($value) || is_int($value), new AssertionError("Expected a float for value $name"));
                    $values[] = (float)$value;
                    continue 2;

                case "int":
                    assert(is_int($value), new AssertionError("Expected an integer for value $name"));
                    $values[] = $value;
                    continue 2;

                case "string":
                    assert(is_string($value), new AssertionError("Expected a string for value $name"));
                    $values[] = $value;
                    continue 2;
            }
        }

        return $class->newInstanceArgs($values);
    }

    /**
     * @throws ReflectionException
     */
    private function hydrateArray(ReflectionParameter $parameter, array $data): array
    {
        $attrs = $parameter->getAttributes(ArrayType::class);
        assert(count($attrs) === 1, new AssertionError("Untyped array, please specify a type for $parameter->name"));
        $type = $attrs[0]->newInstance();
        assert($type instanceof ArrayType);

        $array = [];
        if (!$type->map) {
            assert(array_is_list($data));
        }
        foreach ($data as $key => $value) {
            if ($type->map) {
                assert(is_string($key), new AssertionError("Expected key to be a string: $key"));
            }
            if (!$type->isBuiltin) {
                $value = $this->hydrateObject($type->name, $value);
            }
            assert($type->is($value), new AssertionError("Value is not of expected type $type"));
            $array[$key] = $value;
        }

        return $array;
    }
}
