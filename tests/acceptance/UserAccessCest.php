<?php

declare(strict_types=1);

namespace app\tests\acceptance;

use app\models\User;
use app\tests\support\AcceptanceTester;
use app\tests\support\fixtures\UserFixture;

/**
 * Acceptance tests for {@see \app\controllers\UserController} `behaviors()` pipeline.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 0.1
 */
final class UserAccessCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->haveFixtures(
            [
                'user' => [
                    'class' => UserFixture::class,
                    // @phpstan-ignore binaryOp.invalid
                    'dataFile' => codecept_data_dir() . 'user.php',
                ],
            ],
        );
    }

    public function authenticatedUserCannotAccessLogin(AcceptanceTester $I): void
    {
        $user = User::findByUsername('admin');

        $I->amLoggedInAs($user);
        $I->amOnRoute('user/login');
        $I->seeResponseCodeIs(403);
    }

    public function authenticatedUserCannotAccessSignup(AcceptanceTester $I): void
    {
        $user = User::findByUsername('admin');

        $I->amLoggedInAs($user);
        $I->amOnRoute('user/signup');
        $I->seeResponseCodeIs(403);
    }

    public function getVerbIsRejectedOnLogout(AcceptanceTester $I): void
    {
        $user = User::findByUsername('admin');

        $I->amLoggedInAs($user);
        $I->amOnRoute('user/logout');
        $I->seeResponseCodeIs(405);
    }

    public function guestIsRedirectedFromUserIndex(AcceptanceTester $I): void
    {
        $I->amOnRoute('user/index');
        $I->seeInCurrentUrl('user/login');
    }

    public function nonAdminGetsForbiddenOnUserIndex(AcceptanceTester $I): void
    {
        $user = User::findByUsername('okirlin');

        $I->amLoggedInAs($user);
        $I->amOnRoute('user/index');
        $I->seeResponseCodeIs(403);
    }
}
