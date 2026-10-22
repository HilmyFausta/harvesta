<?php

namespace App\Livewire\Forms;

use App\Enums\TreeStatus;
use App\Models\Tree;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Form;

/**
 * Shared state, validation, and persistence for the tree create and edit pages (PRD F-05).
 */
class TreeForm extends Form
{
    public ?Tree $tree = null;

    public string $code = '';

    public string $tree_type_id = '';

    public string $block_id = '';

    public string $planted_on = '';

    public string $status = '';

    public string $height_cm = '';

    public string $notes = '';

    /** @var UploadedFile|null */
    public $photo = null;

    public bool $remove_photo = false;

    /**
     * Fill the form from an existing tree.
     */
    public function setTree(Tree $tree): void
    {
        $this->tree = $tree;
        $this->code = $tree->code;
        $this->tree_type_id = (string) $tree->tree_type_id;
        $this->block_id = (string) $tree->block_id;
        $this->planted_on = $tree->planted_on->format('Y-m-d');
        $this->status = $tree->status->value;
        $this->height_cm = $tree->height_cm ?? '';
        $this->notes = $tree->notes ?? '';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            // Blank on create means "generate the next T-0001 code".
            'code' => [
                $this->tree ? 'required' : 'nullable', 'string', 'max:30',
                Rule::unique('trees', 'code')->ignore($this->tree?->id),
            ],
            'tree_type_id' => ['required', 'integer', Rule::exists('tree_types', 'id')],
            'block_id' => ['required', 'integer', Rule::exists('blocks', 'id')],
            'planted_on' => ['required', 'date', 'before_or_equal:today'],
            'status' => ['required', Rule::enum(TreeStatus::class)],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'tree_type_id' => __('tree type'),
            'block_id' => __('block'),
            'planted_on' => __('planting date'),
            'height_cm' => __('height'),
        ];
    }

    /**
     * Validate and save the tree, storing a new photo on the photos disk.
     */
    public function save(): Tree
    {
        $this->validate();

        $tree = $this->tree ?? new Tree;

        $tree->fill([
            'code' => trim($this->code),
            'tree_type_id' => (int) $this->tree_type_id,
            'block_id' => (int) $this->block_id,
            'planted_on' => $this->planted_on,
            'status' => $this->status,
            'height_cm' => $this->height_cm !== '' ? $this->height_cm : null,
            'notes' => trim($this->notes) !== '' ? trim($this->notes) : null,
        ]);

        // The model deletes the previous file once the new path is saved.
        $newPhoto = $this->photo?->store('trees', 'photos');

        if ($newPhoto) {
            $tree->photo_path = $newPhoto;
        } elseif ($this->remove_photo) {
            $tree->photo_path = null;
        }

        try {
            $tree->save();
        } catch (\Throwable $e) {
            if ($newPhoto) {
                Storage::disk('photos')->delete($newPhoto);
            }

            throw $e;
        }

        $this->reset('photo', 'remove_photo');

        return $tree;
    }
}
