        ee()->db->where('class', '{{slug_uc}}_ext');
        ee()->db->delete('extensions');
