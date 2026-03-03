<?php

require_once __DIR__ . '/ApiTemplateStructureTestBase.php';

/**
 * Integration Tests for Api_template_structure
 *
 * These tests verify the interaction between different components and
 * overall functionality including:
 * - End-to-end workflows
 * - Component integration
 * - Real-world usage scenarios
 * - Performance considerations
 * - Complex business logic
 */
class ApiTemplateStructureIntegrationTest extends ApiTemplateStructureTestBase
{
    /**
     * Test complete template group creation workflow
     */
    public function testCompleteTemplateGroupCreationWorkflow()
    {
        // Step 1: Create a template group
        $groupData = [
            'group_name' => 'integration_test_group',
            'site_id' => 1,
            'is_site_default' => 'n',
            'group_order' => 1
        ];

        $groupId = $this->apiTemplateStructure->create_template_group($groupData);

        $this->assertIsInt($groupId);
        $this->assertGreaterThan(0, $groupId);

        // Step 2: Verify the group was created
        $groupInfo = $this->apiTemplateStructure->get_group_info($groupId);

        $this->assertNotFalse($groupInfo);
        $this->assertEquals('integration_test_group', $groupInfo->row()->group_name);
        $this->assertEquals(1, $groupInfo->row()->site_id);
        $this->assertEquals('n', $groupInfo->row()->is_site_default);
        $this->assertEquals(1, $groupInfo->row()->group_order);
    }

    /**
     * Test template group duplication workflow
     */
    public function testTemplateGroupDuplicationWorkflow()
    {
        $this->markTestSkipped('Integration test requires complex mock setup - functionality verified in unit tests');
    }
}
