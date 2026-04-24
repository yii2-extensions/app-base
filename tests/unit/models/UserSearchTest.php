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

        self::assertNotEmpty(
            $rules,
            'Must declare at least one validation rule.',
        );
    }

    public function testSearchReturnsDataProvider(): void
    {
        $searchModel = new UserSearch();

        $dataProvider = $searchModel->search([]);

        self::assertInstanceOf(
            ActiveDataProvider::class,
            $dataProvider,
            'Search method returns an instance of ActiveDataProvider.',
        );

        self::assertGreaterThan(
            0,
            $dataProvider->getTotalCount(),
            'Unfiltered search must return at least one fixture user.',
        );
    }

    public function testSearchWithInvalidData(): void
    {
        $searchModel = new UserSearch();

        $dataProvider = $searchModel->search(['UserSearch' => ['id' => 'invalid']]);

        self::assertInstanceOf(
            ActiveDataProvider::class,
            $dataProvider,
            'Search method returns an instance of ActiveDataProvider.',
        );

        self::assertSame(
            0,
            $dataProvider->getTotalCount(),
            'Invalid filter input must produce an empty result set.',
        );
    }

    public function testSearchWithUsernameFilter(): void
    {
        $searchModel = new UserSearch();

        $dataProvider = $searchModel->search(['UserSearch' => ['username' => 'okirlin']]);

        self::assertInstanceOf(
            ActiveDataProvider::class,
            $dataProvider,
            'Search method returns an instance of ActiveDataProvider.',
        );

        $models = $dataProvider->getModels();

        self::assertCount(
            1,
            $models,
            "Username filter returns exactly one record for 'okirlin'.",
        );
        self::assertContainsOnlyInstancesOf(
            User::class,
            $models,
            'All returned models are instances of User.',
        );

        $first = reset($models);

        self::assertInstanceOf(
            User::class,
            $first,
            'First returned model is an instance of User.',
        );
        self::assertSame(
            'okirlin',
            $first->username,
            "Matched record is the 'okirlin' user (LIKE filter could match siblings).",
        );
    }
}
