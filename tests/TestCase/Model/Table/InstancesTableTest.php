<?php
declare(strict_types=1);

namespace App\Test\TestCase\Model\Table;

use App\Model\Table\InstancesTable;
use Cake\TestSuite\TestCase;

/**
 * App\Model\Table\InstancesTable Test Case
 */
class InstancesTableTest extends TestCase
{
    /**
     * Test subject
     *
     * @var \App\Model\Table\InstancesTable
     */
    protected $Instances;

    /**
     * Fixtures
     *
     * @var array<string>
     */
    protected array $fixtures = [
        'app.Instances',
        'app.ClubAdmins',
        'app.ActivationLicenses',
        'app.AppRemoteConfig',
        'app.Devices',
        'app.Versions',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $config = $this->getTableLocator()->exists('Instances') ? [] : ['className' => InstancesTable::class];
        $this->Instances = $this->getTableLocator()->get('Instances', $config);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->Instances);

        parent::tearDown();
    }

    /**
     * Test validationDefault method
     *
     * @return void
     * @link \App\Model\Table\InstancesTable::validationDefault()
     */
    public function testValidationDefault(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }

    /**
     * Test buildRules method
     *
     * @return void
     * @link \App\Model\Table\InstancesTable::buildRules()
     */
    public function testBuildRules(): void
    {
        $this->markTestIncomplete('Not implemented yet.');
    }
}
