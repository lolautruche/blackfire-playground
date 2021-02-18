<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Controller\Admin;

use App\Repository\PostRepository;
use Blackfire\Bridge\PhpUnit\BlackfireTestCase;
use Blackfire\Build\BuildHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Response;

/**
 * Functional test for the controllers defined inside the BlogController used
 * for managing the blog in the backend.
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
class BlogControllerTest extends BlackfireTestCase
{
    // Let's keep control on the Blackfire Scenarios.
    protected const BLACKFIRE_SCENARIO_AUTO_START = false;

    /**
     * @dataProvider getUrlsForRegularUsers
     */
    public function testAccessDeniedForRegularUsers(string $httpMethod, string $url): void
    {
        // Here we use the default WebTestCase client.
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'john_user',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $client->request($httpMethod, $url);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function getUrlsForRegularUsers(): ?\Generator
    {
        yield ['GET', '/en/admin/post/'];
        yield ['GET', '/en/admin/post/1'];
        yield ['GET', '/en/admin/post/1/edit'];
        yield ['POST', '/en/admin/post/1/delete'];
    }

    public function testAdminBackendHomePage(): void
    {
        $buildHelper = BuildHelper::getInstance();
        $buildHelper->createScenario('Admin Backend HomePage');

        $client = static::createBlackfiredHttpBrowserClient();
        $client->request('GET', '/en/admin/post/', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists(
            'body#admin_post_index #main tbody tr',
            'The backend homepage displays all the available posts.'
        );

        $buildHelper->endCurrentScenario();
    }

    /**
     * This test changes the database contents by creating a new blog post.
     */
    public function testAdminNewPost(): void
    {
        $buildHelper = BuildHelper::getInstance();
        $buildHelper->createScenario('Admin New Post');

        $postTitle = 'Blog Post Title '.mt_rand();
        $postSummary = $this->generateRandomString(255);
        $postContent = $this->generateRandomString(1024);

        $client = static::createBlackfiredHttpBrowserClient();
        $client->followRedirects(false);
        $client->request('GET', '/en/admin/post/new', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);
        $client->submitForm('Create post', [
            'post[title]' => $postTitle,
            'post[summary]' => $postSummary,
            'post[content]' => $postContent,
        ]);

        $this->assertResponseRedirects('/en/admin/post/', Response::HTTP_FOUND);

        /** @var \App\Entity\Post $post */
        static::bootKernel();
        $post = static::$container->get(PostRepository::class)->findOneByTitle($postTitle);
        $this->assertNotNull($post);
        $this->assertSame($postSummary, $post->getSummary());
        $this->assertSame($postContent, $post->getContent());

        $buildHelper->endCurrentScenario();
    }

    public function testAdminNewDuplicatedPost(): void
    {
        $buildHelper = BuildHelper::getInstance();
        $buildHelper->createScenario('Admin New Duplicated Post');

        $postTitle = 'Blog Post Title '.mt_rand();
        $postSummary = $this->generateRandomString(255);
        $postContent = $this->generateRandomString(1024);

        $client = static::createBlackfiredHttpBrowserClient();
        $client->followRedirects(false);
        // Disable profiling as we just did it before.
        $client->disableProfiling();
        $crawler = $client->request('GET', '/en/admin/post/new', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);
        $form = $crawler->selectButton('Create post')->form([
            'post[title]' => $postTitle,
            'post[summary]' => $postSummary,
            'post[content]' => $postContent,
        ]);
        $client->submit($form);

        // post titles must be unique, so trying to create the same post twice should result in an error
        $client->enableProfiling();
        $client->submit($form);

        $this->assertSelectorTextSame('form .form-group.has-error label', 'Title');
        $this->assertSelectorTextContains('form .form-group.has-error .help-block', 'This title was already used in another blog post, but they must be unique.');

        $buildHelper->endCurrentScenario();
    }

    public function testAdminShowPost(): void
    {
        $buildHelper = BuildHelper::getInstance();
        $buildHelper->createScenario('Admin Show Post');

        $client = static::createBlackfiredHttpBrowserClient();
        $client->request('GET', '/en/admin/post/1', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);

        $this->assertResponseIsSuccessful();

        $buildHelper->endCurrentScenario();
    }

    /**
     * This test changes the database contents by editing a blog post.
     */
    public function testAdminEditPost(): void
    {
        $buildHelper = BuildHelper::getInstance();
        $buildHelper->createScenario('Admin Edit Post');

        $newBlogPostTitle = 'Blog Post Title '.mt_rand();

        $client = static::createBlackfiredHttpBrowserClient();
        $client->followRedirects(false);
        $client->request('GET', '/en/admin/post/1/edit', [], [], [
            'HTTP_Authorization' => sprintf('Basic %s', base64_encode('jane_admin:kitten')),
        ]);
        $client->submitForm('Save changes', [
            'post[title]' => $newBlogPostTitle,
        ]);

        $this->assertResponseRedirects('/en/admin/post/1/edit', Response::HTTP_FOUND);

        /** @var \App\Entity\Post $post */

        static::bootKernel();
        $post = static::$container->get(PostRepository::class)->find(1);
        $this->assertSame($newBlogPostTitle, $post->getTitle());

        $buildHelper->endCurrentScenario();
    }

    /**
     * This test changes the database contents by deleting a blog post. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testAdminDeletePost(): void
    {
        // Here we use the default WebTestCase client.
        $client = static::createClient([], [
            'PHP_AUTH_USER' => 'jane_admin',
            'PHP_AUTH_PW' => 'kitten',
        ]);
        $crawler = $client->request('GET', '/en/admin/post/1');
        $client->submit($crawler->filter('#delete-form')->form());

        $this->assertResponseRedirects('/en/admin/post/', Response::HTTP_FOUND);

        $post = static::$container->get(PostRepository::class)->find(1);
        $this->assertNull($post);
    }

    private function generateRandomString(int $length): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return mb_substr(str_shuffle(str_repeat($chars, ceil($length / mb_strlen($chars)))), 1, $length);
    }
}
