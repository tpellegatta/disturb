<?php

namespace Tests\Library\Context;

use Vpg\Disturb\ContextStorageAdapters\ElasticsearchAdapter;
use Vpg\Disturb\Exceptions\ContextStorageException;

use Phalcon\Config\Adapter\Json;

/**
 * Elasticsearch context storage Test class
 *
 * @author  Alexandre DEFRETIN <adefretin@voyageprive.com>
 */
class ElasticsearchAdapterTest extends \Tests\DisturbUnitTestCase
{
    /**
     * @const string TEST_DOCUMENT_ID
     */
    const TEST_DOCUMENT_ID = 'doc_test';

    /**
     * @const string TEST_DOCUMENT_FAKE_ID
     */
    const TEST_DOCUMENT_FAKE_ID = 'fake_doc_test';

    /**
     * @const array TEST_DOCUMENT
     */
    const TEST_DOCUMENT = [
        'key1' => 'content1',
        'key2' => 'content2'
    ];

    /**
     * @var string $elasticsearchTestHost
     */
    private $elasticsearchTestHost;

    /**
     * @var ElasticsearchAdapter $elasticsearchAdapter
     */
    private $elasticsearchAdapter;

    /**
     * Setup
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->elasticsearchAdapter = new ElasticsearchAdapter();

        $contextStorageConfig = new Json(realpath(__DIR__ . '/Config/elasticsearchConfig.json'));
        $this->elasticsearchTestHost = $contextStorageConfig[ElasticsearchAdapter::CONFIG_HOST];
    }

    /**
     * Test initConfig method
     *
     * @return void
     */
    public function testInitConfig()
    {
        // config not found
        try {
            $this->invokeMethod($this->elasticsearchAdapter, 'initConfig', []);
        } catch (\Throwable $exception) {
            if (!$exception) {
                $this->fail('Exception expected : Elasticsearch config not found');
            }
        }

        // missing required config field
        $uncompletedConfig = new Json(
            realpath(__DIR__ . '/Config/elasticsearchUncompletedConfig.json')
        );

        try {
            $this->invokeMethod($this->elasticsearchAdapter, 'initConfig', [$uncompletedConfig]);
        } catch (ContextStorageException $exception) {
            if ($exception) {
                $this->assertEquals('Vpg\Disturb\ContextStorageAdapters\ElasticsearchAdapter::initConfig : config ' .
                    ElasticsearchAdapter::CONFIG_HOST . ' not found',
                    $exception->getMessage());
            } else {
                $this->fail('Exception expected : Vpg\Disturb\ContextStorageAdapters\ElasticsearchAdapter::initConfig : 
                config ' . ElasticsearchAdapter::CONFIG_HOST . ' not found');
            }
        }

        // success
        $this->initValidConfig();

        $adapterConfig = $this->getProperty($this->elasticsearchAdapter, 'config');
        $this->assertEquals($this->elasticsearchTestHost, $adapterConfig[ElasticsearchAdapter::CONFIG_HOST]);

        $this->assertEquals(
            ElasticsearchAdapter::DEFAULT_DOC_INDEX,
            $adapterConfig[ElasticsearchAdapter::DOC_INDEX]
        );

        $this->assertEquals(
            ElasticsearchAdapter::DEFAULT_DOC_TYPE,
            $adapterConfig[ElasticsearchAdapter::DOC_TYPE]
        );
    }

    /**
     * Test checkVendorLibraryAvailable method
     *
     * @return void
     */
    public function testCheckVendorLibraryAvailable()
    {
        $badVendorLibraryName = 'badVendorLibraryName';
        try {
            $this->invokeMethod($this->elasticsearchAdapter, 'checkVendorLibraryAvailable', [$badVendorLibraryName]);
        } catch (ContextStorageException $exception) {
            if ($exception) {
                $this->assertEquals('Vpg\Disturb\ContextStorageAdapters\ElasticsearchAdapter::checkVendorLibraryAvailable : ' .
                    $badVendorLibraryName . ' lib not found. Please make "composer update"',
                    $exception->getMessage());
            } else {
                $this->fail('Vpg\Disturb\ContextStorageAdapters\ElasticsearchAdapter::checkVendorLibraryAvailable : ' .
                    $badVendorLibraryName . ' lib not found.
                 Please make "composer update"');
            }
        }

        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'checkVendorLibraryAvailable',
                [ElasticsearchAdapter::VENDOR_CLASSNAME]
            );

        } catch (\Exception $exception) {
            $this->fail('Exception expected : ' . ElasticsearchAdapter::VENDOR_CLASSNAME . ' lib not found.
             Please make "composer update"');
        }
    }

    /**
     * Test checkVendorLibraryAvailable method
     *
     * @return void
     */
    public function testInitCommonRequestParams()
    {
        $this->initValidConfig();

        $this->invokeMethod($this->elasticsearchAdapter, 'initCommonRequestParams', []);
        $commonRequestParamHash = $this->getProperty($this->elasticsearchAdapter, 'commonRequestParamHash');

        $this->assertEquals(
            ElasticsearchAdapter::DEFAULT_DOC_INDEX,
            $commonRequestParamHash[ElasticsearchAdapter::DOC_INDEX]
        );

        $this->assertEquals(
            ElasticsearchAdapter::DEFAULT_DOC_TYPE,
            $commonRequestParamHash[ElasticsearchAdapter::DOC_TYPE]
        );
    }

    /**
     * Test init client
     *
     * @return void
     */
    public function testInitClient()
    {
        // host unavailable
        $this->initBadConfig();
        $config = $this->getProperty($this->elasticsearchAdapter, 'config');

        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'initClient',
                []
            );
        } catch (ContextStorageException $exception) {
            if ($exception) {
                $this->assertEquals('Vpg\Disturb\ContextStorageAdapters\ElasticsearchAdapter::initClient : host : ' .
                    $config[ElasticsearchAdapter::CONFIG_HOST] .
                    ' not available', $exception->getMessage());
            } else {
                $this->fail('Vpg\Disturb\ContextStorageAdapters\ElasticsearchAdapter::initClient : host : ' .
                    $config[ElasticsearchAdapter::CONFIG_HOST] . ' not available');
            }
        }

