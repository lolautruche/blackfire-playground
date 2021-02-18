<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller;

use App\Repository\UserRepository;
use Blackfire\Bridge\PhpUnit\BlackfireTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the UserController used
 * for managing the current logged user.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Whenever you test resources protected by a firewall, consider using the
 * technique explained in:
 * https://symfony.com/doc/current/testing/http_authentication.html
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class UserControllerTest extends BlackfireTestCase
{
    protected const BLACKFIRE_SCENARIO_TITLE = 'User Controller';

    public function tearDown(): void
    {
        // Enforce to "quit" the browser session.
        self::$httpBrowserClient = null;
        parent::tearDown();
    }

    /**
     * @dataProvider getUrlsForAnonymousUsers
     */
    public function testAccessDeniedForAnonymousUsers(string $httpMethod, string $url): void
    {
        $client = static::createBlackfiredHttpBrowserClient();
        $client->followRedirects(false);
        $client->request($httpMethod, $url);

        $this->assertResponseRedirects(
            'https://localhost:8000/en/login',
            Response::HTTP_FOUND,
            sprintf('The %s secure URL redirects to the login form.', $url)
        );
    }

    public function getUrlsForAnonymousUsers(): ?\Generator
    {
        yield ['GET', '/en/profile/edit'];
        yield ['GET', '/en/profile/change-password'];
    }

    public function testEditUser(): void
    {
        $originalUserEmail = 'admin_jane@symfony.com';
        $newUserEmail = 'admin_jane@symfony.com';

        $client = static::createBlackfiredHttpBrowserClient();
        $client->followRedirects(false);
        $client->request('GET', '/en/profile/edit', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);
        $client->submitForm('Save changes', [
            'user[email]' => $newUserEmail,
        ]);

        $this->assertResponseRedirects('/en/profile/edit', Response::HTTP_FOUND);

        static::bootKernel();
        /** @var \App\Entity\User $user */
        $user = self::$container->get(UserRepository::class)->findOneByEmail($newUserEmail);

        $this->assertNotNull($user);
        $this->assertSame($newUserEmail, $user->getEmail());

        // Now rollback changes
        $client->disableProfiling();
        $client->request('GET', '/en/profile/edit');
        $client->submitForm('Save changes', [
            'user[email]' => $originalUserEmail,
        ]);
    }

    public function testChangePassword(): void
    {
        $newUserPassword = 'new-password';

        $client = static::createBlackfiredHttpBrowserClient();
        $client->followRedirects(false);
        $client->request('GET', '/en/profile/change-password', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);
        $client->submitForm('Save changes', [
            'change_password[currentPassword]' => 'kitten',
            'change_password[newPassword][first]' => $newUserPassword,
            'change_password[newPassword][second]' => $newUserPassword,
        ]);

        $this->assertResponseRedirects(
            '/en/logout',
            Response::HTTP_FOUND,
            'Changing password logout the user.'
        );

        // Now rollback changes
        $client->disableProfiling();
        $client->request('GET', '/en/profile/change-password', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);
        $client->submitForm('Save changes', [
            'change_password[currentPassword]' => $newUserPassword,
            'change_password[newPassword][first]' => 'kitten',
            'change_password[newPassword][second]' => 'kitten',
        ]);
    }
}
