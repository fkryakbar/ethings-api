<?php

namespace App\Http\Controllers;

use App\Models\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage as FacadesStorage;
use Illuminate\Validation\ValidationException;

class StorageController extends Controller
{
    public function create_folder(Request $request)
    {
        $request->validate([
            'name' => 'max:20|required',
        ]);

        $request->merge([
            'item_id' => Str::uuid(),
            'user_id' => $request->user()->user_id,
        ]);

        $item = Storage::create($request->all());
        return response($item);
    }

    public function file_upload(Request $request)
    {
        $request->validate([
            "file" => ["required"],
            "file.*" => ["required", 'max:5120'],
        ]);
        if ($request->belongs_to) {
            $isValid = Storage::where('item_id', $request->belongs_to)->first();
            if (!$isValid) {
                return response(['message' => 'Folder is not valid'], 422);
            }
        }
        foreach ($request->file('file') as $file) {
            $path = $file->store('');

            $request->merge([
                'user_id' => $request->user()->user_id,
                'item_id' => $path,
                'real_path' => $path,
                'type' => $file->getClientOriginalExtension(),
                'name' => $file->getClientOriginalName()
            ]);
            // return $request->all();
            Storage::create($request->except(['file']));
        }
        return response([
            'message' => 'File(s) Uploaded'
        ]);
    }

    public function file_delete(Request $request)
    {
        $request->validate([
            'item_ids' => ['array', 'required']
        ]);

        foreach ($request->item_ids as $item_id) {
            $item = Storage::where('item_id', $item_id)->first();
            if ($item) {
                if ($item->type == 'folder') {
                    return response(['message' => 'Item cannot be folder'], 422);
                }
            } else {
                return response(['message' => 'Item Not found'], 404);
            }
        }

        foreach ($request->item_ids as $item_id) {
            $item = Storage::where('item_id', $item_id)->first();
            FacadesStorage::delete($item->real_path);
            $item->delete();
        }

        return response(["message" => "Item(s) deleted"]);
    }

    public function folder_delete(Request $request)
    {
        $request->validate([
            'item_id' => ['required']
        ]);

        $folder = Storage::where('item_id', $request->item_id)->first();
        if ($folder) {
            if ($folder->type == 'folder') {
                $files = Storage::where('belongs_to', $request->item_id)->get();
                // return response($files, 422);
                if (count($files) > 0) {
                    return response(['message' => "The folder must be empty"], 422);
                }
                $folder->delete();
                return response(['message' => "Folder deleted"]);
            }
            return response(['message' => "It's not a folder"], 422);
        }
        return response(['message' => 'Item not found'], 404);
    }

    public function get_item($folder_id)
    {
        if ($folder_id != 'root') {
            $folder = Storage::where('item_id', $folder_id)->first();
            if ($folder && $folder->type == 'folder') {
                $items = Storage::where('belongs_to', $folder_id)->get();
                return response([
                    'message' => 'Success',
                    'data' => $items
                ]);
            }
            return response(['message' => "It's not a folder"], 422);
        }
        $items = Storage::where('belongs_to', 'root')->get();
        return response([
            'message' => 'Success',
            'data' => $items
        ]);
    }

    public function update_item($item_id, Request $request)
    {
        $request->validate([
            'name' => 'required|max:40',
            'access' => 'required|max:40'
        ]);

        $item = Storage::where('item_id', $item_id)->first();
        if ($item) {
            $item->update($request->only(['name', 'access']));
            return response([
                'message' => 'Item Updated',
                'data' => $item
            ]);
        }
        return response(['message' => 'Item not found'], 404);
    }
}