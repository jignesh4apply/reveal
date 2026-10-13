<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promocode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PromocodeController extends Controller
{
    public function index()
    {
        Session::put('page', 'promocode');
        $promocodes = Promocode::all();
        return view('admin.promocode.index', compact('promocodes'));
    }

    public function create(Request $request)
    {
        Session::put('page', 'promocode');

        if ($request->isMethod('post')) {

            $data = $request->all();

            $validation = [
                'title' => 'required|string|max:255',
                'promocode' => 'required|string|max:255|unique:promocodes',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|boolean',
                'discount_type' => 'required|in:percentage,amount',
                'discount_value' => 'required|numeric|min:0',
            ];

            if ($request->has('is_yearly_commitment')) {
                $validation['yc_discount_type'] = 'required|in:percentage,amount';
                $validation['yc_discount_value'] = 'required|numeric|min:0';
            }

            $validator = Validator::make($data, $validation);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors($validator->getMessageBag());
            }

            $input = $request->except(['_token']);

            // Slug
            $input['slug'] = Str::slug($data['title']);

            $input['is_yearly_commitment'] = $request->has('is_yearly_commitment') ? 1 : 0;

            if (!$request->has('is_yearly_commitment')) {
                $input['yc_discount_type'] = null;
                $input['yc_discount_value'] = null;
            }

            Promocode::create($input);

            return redirect()->route('admin.promocode.index')
                ->with('success', 'Promo code created successfully.');
        }

        return view('admin.promocode.create');
    }

    public function update(Request $request, $slug, $id)
    {
        Session::put('page', 'promocode');

        $promocode = Promocode::findOrFail($id);

        if ($request->isMethod('post')) {

            $data = $request->all();

            $validation = [
                'title' => 'required|string|max:255',
                'promocode' => 'required|string|max:255|unique:promocodes,promocode,' . $promocode->id,
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|boolean',
                'discount_type' => 'required|in:percentage,amount',
                'discount_value' => 'required|numeric|min:0',
            ];

            if ($request->has('is_yearly_commitment')) {
                $validation['yc_discount_type'] = 'required|in:percentage,amount';
                $validation['yc_discount_value'] = 'required|numeric|min:0';
            }

            $validator = Validator::make($data, $validation);

            if ($validator->fails()) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors($validator->getMessageBag());
            }

            $input = $request->except(['_token']);

            $input['slug'] = Str::slug($data['title']);

            $input['is_yearly_commitment'] = $request->has('is_yearly_commitment') ? 1 : 0;

            if (!$request->has('is_yearly_commitment')) {
                $input['yc_discount_type'] = null;
                $input['yc_discount_value'] = null;
            }

            $promocode->update($input);

            return redirect()->route('admin.promocode.index')
                ->with('success', 'Promo code updated successfully.');
        }

        return view('admin.promocode.edit', ['promocode' => $promocode]);
    }

    public function destroy($slug, $id)
    {
        Session::put('page', 'promocode');
        Promocode::where(['slug' => $slug, 'id' => $id])->delete();
        sleep(1);
        return redirect()->back();
    }
}
