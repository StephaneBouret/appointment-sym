<?php
declare(strict_types=1);

// The two DBAL types accept mixed natively but validate concrete objects at runtime.
// ReflectionDescriptor would expose mixed to PHPStan for writes and miss nullability mismatches.
final class DiagnosticObjectTypeDescriptor implements PHPStan\Type\Doctrine\Descriptors\DoctrineTypeDescriptor
{
    public function __construct(private string $dbalType, private string $valueType) {}
    public function getType(): string { return $this->dbalType; }
    public function getWritableToPropertyType(): PHPStan\Type\Type
    {
        return new PHPStan\Type\ObjectType($this->valueType);
    }
    public function getWritableToDatabaseType(): PHPStan\Type\Type
    {
        return new PHPStan\Type\ObjectType($this->valueType);
    }
    public function getDatabaseInternalType(): PHPStan\Type\Type
    {
        return new PHPStan\Type\StringType();
    }
}
