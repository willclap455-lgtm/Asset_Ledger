<?php

return [
    'standard_movement' => [
        'name' => 'Standard Inventory Movement Form',
        'description' => 'Fallback DOCX layout used when production Word templates are not yet imported.',
        'supported_types' => ['receiving', 'deployment', 'transfer', 'return', 'repair_intake', 'repair_return', 'swap', 'retirement'],
    ],
];
