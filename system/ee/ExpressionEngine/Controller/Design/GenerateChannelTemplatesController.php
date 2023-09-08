<?php

class GenerateChannelTemplatesController
{

    protected $generator;

    public function __construct()
    {
        $this->generator = new ChannelTemplatesGenerator;
    }

    public function handle($request)
    {
        $channel = $request->get('channel');

        // convert to model
        if (!$channel->exists) {
            return;
        }

        $this->generator->generate([
            'channel' => $channel
        ]);

        return response(200);
    }
}
