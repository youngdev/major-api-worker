<?php

namespace MajorApi\Tests\Functional\Quickbooks\Command;

use MajorApi\Quickbooks\Command\ItemQueryCommand;
use MajorApi\Quickbooks\IppClient;
use MajorApi\Library\Registry;
use MajorApi\Tests\Functional\TestCase;

/**
 * @group FunctionalTests
 */
class ItemQueryCommandTest extends TestCase
{

    /**
     * @expectedException MajorApi\Quickbooks\Parser\Exception\Exception
     */
    public function testQueryingItemsRequiresIppApplication()
    {
        $quickbooksQueueId = 0;
        $objectId = 0;
        $application = $this->getApplicationFixture();

        $command = new ItemQueryCommand(
            Registry::getPostgres(),
            Registry::getTwig(),
            $application['id'],
            $quickbooksQueueId,
            $objectId,
            uniqid(),
            uniqid()
        );

        $parser = $command->execute();
        $parser->load();
    }

    public function testQueryingItems()
    {
        $quickbooksQueueId = 0;
        $objectId = 0;

        $responseXmlFilePath = sprintf('%s/%s', $this->fixtureDir, 'ipp-item-query-response.xml');
        $responseXml = file_get_contents($responseXmlFilePath);

        $majorApiConfig = Registry::getMajorApiConfig();
        $application = $this->getIppApplicationFixture();

        $mockIppClient = $this->getMockBuilder('MajorApi\Quickbooks\IppDesktopClient')
            ->disableOriginalConstructor()
            ->setMethods(['read', 'getLastResponse'])
            ->getMock();
        $mockIppClient->expects($this->once())
            ->method('read')
            ->will($this->returnValue('true'));
        $mockIppClient->expects($this->once())
            ->method('getLastResponse')
            ->will($this->returnValue($responseXml));
        
        $command = new ItemQueryCommand(
            Registry::getPostgres(),
            Registry::getTwig(),
            $application['id'],
            $quickbooksQueueId,
            $objectId,
            $majorApiConfig['test_ipp_oauth_consumer_key'],
            $majorApiConfig['test_ipp_oauth_consumer_secret']
        );

        $command->setIppClient($mockIppClient);
        $parser = $command->execute();

        $parser->load();
        $parser->initialize();

        $quickbooksItems = $parser->parse();

        $this->assertGreaterThan(0, $quickbooksItems->count());
        $this->assertInstanceOf('MajorApi\Quickbooks\Parser\Ipp\ItemQueryParser', $parser);
    }

}
