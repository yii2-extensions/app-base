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
        $this->loginAs($I, 'admin');

        $I->amOnRoute('user/login');
        $I->seeResponseCodeIs(403);
    }

    public function authenticatedUserCannotAccessSignup(AcceptanceTester $I): void
    {
        $this->loginAs($I, 'admin');

        $I->amOnRoute('user/signup');
        $I->seeResponseCodeIs(403);
    }

    public function authenticatedUserCanReachResetPasswordWithToken(AcceptanceTester $I): void
    {
        $this->loginAs($I, 'admin');

        $owner = User::findByUsername('okirlin');

        $I->assertNotNull(
            $owner,
            "Expected fixture user 'okirlin' to exist.",
        );
        $I->amOnRoute('user/reset-password', ['token' => $owner->password_reset_token]);
        $I->dontSeeResponseCodeIs(403);
    }

    public function authenticatedUserCanReachVerifyEmailWithToken(AcceptanceTester $I): void
    {
        $this->loginAs($I, 'admin');

        $owner = User::findOne(['username' => 'test.test']);

        $I->assertNotNull(
            $owner,
            "Expected fixture user 'test.test' to exist.",
        );
        $I->amOnRoute('user/verify-email', ['token' => $owner->verification_token]);
        $I->dontSeeResponseCodeIs(403);
    }

    public function getVerbIsRejectedOnLogout(AcceptanceTester $I): void
    {
        $this->loginAs($I, 'admin');

        $I->amOnRoute('user/logout');
        $I->seeResponseCodeIs(405);
    }

    public function guestIsRedirectedFromUserIndex(AcceptanceTester $I): void
    {
        $I->amOnRoute('user/index');
        $I->seeInCurrentUrl('user/login');
        $I->seeResponseCodeIs(200);
    }

    public function nonAdminGetsForbiddenOnUserIndex(AcceptanceTester $I): void
    {
        $this->loginAs($I, 'okirlin');

        $I->amOnRoute('user/index');
        $I->seeResponseCodeIs(403);
    }

    /**
     * Resolves a fixture user by username, fails fast if missing, and logs them in.
     *
     * Centralizes the `null`-guard so a missing or renamed fixture row surfaces as a clear test failure instead of a
     * confusing `amLoggedInAs(null)` downstream error.
     */
    private function loginAs(AcceptanceTester $I, string $username): void
    {
        $user = User::findByUsername($username);

        $I->assertNotNull(
            $user,
            "Expected fixture user '{$username}' to exist.",
        );
        $I->amLoggedInAs($user);
    }
}
