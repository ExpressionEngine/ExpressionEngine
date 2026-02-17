<?php

require_once 'ChannelApiTestBase.php';

/**
 * Performance tests for Api_channel_entries
 * Tests handling of large datasets and memory usage
 */
class ApiChannelEntriesPerformanceTest extends ChannelApiTestBase
{
    /**
     * Test performance with large entry data
     */
    public function testLargeEntryDataPerformance()
    {
        $largeContent = str_repeat('Large content block for performance testing. ', 1000); // ~50KB

        $entryData = [
            'channel_id' => 1,
            'title' => 'Performance Test Entry',
            'url_title' => 'performance-test-entry',
            'entry_date' => time(),
            'author_id' => 1,
            'status' => 'open',
            'field_id_1' => $largeContent,
            'field_id_2' => str_repeat('Another large field content. ', 500)
        ];

        // Add many custom fields
        for ($i = 3; $i <= 50; $i++) {
            $entryData["field_id_{$i}"] = str_repeat("Field {$i} content. ", 100);
        }

        // Mock _validate_url_title
        $this->api = $this->getMockBuilder(Api_channel_entries::class)
            ->setMethods(['_validate_url_title'])
            ->getMock();
        $this->api->expects($this->once())
            ->method('_validate_url_title')
            ->willReturn('performance-test-entry');

        $this->api->channel_id = 1;

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $result = $this->api->save_entry($entryData, 1, 0, false);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Verify successful processing
        $this->assertTrue($result);

        // Check performance metrics
        $processingTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        // Should complete within reasonable time (adjust threshold as needed)
        $this->assertLessThan(5.0, $processingTime, 'Processing should complete within 5 seconds');

        // Should not use excessive memory (adjust threshold as needed)
        $this->assertLessThan(50 * 1024 * 1024, $memoryUsage, 'Should use less than 50MB of memory');
    }

    /**
     * Test performance with many custom fields
     */
    public function testManyCustomFieldsPerformance()
    {
        $entryData = [
            'channel_id' => 1,
            'title' => 'Many Fields Test',
            'url_title' => 'many-fields-test',
            'entry_date' => time(),
            'author_id' => 1,
            'status' => 'open'
        ];

        // Add 100 custom fields
        for ($i = 1; $i <= 100; $i++) {
            $entryData["field_id_{$i}"] = "Content for field {$i}";
        }

        // Mock _validate_url_title
        $this->api = $this->getMockBuilder(Api_channel_entries::class)
            ->setMethods(['_validate_url_title'])
            ->getMock();
        $this->api->expects($this->once())
            ->method('_validate_url_title')
            ->willReturn('many-fields-test');

        $this->api->channel_id = 1;

        $startTime = microtime(true);

        $result = $this->api->save_entry($entryData, 1, 0, false);

        $endTime = microtime(true);

        // Verify successful processing
        $this->assertTrue($result);

        // Check processing time
        $processingTime = $endTime - $startTime;
        $this->assertLessThan(3.0, $processingTime, 'Should process 100 fields within 3 seconds');
    }

    /**
     * Test memory efficiency with repeated operations
     */
    public function testMemoryEfficiencyWithRepeatedOperations()
    {
        $initialMemory = memory_get_usage();

        // Perform 50 entry operations
        for ($i = 1; $i <= 50; $i++) {
            $entryData = [
                'channel_id' => 1,
                'title' => "Entry {$i}",
                'url_title' => "entry-{$i}",
                'entry_date' => time(),
                'author_id' => 1,
                'status' => 'open',
                'field_id_1' => str_repeat("Content for entry {$i}. ", 50)
            ];

            // Mock _validate_url_title for each iteration
            $this->api = $this->getMockBuilder(Api_channel_entries::class)
                ->setMethods(['_validate_url_title'])
                ->getMock();
            $this->api->expects($this->once())
                ->method('_validate_url_title')
                ->willReturn("entry-{$i}");

            $this->api->channel_id = 1;

            $result = $this->api->save_entry($entryData, 1, 0, false);
            $this->assertTrue($result);

            // Check memory usage every 10 iterations
            if ($i % 10 === 0) {
                $currentMemory = memory_get_usage();
                $memoryIncrease = $currentMemory - $initialMemory;

                // Should not have excessive memory growth
                $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease,
                    "Memory increase should be less than 10MB after {$i} operations");
            }
        }

        $finalMemory = memory_get_usage();
        $totalMemoryIncrease = $finalMemory - $initialMemory;

