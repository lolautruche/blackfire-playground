<?php

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\MinkExtension\Context\RawMinkContext;
use Blackfire\Bridge\Behat\Context\BlackfireContextTrait;

/**
 * Defines application features from the specific context.
 */
class FeatureContext extends RawMinkContext
{
    use BlackfireContextTrait;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @BeforeScenario
     */
    public function beforeScenario(BeforeScenarioScope $scope)
    {
        /** @var \Blackfire\Bridge\Symfony\BlackfiredHttpBrowser $client */
//        $client = $this->getSession()->getDriver()->getClient();
//        $client->disableProfiling();
//        $client->followRedirects(true);
//        $client->enableProfiling();
    }
}
