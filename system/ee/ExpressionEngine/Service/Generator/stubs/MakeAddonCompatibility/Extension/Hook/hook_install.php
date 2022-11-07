        $data = [
{{hook_array}}
        ];

        foreach ($data as $hook) {
            ee()->db->insert('extensions', $hook);
        }