        // Total memory increase should be reasonable
        $this->assertLessThan(20 * 1024 * 1024, $totalMemoryIncrease,
            'Total memory increase should be less than 20MB for 50 operations');
    }

    /**
     * Test performance with concurrent simulated operations
     */
    public function testConcurrentOperationsPerformance()
    {
        $operations = [];

        // Prepare 20 concurrent operations
        for ($i = 1; $i <= 20; $i++) {
            $operations[] = [
                'channel_id' => 1,
                'title' => "Concurrent Entry {$i}",
                'url_title' => "concurrent-entry-{$i}",
                'entry_date' => time(),
                'author_id' => 1,
                'status' => 'open',
                'field_id_1' => str_repeat("Concurrent content {$i}. ", 100)
            ];
        }

        $startTime = microtime(true);

        // Process all operations
        foreach ($operations as $operation) {
            $this->api = $this->getMockBuilder(Api_channel_entries::class)
                ->setMethods(['_validate_url_title'])
                ->getMock();
            $this->api->expects($this->once())
                ->method('_validate_url_title')
                ->willReturn($operation['url_title']);

            $this->api->channel_id = 1;
            $result = $this->api->save_entry($operation, 1, 0, false);
            $this->assertTrue($result);
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Should complete within reasonable time
        $this->assertLessThan(10.0, $totalTime, '20 concurrent operations should complete within 10 seconds');
    }

    /**
     * Test performance with deeply nested data structures
     */
    public function testDeeplyNestedDataPerformance()
    {
        // Create deeply nested data structure
        $nestedData = [
            'channel_id' => 1,
            'title' => 'Nested Data Test',
            'url_title' => 'nested-data-test',
            'entry_date' => time(),
            'author_id' => 1,
            'status' => 'open'
        ];

        // Add deeply nested custom field data
        $current = &$nestedData;
        for ($i = 1; $i <= 10; $i++) {
            $current["field_id_{$i}"] = [];
            $current = &$current["field_id_{$i}"];
            $current['nested_content'] = str_repeat("Nested level {$i} content. ", 50);
        }

        // Mock _validate_url_title
        $this->api = $this->getMockBuilder(Api_channel_entries::class)
            ->setMethods(['_validate_url_title'])
            ->getMock();
        $this->api->expects($this->once())
            ->method('_validate_url_title')
            ->willReturn('nested-data-test');

        $this->api->channel_id = 1;

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $result = $this->api->save_entry($nestedData, 1, 0, false);

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Verify processing completed
        $this->assertTrue($result);

        // Check performance
        $processingTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;

        $this->assertLessThan(2.0, $processingTime, 'Deep nesting should process within 2 seconds');
        $this->assertLessThan(5 * 1024 * 1024, $memoryUsage, 'Deep nesting should use less than 5MB');
    }

    /**
     * Test performance with large batch operations
     */
    public function testLargeBatchOperationsPerformance()
    {
        $batchSize = 100;
        $batchData = [];

        // Prepare batch data
        for ($i = 1; $i <= $batchSize; $i++) {
            $batchData[] = [
                'channel_id' => 1,
                'title' => "Batch Entry {$i}",
                'url_title' => "batch-entry-{$i}",
                'entry_date' => time(),
                'author_id' => 1,
                'status' => 'open',
                'field_id_1' => "Batch content {$i}"
            ];
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        $processedCount = 0;
        foreach ($batchData as $entryData) {
            $this->api = $this->getMockBuilder(Api_channel_entries::class)
                ->setMethods(['_validate_url_title'])
                ->getMock();
            $this->api->expects($this->once())
                ->method('_validate_url_title')
                ->willReturn($entryData['url_title']);

            $this->api->channel_id = 1;
            $result = $this->api->save_entry($entryData, 1, 0, false);

            if ($result) {
                $processedCount++;
            }
        }

        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        // Verify all entries were processed
        $this->assertEquals($batchSize, $processedCount);

        // Check performance metrics
        $totalTime = $endTime - $startTime;
        $avgTimePerEntry = $totalTime / $batchSize;
        $totalMemory = $endMemory - $startMemory;

        // Performance assertions
        $this->assertLessThan(15.0, $totalTime, 'Batch processing should complete within 15 seconds');
        $this->assertLessThan(0.1, $avgTimePerEntry, 'Average time per entry should be less than 0.1 seconds');
        $this->assertLessThan(30 * 1024 * 1024, $totalMemory, 'Batch processing should use less than 30MB');
    }

    /**
     * Test memory leak prevention
     */
    public function testMemoryLeakPrevention()
    {
        $initialMemory = memory_get_usage();

        // Perform operations that might cause memory leaks
        for ($i = 1; $i <= 100; $i++) {
            $entryData = [
                'channel_id' => 1,
                'title' => "Memory Test Entry {$i}",
                'url_title' => "memory-test-entry-{$i}",
                'entry_date' => time(),
                'author_id' => 1,
                'status' => 'open',
                'large_field' => str_repeat("Large content that might cause memory issues {$i}. ", 200)
            ];

            $this->api = $this->getMockBuilder(Api_channel_entries::class)
                ->setMethods(['_validate_url_title'])
                ->getMock();
            $this->api->expects($this->once())
                ->method('_validate_url_title')
                ->willReturn("memory-test-entry-{$i}");

            $this->api->channel_id = 1;
            $result = $this->api->save_entry($entryData, 1, 0, false);
            $this->assertTrue($result);

            // Force garbage collection if available
            if (function_exists('gc_collect_cycles')) {
                gc_collect_cycles();
            }
        }

        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable and not growing unbounded
        $this->assertLessThan(15 * 1024 * 1024, $memoryIncrease,
            'Memory increase should be less than 15MB after 100 operations (no memory leaks)');
    }
}
