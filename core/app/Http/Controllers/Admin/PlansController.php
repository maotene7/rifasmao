<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Rules\FileTypeValidate;
use App\Models\Plans;
use Illuminate\Http\Request;

class PlansController extends Controller
{
    public function methods()
    {
        $pageTitle = 'Plan List';
        $emptyMessage = 'Plans not found.';
        $methods = Plans::orderBy('status','desc')->orderBy('id')->get();
        return view('admin.plans.index', compact('pageTitle', 'emptyMessage', 'methods'));
    }

    public function create()
    {
        $pageTitle = 'New Withdrawal Method';
        return view('admin.withdraw.create', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max: 60',
            'image' => [
                'required',
                'image',
                new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'rate' => 'required|numeric|gt:0',
            'delay' => 'required',
            'currency' => 'required',
            'min_limit' => 'required|numeric|gt:0',
            'max_limit' => 'required|numeric|gt:min_limit',
            'fixed_charge' => 'required|numeric|gte:0',
            'percent_charge' => 'required|numeric|between:0,100',
            'instruction' => 'required|max:64000',
            'field_name.*'    => 'sometimes|required'
        ],[
            'field_name.*.required'=>'All field is required'
        ]);
        $filename = '';
        $path = imagePath()['withdraw']['method']['path'];
        $size = imagePath()['withdraw']['method']['size'];
        if ($request->hasFile('image')) {
            try {
                $filename = uploadImage($request->image, $path, $size);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
        }

        $input_form = [];
        if ($request->has('field_name')) {
            for ($a = 0; $a < count($request->field_name); $a++) {

                $arr = [];
                $arr['field_name'] = strtolower(str_replace(' ', '_', $request->field_name[$a]));
                $arr['field_level'] = $request->field_name[$a];
                $arr['type'] = $request->type[$a];
                $arr['validation'] = $request->validation[$a];
                $input_form[$arr['field_name']] = $arr;
            }
        }

        $method = new WithdrawMethod();
        $method->name = $request->name;
        $method->image = $filename;
        $method->rate = $request->rate;
        $method->delay = $request->delay;
        $method->min_limit = $request->min_limit;
        $method->max_limit = $request->max_limit;
        $method->fixed_charge = $request->fixed_charge;
        $method->percent_charge = $request->percent_charge;
        $method->currency = $request->currency;
        $method->description = $request->instruction;
        $method->user_data = $input_form;
        $method->save();
        $notify[] = ['success', $method->name . ' has been added.'];
        return redirect()->route('admin.withdraw.method.index')->withNotify($notify);
    }


    public function edit($id)
    {
        $pageTitle = 'Update Plans';
        $method = Plans::findOrFail($id);
        return view('admin.plans.edit', compact('pageTitle', 'method'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'           => 'required|max: 60',
            'image' => [
                'image',
                new FileTypeValidate(['jpeg', 'jpg', 'png'])],
            'rate'           => 'required|numeric|gt:0',
            'delay'          => 'required',
            'min_limit'      => 'required|numeric|gt:0',
            'max_limit'      => 'required|numeric|gt:min_limit',
            'fixed_charge'   => 'required|numeric|gte:0',
            'percent_charge' => 'required|numeric|between:0,100',
            'currency'       => 'required',
            'instruction'    => 'required|max:64000',
            'field_name.*'    => 'sometimes|required'
        ],[
            'field_name.*.required'=>'All field is required'
        ]);

        $method = WithdrawMethod::findOrFail($id);
        $filename = $method->image;

        $path = imagePath()['withdraw']['method']['path'];
        $size = imagePath()['withdraw']['method']['size'];

        if ($request->hasFile('image')) {
            try {
                $filename = uploadImage($request->image,$path, $size, $method->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Image could not be uploaded.'];
                return back()->withNotify($notify);
            }
        }


        $input_form = [];
        if ($request->has('field_name')) {
            for ($a = 0; $a < count($request->field_name); $a++) {
                $arr = [];
                $arr['field_name'] = strtolower(str_replace(' ', '_', $request->field_name[$a]));
                $arr['field_level'] = $request->field_name[$a];
                $arr['type'] = $request->type[$a];
                $arr['validation'] = $request->validation[$a];
                $input_form[$arr['field_name']] = $arr;
            }
        }

        $method->name           = $request->name;
        $method->image          = $filename;
        $method->rate           = $request->rate;
        $method->delay          = $request->delay;
        $method->min_limit      = $request->min_limit;
        $method->max_limit      = $request->max_limit;
        $method->fixed_charge   = $request->fixed_charge;
        $method->percent_charge = $request->percent_charge;
        $method->description    = $request->instruction;
        $method->user_data      = $input_form;
        $method->currency       = $request->currency;
        $method->save();

        $notify[] = ['success', $method->name . ' has been updated.'];
        return back()->withNotify($notify);
    }



    public function activate(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $method = WithdrawMethod::findOrFail($request->id);
        $method->status = 1;
        $method->save();
        $notify[] = ['success', $method->name . ' has been activated.'];
        return redirect()->route('admin.withdraw.method.index')->withNotify($notify);
    }

    public function deactivate(Request $request)
    {
        $request->validate(['id' => 'required|integer']);
        $method = WithdrawMethod::findOrFail($request->id);
        $method->status = 0;
        $method->save();
        $notify[] = ['success', $method->name . ' has been deactivated.'];
        return redirect()->route('admin.withdraw.method.index')->withNotify($notify);
    }

}
