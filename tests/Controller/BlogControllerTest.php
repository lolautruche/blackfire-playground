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

use App\Entity\Post;
use App\Pagination\Paginator;
use Blackfire\Build\BuildHelper;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Panther\PantherTestCase;

/**
 * Functional test for the controllers defined inside BlogController.
 *
 * See https://symfony.com/doc/current/testing.html#functional-tests
 *
 * Execute the application tests using this command (requires PHPUnit to be installed):
 *
 *     $ cd your-symfony-project/
 *     $ ./vendor/bin/phpunit
 */
class BlogControllerTest extends PantherTestCase/*WebTestCase*/
{
    use BlackfireTestCaseTrait;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        BuildHelper::getInstance()->startBuild('e97cbe91-24b0-40ed-8cb6-f3c9886bccc7', 'Playground build with Symfony functional tests');
        BuildHelper::getInstance()->createScenario('Blog Controller');
    }

    public static function tearDownAfterClass(): void
    {
        BuildHelper::getInstance()->endCurrentScenario();
        $report = BuildHelper::getInstance()->endCurrentBuild();
        echo $report->getUrl();
        parent::tearDownAfterClass();
    }

    public function testIndex(): void
    {
        $client = static::createBlackfiredHttpBrowserClient();
        $crawler = $client->request('GET', '/en/blog/');

        $this->assertResponseIsSuccessful();

        $this->assertCount(
            Paginator::PAGE_SIZE,
            $crawler->filter('article.post'),
            'The homepage displays the right number of posts.'
        );
    }

    public function testRss(): void
    {
        $client = static::createBlackfiredHttpBrowserClient();
        $crawler = $client->request('GET', '/en/blog/rss.xml');

        $this->assertResponseHeaderSame('Content-Type', 'text/xml; charset=UTF-8');

        $this->assertCount(
            Paginator::PAGE_SIZE,
            $crawler->filter('item'),
            'The xml file displays the right number of posts.'
        );
    }

    /**
     * This test changes the database contents by creating a new comment. However,
     * thanks to the DAMADoctrineTestBundle and its PHPUnit listener, all changes
     * to the database are rolled back when this test completes. This means that
     * all the application tests begin with the same database contents.
     */
    public function testNewComment(): void
    {
        $client = static::createBlackfiredHttpBrowserClient();
        $client->followRedirects();

        // Find first blog post
        $crawler = $client->request('GET', '/en/blog/');
        $postLink = $crawler->filter('article.post > h2 a')->link();

        $client->click($postLink);
        $client->clickLink('Sign in');
        $client->submitForm('Sign in', [
            '_username' => 'john_user',
            '_password' => 'kitten',
        ]);

        $crawler = $client->submitForm('Publish comment', [
            'comment[content]' => 'Hi, Symfony!',
        ]);

        $newComment = $crawler->filter('.post-comment')->first()->filter('div > p')->text();

        $this->assertSame('Hi, Symfony!', $newComment);
    }

    public function testAjaxSearch(): void
    {
        $client = static::createBlackfiredHttpBrowserClient();
        $client->xmlHttpRequest('GET', '/en/blog/search?q=lorem', ['q' => 'lorem']);

        $results = json_decode($client->getResponse()->getContent(), true);

        $this->assertResponseHeaderSame('Content-Type', 'application/json');
        $this->assertCount(1, $results);
        $this->assertSame('Lorem ipsum dolor sit amet consectetur adipiscing elit', $results[0]['title']);
        $this->assertSame('Jane Doe', $results[0]['author']);
    }
}
