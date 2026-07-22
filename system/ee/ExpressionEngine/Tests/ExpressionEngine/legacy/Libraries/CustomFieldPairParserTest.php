<?php

namespace {
    require_once __DIR__ . '/../../../eeObjectMock.php';
    require_once SYSPATH . 'ee/legacy/libraries/channel_entries_parser/Parser.php';

    if (!interface_exists('EE_Channel_parser_component', false)) {
        interface EE_Channel_parser_component
        {
            public function disabled(array $disabled, \EE_Channel_preparser $pre);
            public function pre_process($tagdata, \EE_Channel_preparser $pre);
            public function replace($tagdata, \EE_Channel_data_parser $obj, $preparse_data);
        }
    }

    require_once SYSPATH . 'ee/legacy/libraries/channel_entries_parser/components/Custom_field_pair.php';
}

namespace ExpressionEngine\Tests\ExpressionEngine\legacy\Libraries {
    use PHPUnit\Framework\TestCase;

    class CustomFieldPairDataParserStub extends \EE_Channel_data_parser
    {
        private $row;
        private $prefix;
        private $channel;
        private $preparsed;

        public function __construct(array $row, string $prefix, object $channel, object $preparsed)
        {
            $this->row = $row;
            $this->prefix = $prefix;
            $this->channel = $channel;
            $this->preparsed = $preparsed;
        }

        public function row()
        {
            return $this->row;
        }

        public function prefix()
        {
            return $this->prefix;
        }

        public function channel()
        {
            return $this->channel;
        }

        public function preparsed()
        {
            return $this->preparsed;
        }
    }

    class CustomFieldPairParserTest extends TestCase
    {
        protected function tearDown(): void
        {
            ee()->resetMocks();
        }

        public function testReplaceUsesStableFluidDetectionWhenFieldTypeMutatesDuringNestedParse(): void
        {
            $fieldType = new class(11) {
                private $fieldId;
                public $disable_frontedit = false;

                public function __construct(int $fieldId)
                {
                    $this->fieldId = $fieldId;
                }

                public function _init(array $config)
                {
                }

                public function id()
                {
                    return $this->fieldId;
                }

                public function replace_tag($data, $params = [], $tagdata = '')
                {
                    return $tagdata;
                }
            };

            $apiChannelFields = new class($fieldType) {
                public $field_type = 'fluid_field';
                public $ft_paths = ['fluid_field' => '/tmp'];

                private $fieldType;

                public function __construct($fieldType)
                {
                    $this->fieldType = $fieldType;
                }

                public function setup_handler($fieldId, $strict = true)
                {
                    $this->field_type = 'fluid_field';
                    return $this->fieldType;
                }

                public function apply($method, array $args)
                {
                    if ($method === 'pre_process') {
                        return $args[0];
                    }

                    if ($method === 'replace_tag') {
                        // Simulate nested parsing mutating API state away from fluid_field.
                        $this->field_type = 'text';
                        return $args[2];
                    }

                    return '';
                }
            };

            $frontEdit = new class {
                public $calls = 0;

                public function entryFieldEditLink($siteId, $channelId, $entryId, $fieldId)
                {
                    $this->calls++;
                    return '{frontedit_link}';
                }
            };

            ee()->setMock('api_channel_fields', $apiChannelFields);
            ee()->setMock('pro:FrontEdit', $frontEdit);
            ee()->setMock('load', new class {
                public function add_package_path($path, $viewCascading = true)
                {
                }

                public function remove_package_path($path)
                {
                }
            });
            ee()->setMock('extensions', new class {
                public function active_hook($name)
                {
                    return false;
                }
            });

            $channel = (object) [
                'cfields' => [
                    1 => ['fluid_field' => 11],
                ],
                'hidden_fields' => [],
            ];

            $dataParser = new CustomFieldPairDataParserStub(
                [
                    'site_id' => 1,
                    'channel_id' => 2,
                    'entry_id' => 5,
                    'field_id_11' => 'value',
                ],
                '',
                $channel,
                new class {
                    public function set_once_data($component, $data)
                    {
                    }
                }
            );

            $chunk = '{fluid_field}Body {fluid_field:frontedit}{/fluid_field}';
            $tagdata = '{fluid_field:frontedit}' . $chunk;
            $pfieldChunks = [
                1 => [
                    'fluid_field' => [
                        ['', 'Body {fluid_field:frontedit}', [], $chunk],
                    ],
                ],
            ];

            $result = (new \EE_Channel_custom_field_pair_parser())->replace($tagdata, $dataParser, $pfieldChunks);

            $this->assertSame('Body ', $result);
            $this->assertSame(0, $frontEdit->calls);
        }
    }
}
