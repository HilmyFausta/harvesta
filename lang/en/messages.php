<?php

/*
|--------------------------------------------------------------------------
| Shared UI Messages
|--------------------------------------------------------------------------
|
| Usage: __('messages.created', ['item' => __('tree')]). Keep every message a
| complete English sentence that ends with a period (PRD 3.1).
|
*/

return [
    'created' => 'The :item was created successfully.',
    'updated' => 'The :item was updated successfully.',
    'deleted' => 'The :item was deleted successfully.',
    'restored' => 'The :item was restored successfully.',
    'confirm_delete' => 'Are you sure you want to delete this :item? This action cannot be undone.',
    'not_found' => 'No :items match your filters.',
    'empty' => 'No :items yet.',
    'forbidden' => 'You do not have permission to perform this action.',

    'in_use' => [
        'category' => 'This category cannot be deleted because it is still used by tree types.',
        'tree_type' => 'This tree type cannot be deleted because it is still used by trees.',
        'block' => 'This block cannot be deleted because it still has trees.',
        'harvest' => 'This harvest cannot be deleted because it already has sales.',
    ],

    'dead_tree' => 'Dead trees cannot receive new care logs or harvests.',
    'sale_exceeds_harvest' => 'The quantity exceeds the remaining unsold amount of this harvest (:remaining :unit).',
    'last_admin' => 'You cannot remove or demote the last admin.',
];
