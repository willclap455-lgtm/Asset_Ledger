<?php

return [
    'standard_movement' => [
        'name' => 'Inventory Movement Log',
        'description' => 'Compact operational movement log matching the legacy Word examples in the repository root.',
        'supported_types' => ['receiving', 'deployment', 'transfer', 'return', 'repair_intake', 'repair_return', 'swap', 'retirement'],
        'example_documents' => [
            'BANTANJ_20260501.doc',
            'MetAnschutz_20260520.doc',
            'UnifiedLA_20260505.doc',
            'WVState_20260511.doc',
        ],
    ],
];
