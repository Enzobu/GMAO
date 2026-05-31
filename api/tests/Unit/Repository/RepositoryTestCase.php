<?php

namespace App\Tests\Unit\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;
use ArrayObject;

abstract class RepositoryTestCase extends TestCase
{
    /** @template T of object @param class-string<T> $repositoryClass @param class-string $entityClass @return T */
    protected function instantiateRepository(string $repositoryClass, string $entityClass): object
    {
        return $this->instantiateRepositoryWithEntityManager($repositoryClass, $entityClass)[0];
    }

    /** @template T of object @param class-string<T> $repositoryClass @param class-string $entityClass @return array{0: T, 1: EntityManagerInterface} */
    protected function instantiateRepositoryWithEntityManager(string $repositoryClass, string $entityClass): array
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = $entityClass;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->with($entityClass)->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->with($entityClass)->willReturn($entityManager);

        return [new $repositoryClass($registry), $entityManager];
    }

    /** @template T of object @param class-string<T> $repositoryClass @param class-string $entityClass @return T */
    protected function instantiateRepositoryWithQueryBuilder(string $repositoryClass, string $entityClass, QueryBuilder $queryBuilder): object
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->name = $entityClass;

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->with($entityClass)->willReturn($metadata);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->with($entityClass)->willReturn($entityManager);

        $repository = $this->getMockBuilder($repositoryClass)
            ->setConstructorArgs([$registry])
            ->onlyMethods(['createQueryBuilder'])
            ->getMock();
        $repository->method('createQueryBuilder')->willReturn($queryBuilder);

        return $repository;
    }

    /** @return array{0: QueryBuilder, 1: Query, 2: ArrayObject<int, array{0: string, 1: array<int, mixed>}>} */
    protected function createRecordingQueryBuilder(array $result = [], mixed $oneOrNullResult = null, mixed $executeResult = null): array
    {
        $calls = new ArrayObject();
        $query = $this->getMockBuilder(Query::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getResult', 'getOneOrNullResult', 'execute'])
            ->getMock();
        $query->method('getResult')->willReturnCallback(function () use ($calls, $result) {
            $calls[] = ['getResult', []];

            return $result;
        });
        $query->method('getOneOrNullResult')->willReturnCallback(function () use ($calls, $oneOrNullResult) {
            $calls[] = ['getOneOrNullResult', []];

            return $oneOrNullResult;
        });
        $query->method('execute')->willReturnCallback(function () use ($calls, $executeResult) {
            $calls[] = ['execute', []];

            return $executeResult;
        });

        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'addOrderBy',
                'addSelect',
                'andWhere',
                'getQuery',
                'innerJoin',
                'join',
                'leftJoin',
                'orderBy',
                'set',
                'setMaxResults',
                'setParameter',
                'update',
                'where',
            ])
            ->getMock();

        foreach (['addOrderBy', 'addSelect', 'andWhere', 'innerJoin', 'join', 'leftJoin', 'orderBy', 'set', 'setMaxResults', 'setParameter', 'update', 'where'] as $method) {
            $queryBuilder->method($method)->willReturnCallback(function (...$args) use ($calls, $method, $queryBuilder) {
                $calls[] = [$method, $args];

                return $queryBuilder;
            });
        }
        $queryBuilder->method('getQuery')->willReturnCallback(function () use ($calls, $query) {
            $calls[] = ['getQuery', []];

            return $query;
        });

        return [$queryBuilder, $query, $calls];
    }

    /** @param ArrayObject<int, array{0: string, 1: array<int, mixed>}> $calls */
    protected function assertRecordedCall(ArrayObject $calls, string $method, array $arguments): void
    {
        foreach ($calls as [$recordedMethod, $recordedArguments]) {
            if ($recordedMethod === $method && array_slice($recordedArguments, 0, count($arguments)) === $arguments) {
                self::assertTrue(true);

                return;
            }
        }

        self::fail(sprintf('Failed asserting that %s was called with leading arguments %s.', $method, json_encode($arguments)));
    }
}
