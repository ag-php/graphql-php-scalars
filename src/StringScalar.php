<?php

declare(strict_types=1);

namespace MLL\GraphQLScalars;

use GraphQL\Error\Error;
use GraphQL\Error\InvariantViolation;
use GraphQL\Language\AST\Node;
use GraphQL\Type\Definition\ScalarType;
use GraphQL\Utils\Utils;

abstract class StringScalar extends ScalarType
{
    /**
     * Instantiate an anonymous subclass that can be used in a schema.
     *
     * @param string $name The name that the scalar type will have in the schema.
     * @param string|null $description A description for the type.
     * @param callable $isValid A function that returns a boolean whether a given string is valid.
     *
     * @return StringScalar
     */
    public static function make(string $name, ?string $description, callable $isValid): self
    {
        $concreteStringScalar = new class() extends StringScalar {
            /**
             * @var callable
             */
            public $isValid;

            /**
             * Check if the given string is a valid email.
             */
            protected function isValid(string $stringValue): bool
            {
                return call_user_func($this->isValid, $stringValue);
            }
        };

        $concreteStringScalar->name = $name;
        $concreteStringScalar->description = $description;
        $concreteStringScalar->isValid = $isValid;

        return $concreteStringScalar;
    }

    /**
     * Check if the given string is valid.
     */
    abstract protected function isValid(string $stringValue): bool;

    /**
     * Serializes an internal value to include in a response.
     *
     *
     * @throws InvariantViolation
     */
    public function serialize($value): string
    {
        $stringValue = coerceToString($value, InvariantViolation::class);

        if (!$this->isValid($stringValue)) {
            throw new InvariantViolation(
                $this->invalidStringMessage($stringValue)
            );
        }

        return $stringValue;
    }

    /**
     * Construct an error message that occurs when an invalid string is passed.
     */
    public function invalidStringMessage(string $stringValue): string
    {
        $safeValue = Utils::printSafeJson($stringValue);

        return "The given string {$safeValue} is not a valid {$this->tryInferName()}.";
    }

    /**
     * Parses an externally provided value (query variable) to use as an input.
     *
     *
     * @throws Error
     */
    public function parseValue($value): string
    {
        $stringValue = coerceToString($value, Error::class);

        if (!$this->isValid($stringValue)) {
            throw new Error(
                $this->invalidStringMessage($stringValue)
            );
        }

        return $stringValue;
    }

    /**
     * Parses an externally provided literal value (hardcoded in GraphQL query) to use as an input.
     *
     * E.g.
     * {
     *   user(email: "user@example.com")
     * }
     *
     * @param Node $valueNode
     * @param mixed[]|null $variables
     *
     * @throws Error
     */
    public function parseLiteral($valueNode, ?array $variables = null): string
    {
        $stringValue = extractStringFromLiteral($valueNode);

        if (!$this->isValid($stringValue)) {
            throw new Error(
                $this->invalidStringMessage($stringValue),
                $valueNode
            );
        }

        return $stringValue;
    }
}
