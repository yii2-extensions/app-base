<?php

declare(strict_types=1);

namespace app\tests\unit\models;

use app\models\{User, UserSearch};
use app\tests\support\fixtures\UserFixture;
use yii\data\ActiveDataProvider;

/**
 * Unit tests for {@see UserSearch} model.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class UserSearchTest extends \Codeception\Test\Unit
{
    /**
     * @return array{user: array{class: string, dataFile: string}}
     */
    public function _fixtures(): array
    {
        return [
            'user' => [
                'class' => UserFixture::class,
                // @phpstan-ignore binaryOp.invalid
                'dataFile' => codecept_data_dir() . 'user.php',
            ],
        ];
    }

    public function testRulesReturnArray(): void
    {
        $searchModel = new UserSearch();

        $rules = $searchModel->rules();

        verify($rules)
            ->notEmpty();
    }

    public function testSearchReturnsDataProvider(): void
    {
        $searchModel = new UserSearch();

        $dataProvider = $searchModel->search([]);

        self::assertInstanceOf(
            ActiveDataProvider::class,
            $dataProvider,
            'Failed asserting that search method returns an instance of ActiveDataProvider.',
        );

        verify($dataProvider->getTotalCount())
            ->greaterThan(0);
    }

    public function testSearchWithInvalidData(): void
    {
        $searchModel = new UserSearch();

        $dataProvider = $searchModel->search(['UserSearch' => ['id' => 'invalid']]);

        self::assertInstanceOf(
            ActiveDataProvider::class,
            $dataProvider,
            'Failed asserting that search method returns an instance of ActiveDataProvider.',
        );

        verify($dataProvider->getTotalCount())
            ->equals(0);
    }

    public function testSearchWithUsernameFilter(): void
    {
        $searchModel = new UserSearch();

        $dataProvider = $searchModel->search(['UserSearch' => ['username' => 'okirlin']]);

        self::assertInstanceOf(
            ActiveDataProvider::class,
            $dataProvider,
            'Failed asserting that search method returns an instance of ActiveDataProvider.',
        );

        $models = $dataProvider->getModels();

        self::assertCount(
            1,
            $models,
            "Failed asserting that the username filter returns exactly one record for 'okirlin'.",
        );
        self::assertContainsOnlyInstancesOf(
            User::class,
            $models,
            'Failed asserting that all returned models are instances of User.',
        );

        $first = reset($models);

        self::assertInstanceOf(
            User::class,
            $first,
            'Failed asserting that the first returned model is an instance of User.',
        );
        self::assertSame(
            'okirlin',
            $first->username,
            "Failed asserting that the matched record is the 'okirlin' user (LIKE filter could match siblings).",
        );
    }
}
