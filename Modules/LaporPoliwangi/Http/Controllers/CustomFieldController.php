<?php

namespace Modules\LaporPoliwangi\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mailbox;
use Modules\LaporPoliwangi\Models\CustomField;

class CustomFieldController extends Controller
{
    private $types = [
        'dropdown',
        'text',
        'textarea',
        'number',
        'date',
        'multiselect',
    ];

    private $typesWithOptions = [
        'dropdown',
        'multiselect',
    ];


    private $maxOptions = 100;

    private $maxOptionLength = 191;

    public function index($mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $fields = CustomField::where('mailbox_id', $mailbox_id)
            ->orderBy('id', 'desc')
            ->get();
        return view('laporpoliwangi::custom_field', compact('fields', 'mailbox_id', 'mailbox'));

    }

    public function store(Request $request, $mailbox_id)
    {
        $mailbox = Mailbox::findOrFail($mailbox_id);

        $this->validateRequest($request);

        CustomField::create([
            'nama_field' => $request->nama_field,
            'type_field' => $request->type_field,
            'options' => $this->prepareOptions($request),
            'show_in_conversation_list' => $request->has('show_in_conversation_list'),
            'required' => $request->has('required'),
            'mailbox_id' => $mailbox->id,
        ]);

        return redirect()
            ->route('laporpoliwangi.custom_fields', $mailbox_id)
            ->with('success', 'Custom field has been created successfully.');
    }

    public function update(Request $request, $mailbox_id, $field_id)
    {
        Mailbox::findOrFail($mailbox_id);

        $field = CustomField::where('mailbox_id', $mailbox_id)
            ->where('id', $field_id)
            ->firstOrFail();

        $this->validateRequest($request);

        $field->update([
            'nama_field' => $request->nama_field,
            'type_field' => $request->type_field,
            'options' => $this->prepareOptions($request),
            'show_in_conversation_list' => $request->has('show_in_conversation_list'),
            'required' => $request->has('required'),
        ]);

        return redirect()
            ->route('laporpoliwangi.custom_fields', $mailbox_id)
            ->with('success', 'Custom field has been updated successfully.');
    }

    public function destroy($mailbox_id, $field_id)
    {
        Mailbox::findOrFail($mailbox_id);

        $field = CustomField::where('mailbox_id', $mailbox_id)
            ->where('id', $field_id)
            ->firstOrFail();

        $field->delete();

        return redirect()
            ->route('laporpoliwangi.custom_fields', $mailbox_id)
            ->with('success', 'Custom field has been deleted successfully.');
    }

    public function getByMailbox($mailbox_id)
    {
        Mailbox::findOrFail($mailbox_id);

        $fields = CustomField::where('mailbox_id', $mailbox_id)->get();

        return response()->json($fields);
    }

    private function validateRequest(Request $request)
    {
        $request->validate(
            [
                'nama_field' => 'required|string|min:2|max:191',
                'type_field' => 'required|in:' . implode(',', $this->types),
                'options' => 'nullable|string|max:10000',
                'show_in_conversation_list' => 'nullable',
                'required' => 'nullable',
            ],
            [
                'nama_field.required' => 'The field name is required.',
                'nama_field.string' => 'The field name must be a valid text.',
                'nama_field.min' => 'The field name must be at least 2 characters.',
                'nama_field.max' => 'The field name may not be greater than 191 characters.',

                'type_field.required' => 'The field type is required.',
                'type_field.in' => 'The selected field type is invalid.',

                'options.string' => 'The options must be valid text.',
                'options.max' => 'The options text is too long.',
            ]
        );

        if (
            in_array($request->type_field, $this->typesWithOptions)
            && !$request->filled('options')
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'options' => 'Options are required for Dropdown and Multiselect Dropdown.'
                ])
                ->throwResponse();
        }

        if (
            !in_array($request->type_field, $this->typesWithOptions)
            && $request->filled('options')
        ) {
            return back()
                ->withInput()
                ->withErrors([
                    'options' => 'Options are only allowed for Dropdown and Multiselect Dropdown.'
                ])
                ->throwResponse();
        }
    }

    private function prepareOptions(Request $request)
    {
        if (!in_array($request->type_field, $this->typesWithOptions)) {
            return null;
        }

        $options = collect(preg_split('/\r\n|\r|\n|,/', $request->options))
            ->map(function ($val) {
                return trim($val);
            })
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        if (count($options) < 1) {
            return back()
                ->withInput()
                ->withErrors([
                    'options' => 'At least one option is required.'
                ])
                ->throwResponse();
        }

        if (count($options) > $this->maxOptions) {
            return back()
                ->withInput()
                ->withErrors([
                    'options' => 'The options may not contain more than ' . $this->maxOptions . ' items.'
                ])
                ->throwResponse();
        }

        foreach ($options as $option) {
            if (mb_strlen($option) > $this->maxOptionLength) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'options' => 'Each option may not be greater than ' . $this->maxOptionLength . ' characters.'
                    ])
                    ->throwResponse();
            }
        }

        return $options;
    }
}
