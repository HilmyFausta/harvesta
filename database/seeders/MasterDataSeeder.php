<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Category;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Tree types grouped by category: name => scientific name.
     *
     * @var array<string, array{description: string, types: array<string, string>}>
     */
    public const CATEGORIES = [
        'Fruit' => [
            'description' => 'Trees grown mainly for their edible fruit.',
            'types' => [
                'Durian' => 'Durio zibethinus',
                'Mango' => 'Mangifera indica',
                'Rambutan' => 'Nephelium lappaceum',
                'Avocado' => 'Persea americana',
            ],
        ],
        'Timber' => [
            'description' => 'Trees grown for wood and long-term investment.',
            'types' => [
                'Teak' => 'Tectona grandis',
                'Mahogany' => 'Swietenia macrophylla',
                'Albizia' => 'Falcataria falcata',
            ],
        ],
        'Ornamental' => [
            'description' => 'Trees planted for shade and landscaping.',
            'types' => [
                'Frangipani' => 'Plumeria rubra',
                'Flame Tree' => 'Delonix regia',
            ],
        ],
        'Spice' => [
            'description' => 'Trees that produce spices.',
            'types' => [
                'Clove' => 'Syzygium aromaticum',
                'Nutmeg' => 'Myristica fragrans',
            ],
        ],
    ];

    /**
     * @var array<string, array{area_sqm: float, location_note: string, description: string}>
     */
    public const BLOCKS = [
        'North Block' => [
            'area_sqm' => 12500.0,
            'location_note' => 'Next to the main gate.',
            'description' => 'Mostly mature fruit trees.',
        ],
        'East Block' => [
            'area_sqm' => 9800.0,
            'location_note' => 'On the slope behind the storage shed.',
            'description' => 'Mixed timber and spice trees.',
        ],
        'River Block' => [
            'area_sqm' => 7200.5,
            'location_note' => 'Along the irrigation canal.',
            'description' => 'Young trees and new plantings.',
        ],
    ];

    /**
     * Seed categories, tree types, and blocks.
     */
    public function run(): void
    {
        foreach (self::CATEGORIES as $categoryName => $data) {
            $category = Category::updateOrCreate(
                ['name' => $categoryName],
                ['description' => $data['description']],
            );

            foreach ($data['types'] as $typeName => $scientificName) {
                $category->treeTypes()->updateOrCreate(
                    ['name' => $typeName],
                    ['scientific_name' => $scientificName],
                );
            }
        }

        foreach (self::BLOCKS as $name => $attributes) {
            Block::updateOrCreate(['name' => $name], $attributes);
        }
    }
}