        // host / index available
        $this->initValidConfig();

        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'initClient',
                []
            );
        } catch (\Exception $exception) {
            $this->fail('ElasticsearchAdatper initClient : ' . $exception->getMessage());
        }
    }

    /**
     * Test save
     */
    public function testSave()
    {
        $this->initializeAdapter();

        // bad parameter
        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'save',
                ['', []]
            );
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_INVALID_PARAMETER);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_INVALID_PARAMETER);
            }
        }

        // succedeed
        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'save',
                [self::TEST_DOCUMENT_ID, self::TEST_DOCUMENT]
            );
        } catch (\Exception $exception) {
            $this->fail('Elasticsearch save failed : ' . $exception->getMessage());
        }
    }

    /**
     * Test get
     *
     * @return void
     */
    public function testGet()
    {
        $this->initializeAdapter();

        // bad parameter
        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'get',
                ['']
            );
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_INVALID_PARAMETER);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_INVALID_PARAMETER);
            }
        }

        // document not found
        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'get',
                [self::TEST_DOCUMENT_FAKE_ID]
            );
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_GET);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_GET);
            }
        }

        // document found
        try {
            $docHash = $this->invokeMethod(
                $this->elasticsearchAdapter,
                'get',
                [self::TEST_DOCUMENT_ID]
            );

            $this->assertArrayHasKey('_id', $docHash);
            $this->assertEquals(self::TEST_DOCUMENT_ID, $docHash['_id']);

            $this->assertArrayHasKey('_source', $docHash);
            $this->assertArraysEquals(self::TEST_DOCUMENT, $docHash['_source']);
        } catch (\Exception $exception) {
            $this->fail($exception->getMessage());
        }
    }

    /**
     * Test exist function
     */
    public function testExist()
    {
        $this->initializeAdapter();

        // bad parameter
        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'exist',
                ['']
            );
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_INVALID_PARAMETER);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_INVALID_PARAMETER);
            }
        }

        // document not exists
        try {
            $doesDocumentExist = $this->invokeMethod(
                $this->elasticsearchAdapter,
                'exist',
                [self::TEST_DOCUMENT_FAKE_ID]
            );
            $this->assertFalse($doesDocumentExist);
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_EXIST);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_EXIST);
            }
        }

        // document exists
        try {
            $doesDocumentExist = $this->invokeMethod(
                $this->elasticsearchAdapter,
                'exist',
                [self::TEST_DOCUMENT_ID]
            );
            $this->assertTrue($doesDocumentExist);
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_EXIST);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_EXIST);
            }
        }
    }

    /**
     * Test delete function
     */
    public function testDelete()
    {
        $this->initializeAdapter();

        // bad parameter
        try {
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'delete',
                ['']
            );
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_INVALID_PARAMETER);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_INVALID_PARAMETER);
            }
        }

        // succedeed
        try {
            // check if test document exist
            $doesDocumentExist = $this->invokeMethod(
                $this->elasticsearchAdapter,
                'exist',
                [self::TEST_DOCUMENT_ID]
            );
            $this->assertTrue($doesDocumentExist);

            // deleting test document
            $this->invokeMethod(
                $this->elasticsearchAdapter,
                'delete',
                [self::TEST_DOCUMENT_ID]
            );

            // check if test document is correctly deleted
            $doesDocumentExist = $this->invokeMethod(
                $this->elasticsearchAdapter,
                'exist',
                [self::TEST_DOCUMENT_ID]
            );
            $this->assertFalse($doesDocumentExist);
        } catch (\Exception $exception) {
            if ($exception) {
                $this->assertEquals($exception->getCode(), ContextStorageException::CODE_DELETE);
            } else {
                $this->fail('Exception code expected : ' . ContextStorageException::CODE_DELETE);
            }
        }
    }

    /**
     * Init valid config
     *
     * @return void
     */
    private function initValidConfig()
    {
        $config = new Json(
            realpath(__DIR__ . '/Config/elasticsearchConfig.json')
        );
        $this->invokeMethod($this->elasticsearchAdapter, 'initConfig', [$config]);
    }

    /**
     * Init bad config
     *
     * @return void
     */
    private function initBadConfig()
    {
        $config = new Json(
            realpath(__DIR__ . '/Config/elasticsearchBadConfig.json')
        );
        $this->invokeMethod($this->elasticsearchAdapter, 'initConfig', [$config]);
    }

    /**
     * Initialize adapter
     *
     * @return void
     */
    private function initializeAdapter()
    {
        $config = new Json(
            realpath(__DIR__ . '/Config/elasticsearchConfig.json')
        );
        $this->invokeMethod($this->elasticsearchAdapter, 'initialize', [$config]);
    }
}